<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Lock;
use Propel\Runtime\Adapter\Exception\ColumnNotFoundException;
use Propel\Runtime\Adapter\Exception\MalformedClauseException;
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\DatabaseMap;
use RuntimeException;

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
     * @see \Propel\Runtime\Adapter\AdapterInterface::setCharset()
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param string $charset
     *
     * @return void
     */
    public function setCharset(ConnectionInterface $con, string $charset): void
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
    public function concatString(string $s1, string $s2): string
    {
        return '(' . $s1 . ' + ' . $s2 . ')';
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param string $s String to extract from.
     * @param int $pos Offset to start from.
     * @param int $len Number of characters to extract.
     *
     * @return string
     */
    public function subString(string $s, int $pos, int $len): string
    {
        return 'SUBSTRING(' . $s . ', ' . $pos . ', ' . $len . ')';
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param string $s String to calculate length of.
     *
     * @return string
     */
    public function strLength(string $s): string
    {
        return 'LEN(' . $s . ')';
    }

    /**
     * @inheritDoc
     */
    public function compareRegex($left, $right): string
    {
        return sprintf('dbo.RegexMatch(%s, %s', $left, $right);
    }

    /**
     * @see \Propel\Runtime\Adapter\AdapterInterface::quoteIdentifier()
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        return '[' . $text . ']';
    }

    /**
     * @see \Propel\Runtime\Adapter\AdapterInterface::quoteIdentifierTable()
     *
     * @param string $table
     *
     * @return string
     */
    public function quoteIdentifierTable(string $table): string
    {
        // e.g. 'database.table alias' should be escaped as '[database].[table] [alias]'
        return '[' . strtr($table, ['.' => '].[', ' ' => '] [']) . ']';
    }

    /**
     * @see SqlAdapterInterface::random()
     *
     * @param string|null $seed
     *
     * @return string
     */
    public function random(?string $seed = null): string
    {
        return 'RAND(' . ((int)$seed) . ')';
    }

    /**
     * Simulated Limit/Offset
     *
     * This rewrites the $sql query to apply the offset and limit.
     * some of the ORDER BY logic borrowed from Doctrine MsSqlPlatform
     *
     * @author Benjamin Runnels <kraven@kraven.org>
     *
     * @see SqlAdapterInterface::applyLimit()
     *
     * @param string $sql
     * @param int $offset
     * @param int $limit
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     *
     * @throws \Propel\Runtime\Adapter\Exception\ColumnNotFoundException
     * @throws \Propel\Runtime\Adapter\Exception\MalformedClauseException
     *
     * @return void
     */
    public function applyLimit(string &$sql, int $offset, int $limit, ?Criteria $criteria = null): void
    {
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
            if ($sql[$i] === '(') {
                $parenthesisMatch++;
            } elseif ($sql[$i] === ')') {
                $parenthesisMatch--;
            } elseif ($parenthesisMatch === 0 && $i === stripos($sql, ' from ', $i)) {
                // If we hit a 'from' clause outside of matching parenthesis, split the
                // query string into `SELECT $selectStatement FROM $fromStatement`
                $selectStatement = trim(substr($sql, $selectTextLen, $i - $selectTextLen));
                $fromStatement = trim(substr($sql, $i + 6));

                break;
            }
        }

        if (!$selectStatement || !$fromStatement) {
            throw new MalformedClauseException('MssqlAdapter::applyLimit() could not locate the select statement at the start of the query.');
        }

        if (preg_match('/\Aselect(\s+)distinct/i', $sql)) {
            $selectText .= 'DISTINCT ';
            $selectStatement = str_ireplace('distinct ', '', $selectStatement);
        }

        // if we're starting at offset 0 then there's no need to simulate limit,
        // just grab the top $limit number of rows
        if ($offset === 0) {
            $sql = $selectText . 'TOP ' . $limit . ' ' . $selectStatement . ' FROM ' . $fromStatement;

            return;
        }

        // get the ORDER BY clause if present
        $orderStatement = stristr($fromStatement, 'ORDER BY');
        $orders = '';
        $orderArr = [];

        if ($orderStatement !== false) {
            // remove order statement from the from statement
            $fromStatement = trim(str_replace($orderStatement, '', $fromStatement));

            $order = str_ireplace('ORDER BY', '', $orderStatement);
            $orders = explode(',', $order);

            $nbOrders = count($orders);
            for ($i = 0; $i < $nbOrders; $i++) {
                $orderArr[trim((string)preg_replace('/\s+(ASC|DESC)$/i', '', $orders[$i]))] = [
                    'sort' => (stripos($orders[$i], ' DESC') !== false) ? 'DESC' : 'ASC',
                    'key' => $i,
                ];
            }
        }

        // setup inner and outer select selects
        $innerSelect = '';
        $outerSelect = '';
        $firstColumnOrderStatement = null;
        foreach (explode(', ', $selectStatement) as $selCol) {
            $selColArr = explode(' ', $selCol);
            $selColCount = count($selColArr) - 1;

            // make sure the current column isn't * or an aggregate
            if ($selColArr[0] !== '*' && !strstr($selColArr[0], '(')) {
                if (isset($orderArr[$selColArr[0]])) {
                    $orders[$orderArr[$selColArr[0]]['key']] = $selColArr[0] . ' ' . $orderArr[$selColArr[0]]['sort'];
                }

                // use the alias if one was present otherwise use the column name
                $alias = (!stristr($selCol, ' AS ')) ? $selColArr[0] : $selColArr[$selColCount];
                // don't quote the identifier if it is already quoted
                if ($alias[0] !== '[') {
                    $alias = $this->quoteIdentifier($alias);
                }

                // save the first non-aggregate column for use in ROW_NUMBER() if required
                if ($firstColumnOrderStatement === null) {
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
                if ($alias[0] !== '[') {
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
            if ($firstColumnOrderStatement !== null) {
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
     * @param string $sql
     * @param array $params
     * @param \Propel\Runtime\ActiveQuery\Criteria $values
     * @param \Propel\Runtime\Map\DatabaseMap $dbMap
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function cleanupSQL(string &$sql, array &$params, Criteria $values, DatabaseMap $dbMap): void
    {
        $i = 1;
        $paramCols = [];
        foreach ($params as $param) {
            if ($param['table'] !== null) {
                $column = $dbMap->getTable($param['table'])->getColumn($param['column']);
                /* MSSQL pdo_dblib and pdo_mssql blob values must be converted to hex and then the hex added
                 * to the query string directly.  If it goes through PDOStatement::bindValue quotes will cause
                 * an error with the insert or update.
                 */
                if (is_resource($param['value']) && $column->isLob()) {
                    // we always need to make sure that the stream is rewound, otherwise nothing will
                    // get written to database.
                    rewind($param['value']);
                    $hexArr = unpack('H*hex', (string)stream_get_contents($param['value']));
                    if (!$hexArr) {
                        throw new RuntimeException('Cannot unpack value `' . $param['value'] . '`');
                    }
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
                $sql = str_replace($match, ':p' . ($key + 1), $sql);
            }
        }
    }

    /**
     * @see SqlAdapterInterface::applyLock()
     *
     * @param string $sql
     * @param \Propel\Runtime\ActiveQuery\Lock $lock
     *
     * @return void
     */
    public function applyLock(string &$sql, Lock $lock): void
    {
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimestampFormatter(): string
    {
        return 'Y-m-d H:i:s:000';
    }

    /**
     * Returns time formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimeFormatter(): string
    {
        return 'H:i:s:000';
    }
}
