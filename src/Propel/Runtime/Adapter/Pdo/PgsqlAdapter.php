<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * This is used to connect to PostgreSQL databases.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hakan Tandogan <hakan42@gmx.de> (Torque)
 */
class PgsqlAdapter extends PdoAdapter implements SqlAdapterInterface
{
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
        return "($s1 || $s2)";
    }

    /**
     * {@inheritDoc}
     */
    public function compareRegex($left, $right)
    {
        return sprintf("%s ~* %s", $left, $right);
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
        return "substring($s from $pos" . ($len > -1 ? "for $len" : "") . ")";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param  string $s String to calculate length of.
     * @return string
     */
    public function strLength($s)
    {
        return "char_length($s)";
    }

    /**
     * @see AdapterInterface::getIdMethod()
     *
     * @return integer
     */
    protected function getIdMethod()
    {
        return AdapterInterface::ID_METHOD_SEQUENCE;
    }

    /**
     * Gets ID for specified sequence name.
     *
     * @param ConnectionInterface $con
     * @param string              $name
     *
     * @return integer
     */
    public function getId(ConnectionInterface $con, $name = null)
    {
        if (null === $name) {
            throw new InvalidArgumentException("Unable to fetch next sequence ID without sequence name.");
        }
        $dataFetcher = $con->query(sprintf('SELECT nextval(%s)', $con->quote($name)));

        return $dataFetcher->fetchColumn();
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     * @return string
     */
    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s O';
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return string
     */
    public function getTimeFormatter()
    {
        return 'H:i:s O';
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
        if ($limit >= 0) {
            $sql .= sprintf(' LIMIT %u', $limit);
        }
        if ($offset > 0) {
            $sql .= sprintf(' OFFSET %u', $offset);
        }
    }

    /**
     * @param Criteria $criteria
     *
     * @return string
     */
    public function getGroupBy(Criteria $criteria)
    {
        $groupBy = $criteria->getGroupByColumns();

        if ($groupBy) {
            // check if all selected columns are groupBy'ed.
            $selected = $this->getPlainSelectedColumns($criteria);
            $asSelects = $criteria->getAsColumns();

            foreach ($selected as $colName) {
                if (!in_array($colName, $groupBy)) {

                    // is a alias there that is grouped?
                    if ($alias = array_search($colName, $asSelects)) {
                        if (in_array($alias, $groupBy)) {
                            continue; //yes, alias is selected.
                        }
                    }
                    $groupBy[] = $colName;
                }
            }

            if ($groupBy) {
                return ' GROUP BY ' . implode(',', $groupBy);
            }
        }

        return '';
    }

    /**
     * @see AdapterInterface::random()
     *
     * @param  string $seed
     * @return string
     */
    public function random($seed = null)
    {
        return 'random()';
    }

    /**
     * @see PdoAdapter::getDeleteFromClause()
     *
     * @param Criteria $criteria
     * @param string   $tableName
     *
     * @return string
     */
    public function getDeleteFromClause(Criteria $criteria, $tableName)
    {
        $sql = 'DELETE ';
        if ($queryComment = $criteria->getComment()) {
            $sql .= '/* ' . $queryComment . ' */ ';
        }
        if ($realTableName = $criteria->getTableForAlias($tableName)) {
            $realTableName = $criteria->quoteIdentifierTable($realTableName);
            $sql .= 'FROM ' . $realTableName . ' AS ' . $tableName;
        } else {
            $tableName = $criteria->quoteIdentifierTable($tableName);
            $sql .= 'FROM ' . $tableName;
        }

        return $sql;
    }

    /**
     * @see AdapterInterface::quoteIdentifierTable()
     *
     * @param  string $table
     * @return string
     */
    public function quoteIdentifierTable($table)
    {
        // e.g. 'database.table alias' should be escaped as '"database"."table" "alias"'
        return '"' . strtr($table, array('.' => '"."', ' ' => '" "')) . '"';
    }

    /**
     * Do Explain Plan for query object or query string
     *
     * @param ConnectionInterface $con   propel connection
     * @param Criteria|string     $query query the criteria or the query string
     *
     * @throws PropelException
     * @return PDOStatement    A PDO statement executed using the connection, ready to be fetched
     */
    public function doExplainPlan(ConnectionInterface $con, $query)
    {
        if ($query instanceof Criteria) {
            $params = array();
            $dbMap = Propel::getServiceContainer()->getDatabaseMap($query->getDbName());
            $sql = $query->createSelectSql($params);
        } else {
            $sql = $query;
        }

        $stmt = $con->prepare($this->getExplainPlanQuery($sql));

        if ($query instanceof Criteria) {
            $this->bindValues($stmt, $params, $dbMap);
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Explain Plan compute query getter
     *
     * @param string $query query to explain
     */
    public function getExplainPlanQuery($query)
    {
        return 'EXPLAIN ' . $query;
    }
}
