<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;

/**
 * This is used in order to connect to a CUBRID database.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jon S. Stevens <jon@clearink.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
class CubridAdapter extends PdoAdapter implements AdapterInterface
{
    /**
    * CUBRID does not support SET NAMES
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
        return "CONCAT($s1, $s2)";
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
        return "SUBSTRING($s, $pos, $len)";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param  string $s String to calculate length of.
     * @return string
     */
    public function strLength($s)
    {
        return "CHAR_LENGTH($s)";
    }

    /**
     * @see AdapterInterface::quoteIdentifier()
     *
     * @param  string $text
     * @return string
     */
    public function quoteIdentifier($text)
    {
        return '`' . strtr($text, array('.' => '`.`')) . '`';
    }

    /**
     * @see AdapterInterface::quoteIdentifierTable()
     *
     * @param  string $table
     * @return string
     */
    public function quoteIdentifierTable($table)
    {
        // e.g. 'database.table alias' should be escaped as '`database`.`table` `alias`'
        return '`' . strtr($table, array('.' => '`.`', ' ' => '` `')) . '`';
    }

    /**
     * @see AdapterInterface::useQuoteIdentifier()
     *
     * @return boolean
     */
    public function useQuoteIdentifier()
    {
        return true;
    }

    /**
     * @see AdapterInterface::applyLimit()
     *
     * @param string  $sql
     * @param integer $offset
     * @param integer $limit
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        if ($limit > 0) {
            $sql .= ' LIMIT ' . ($offset > 0 ? $offset . ', ' : '') . $limit;
        } elseif ($offset > 0) {
            $sql .= ' LIMIT ' . $offset . ', 18446744073709551615';
        }
    }

    /**
     * @see AdapterInterface::random()
     *
     * @param  string $seed
     * @return string
     */
    public function random($seed = null)
    {
        return 'rand('.((int) $seed).')';
    }

    /**
     * @see AdapterInterface::bindValue()
     *
     * @param StatementInterface $stmt
     * @param string             $parameter
     * @param mixed              $value
     * @param ColumnMap          $cMap
     * @param null|integer       $position
     *
     * @return boolean
     */
    public function bindValue(StatementInterface $stmt, $parameter, $value, ColumnMap $cMap, $position = null)
    {
        $pdoType = $cMap->getPdoType();
        if (\PDO::PARAM_BOOL === $pdoType) {
            $value = (int) $value;
            $pdoType = \PDO::PARAM_INT;

            return $stmt->bindValue($parameter, $value, $pdoType);
        }

        if ($cMap->isTemporal()) {
            $value = $this->formatTemporalValue($value, $cMap);
        } elseif (is_resource($value) && $cMap->isLob()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            rewind($value);
        }

        return $stmt->bindValue($parameter, $value, $pdoType);
    }

    /**
     * @see parent
     */
    public function createSelectSqlPart(Criteria $criteria, &$fromClause, $aliasAll = false)
    {
        $selectClause = array();

        if ($aliasAll) {
            $this->turnSelectColumnsToAliases($criteria);
            // no select columns after that, they are all aliases
        } else {
            foreach ($criteria->getSelectColumns() as $columnName) {

                // expect every column to be of "table.column" formation
                // it could be a function:  e.g. MAX(books.price)

                $tableName = null;

                $parenPos = strrpos($columnName, '(');

                //the full column name must be quoted
                if (false === $parenPos) {
                    if ('*' != $columnName) {
                        $selectClause[] = $this->quoteIdentifier($columnName);
                    }
                } else {
                    $column = '';
                    $colName = substr($columnName, $parenPos + 1, -1);
                    if ('*' != $colName) {
                        $column = substr($columnName, 0, $parenPos + 1);
                        $column .= $this->quoteIdentifier($colName).')';
                    } else {
                        $column = $columnName;
                    }

                    $selectClause[] = $column;
                }

                $dotPos = strrpos($columnName, '.', ($parenPos !== false ? $parenPos : 0));

                if (false !== $dotPos) {
                    if (false === $parenPos) { // table.column
                        $tableName = substr($columnName, 0, $dotPos);
                    } else { // FUNC(table.column)
                        // functions may contain qualifiers so only take the last
                        // word as the table name.
                        // COUNT(DISTINCT books.price)
                        $tableName = substr($columnName, $parenPos + 1, $dotPos - ($parenPos + 1));
                        $lastSpace = strrpos($tableName, ' ');
                        if (false !== $lastSpace) { // COUNT(DISTINCT books.price)
                            $tableName = substr($tableName, $lastSpace + 1);
                        }
                    }
                    // is it a table alias?
                    $tableName2 = $criteria->getTableForAlias($tableName);
                    if ($tableName2 !== null) {
                        $fromClause[] = $tableName2 . ' ' . $tableName;
                    } else {
                        $fromClause[] = $tableName;
                    }
                } // if $dotPost !== false
            }
        }

        // set the aliases
        foreach ($criteria->getAsColumns() as $alias => $col) {
            //quote identifiers which are not part of a function
            $parenPos = strrpos($col, '(');
            if (false === $parenPos) {
                $column = $this->quoteIdentifier($col);
            } else {
                $column = $col;
            }
            $selectClause[] = $column . ' AS ' . $alias;
        }

        $selectModifiers = $criteria->getSelectModifiers();
        $queryComment = $criteria->getComment();

        // Build the SQL from the arrays we compiled
        $sql =  'SELECT '
            . ($queryComment ? '/* ' . $queryComment . ' */ ' : '')
            . ($selectModifiers ? (implode(' ', $selectModifiers) . ' ') : '')
            . implode(', ', $selectClause)
        ;

        return $sql;
    }

    /**
     * Facility method to quote raw data in update clause.
     *
     * @see BasePeer::doUpdate
     * @param string $raw The text to be quoted
     */
    public function quoteRaw($raw)
    {
        $pos = strpos($raw, ' ');
        $output = substr($raw, 0, $pos);
        $output = $this->quoteIdentifier($output);
        $output .= substr($raw, $pos, strlen($raw));

        return $output;
    }
}
