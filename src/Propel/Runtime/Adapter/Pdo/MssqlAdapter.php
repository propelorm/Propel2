<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Adapter\Exception\MalformedClauseException;
use Propel\Runtime\Adapter\Exception\ColumnNotFoundException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * This is used to connect to a MSSQL database.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
class MssqlAdapter extends PdoAdapter implements SqlAdapterInterface
{
    /**
     * MS SQL Server does not support SET NAMES
     *
     * @see AdapterInterface::setCharset()
     *
     * @param ConnectionInterface $con
     * @param string              $charset
     */
    public function setCharset(ConnectionInterface $con, $charset)
    {
    }

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param string $s1 String to concatenate.
     * @param string $s2 String to append.
     *
     * @return string
     */
    public function concatString($s1, $s2)
    {
        return '(' . $s1 . ' + ' . $s2 . ')';
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param string  $s   String to extract from.
     * @param integer $pos Offset to start from.
     * @param integer $len Number of characters to extract.
     *
     * @return string
     */
    public function subString($s, $pos, $len)
    {
        return 'SUBSTRING(' . $s . ', ' . $pos . ', ' . $len . ')';
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param  string $s String to calculate length of.
     * @return string
     */
    public function strLength($s)
    {
        return 'LEN(' . $s . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function compareRegex($left, $right)
    {
        return sprintf("dbo.RegexMatch(%s, %s", $left, $right);
    }

    /**
     * @see AdapterInterface::quoteIdentifier()
     *
     * @param  string $text
     * @return string
     */
    public function quoteIdentifier($text)
    {
        return '[' . $text . ']';
    }

    /**
     * @see AdapterInterface::quoteIdentifierTable()
     *
     * @param  string $table
     * @return string
     */
    public function quoteIdentifierTable($table)
    {
        // e.g. 'database.table alias' should be escaped as '[database].[table] [alias]'
        return '[' . strtr($table, ['.' => '].[', ' ' => '] [']) . ']';
    }

    /**
     * @see AdapterInterface::random()
     *
     * @param  string $seed
     * @return string
     */
    public function random($seed = null)
    {
        return 'RAND(' . ((int) $seed) . ')';
    }

    /**
     * Simulated Limit/Offset
     *
     * This rewrites the $sql query to apply the offset and limit.
     * some of the ORDER BY logic borrowed from Doctrine MsSqlPlatform
     *
     * @see AdapterInterface::applyLimit()
     * @author    Benjamin Runnels <kraven@kraven.org>
     *
     * @param string  $sql
     * @param integer $offset
     * @param integer $limit
     *
     * @return void
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        // make sure offset and limit are numeric
        if (!is_numeric($offset) || !is_numeric($limit)) {
            throw new InvalidArgumentException('MssqlAdapter::applyLimit() expects a number for argument 2 and 3');
        }

        // split the select and from clauses out of the original query
        $selectStatement = '';
        $fromStatement = '';
        $selectText = 'SELECT ';
        $selectTextLen = strlen($selectText);

        // Ensure that subqueries are ignored while iterating the SELECT list
        // and that the first non-subquery FROM statement is our split
        $parenthesisMatch = 0;
        $len = strlen($sql);

        for ($i = $selectTextLen; $i < $len; $i++) {
            if ('(' === $sql[$i]) {
                $parenthesisMatch++;
            } else if (')' === $sql[$i]) {
                $parenthesisMatch--;
            } else if (0 === $parenthesisMatch && $i === stripos($sql, ' from ', $i)) {
                // If we hit a 'from' clause outside of matching parenthesis, split the
                // query string into `SELECT $selectStatement FROM $fromStatement`
                $selectStatement = trim(substr($sql, $selectTextLen, $i - $selectTextLen));
                $fromStatement = trim(substr($sql, $i + 6));
                break;
            }
        }

        if (empty($selectStatement) || empty($fromStatement)) {
            throw new MalformedClauseException('MssqlAdapter::applyLimit() could not locate the select statement at the start of the query.');
        }

        if (preg_match('/\Aselect(\s+)distinct/i', $sql)) {
            $selectText .= 'DISTINCT ';
            $selectStatement = str_ireplace('distinct ', '', $selectStatement);
        }

        // if we're starting at offset 0 then there's no need to simulate limit,
        // just grab the top $limit number of rows
        if (0 === $offset) {
            $sql = $selectText . 'TOP ' . $limit . ' ' . $selectStatement . ' FROM ' . $fromStatement;

            return;
        }

        // get the ORDER BY clause if present
        $orderStatement = stristr($fromStatement, 'ORDER BY');
        $orders = '';

        if (false !== $orderStatement) {
            // remove order statement from the from statement
            $fromStatement = trim(str_replace($orderStatement, '', $fromStatement));

            $order = str_ireplace('ORDER BY', '', $orderStatement);
            $orders = explode(',', $order);

            $nbOrders = count($orders);
            for ($i = 0; $i < $nbOrders; $i ++) {
                $orderArr[trim(preg_replace('/\s+(ASC|DESC)$/i', '', $orders[$i]))] = [
                    'sort' => (stripos($orders[$i], ' DESC') !== false) ? 'DESC' : 'ASC',
                    'key' => $i
                ];
            }
        }

        // setup inner and outer select selects
        $innerSelect = '';
        $outerSelect = '';
        foreach (explode(', ', $selectStatement) as $selCol) {
            $selColArr = explode(' ', $selCol);
            $selColCount = count($selColArr) - 1;

            // make sure the current column isn't * or an aggregate
            if ($selColArr[0] != '*' && ! strstr($selColArr[0], '(')) {
                if (isset($orderArr[$selColArr[0]])) {
                    $orders[$orderArr[$selColArr[0]]['key']] = $selColArr[0] . ' ' . $orderArr[$selColArr[0]]['sort'];
                }

                // use the alias if one was present otherwise use the column name
                $alias = (! stristr($selCol, ' AS ')) ? $selColArr[0] : $selColArr[$selColCount];
                // don't quote the identifier if it is already quoted
                if ('[' !== $alias[0]) {
                    $alias = $this->quoteIdentifier($alias);
                }

                // save the first non-aggregate column for use in ROW_NUMBER() if required
                if (!isset($firstColumnOrderStatement)) {
                    $firstColumnOrderStatement = 'ORDER BY ' . $selColArr[0];
                }

                // add an alias to the inner select so all columns will be unique
                $innerSelect .= $selColArr[0] . ' AS ' . $alias . ', ';
                $outerSelect .= $alias . ', ';
            } else {
                // aggregate columns must always have an alias clause
                if (!stristr($selCol, ' AS ')) {
                    throw new MalformedClauseException('MssqlAdapter::applyLimit() requires aggregate columns to have an Alias clause');
                }

                // aggregate column alias can't be used as the count column you must use the entire aggregate statement
                if (isset($orderArr[$selColArr[$selColCount]])) {
                    $orders[$orderArr[$selColArr[$selColCount]]['key']] = str_replace($selColArr[$selColCount - 1] . ' ' . $selColArr[$selColCount], '', $selCol) . $orderArr[$selColArr[$selColCount]]['sort'];
                }

                // quote the alias
                $alias = $selColArr[$selColCount];
                // don't quote the identifier if it is already quoted
                if ($alias[0] != '[') {
                    $alias = $this->quoteIdentifier($alias);
                }

                $innerSelect .= str_replace($selColArr[$selColCount], $alias, $selCol) . ', ';
                $outerSelect .= $alias . ', ';
            }
        }

        if (is_array($orders)) {
            $orderStatement = 'ORDER BY ' . implode(', ', $orders);
        } else {
            // use the first non aggregate column in our select statement if no ORDER BY clause present
            if (isset($firstColumnOrderStatement)) {
                $orderStatement = $firstColumnOrderStatement;
            } else {
                throw new ColumnNotFoundException('MssqlAdapter::applyLimit() unable to find column to use with ROW_NUMBER()');
            }
        }

        // substring the select strings to get rid of the last comma and add our FROM and SELECT clauses
        $innerSelect = $selectText . 'ROW_NUMBER() OVER(' . $orderStatement . ') AS [RowNumber], ' . substr($innerSelect, 0, - 2) . ' FROM';
        // outer select can't use * because of the RowNumber column
        $outerSelect = 'SELECT ' . substr($outerSelect, 0, - 2) . ' FROM';

        // ROW_NUMBER() starts at 1 not 0
        $sql = $outerSelect . ' (' . $innerSelect . ' ' . $fromStatement . ') AS derivedb WHERE RowNumber BETWEEN ' . ($offset + 1) . ' AND ' . ($limit + $offset);
    }

    /**
     * @see parent::cleanupSQL()
     *
     * @param string      $sql
     * @param array       $params
     * @param Criteria    $values
     * @param DatabaseMap $dbMap
     */
    public function cleanupSQL(&$sql, array &$params, Criteria $values, DatabaseMap $dbMap)
    {
        $i = 1;
        $paramCols = [];
        foreach ($params as $param) {
            if (null !== $param['table']) {
                $column = $dbMap->getTable($param['table'])->getColumn($param['column']);
                /* MSSQL pdo_dblib and pdo_mssql blob values must be converted to hex and then the hex added
                 * to the query string directly.  If it goes through PDOStatement::bindValue quotes will cause
                 * an error with the insert or update.
                 */
                if (is_resource($param['value']) && $column->isLob()) {
                    // we always need to make sure that the stream is rewound, otherwise nothing will
                    // get written to database.
                    rewind($param['value']);
                    $hexArr = unpack('H*hex', stream_get_contents($param['value']));
                    $sql = str_replace(":p$i", '0x' . $hexArr['hex'], $sql);
                    unset($hexArr);
                    fclose($param['value']);
                } else {
                    $paramCols[] = $param;
                }
            }
            $i++;
        }

        // if we made changes re-number the params
        if ($params != $paramCols) {
            $params = $paramCols;
            unset($paramCols);
            preg_match_all('/:p\d/', $sql, $matches);
            foreach ($matches[0] as $key => $match) {
                $sql = str_replace($match, ':p'.($key+1), $sql);
            }
        }
    }
}
