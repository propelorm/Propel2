<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Query\Criteria;

use \PDO;

/**
 * This is used to connect to PostgresQL databases.
 *
 * <a href="http://www.pgsql.org">http://www.pgsql.org</a>
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Hakan Tandogan <hakan42@gmx.de> (Torque)
 */
class PgsqlAdapter extends PdoAdapter implements AdapterInterface
{

    /**
     * Returns SQL which concatenates the second string to the first.
     *
     * @param     string  $s1  String to concatenate.
     * @param     string  $s2  String to append.
     *
     * @return    string
     */
    public function concatString($s1, $s2)
    {
        return "($s1 || $s2)";
    }

    /**
     * Returns SQL which extracts a substring.
     *
     * @param     string   $s  String to extract from.
     * @param     integer  $pos  Offset to start from.
     * @param     integer  $len  Number of characters to extract.
     *
     * @return    string
     */
    public function subString($s, $pos, $len)
    {
        return "substring($s from $pos" . ($len > -1 ? "for $len" : "") . ")";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param     string  $s  String to calculate length of.
     * @return    string
     */
    public function strLength($s)
    {
        return "char_length($s)";
    }

    /**
     * @see       AbstractAdapter::getIdMethod()
     *
     * @return    integer
     */
    protected function getIdMethod()
    {
        return AbstractAdapter::ID_METHOD_SEQUENCE;
    }

    /**
     * Gets ID for specified sequence name.
     *
     * @param     ConnectionInterface $con
     * @param     string  $name
     *
     * @return    integer
     */
    public function getId(ConnectionInterface $con, $name = null)
    {
        if ($name === null) {
            throw new InvalidArgumentException("Unable to fetch next sequence ID without sequence name.");
        }
        $stmt = $con->query("SELECT nextval(".$con->quote($name).")");
        $row = $stmt->fetch(PDO::FETCH_NUM);

        return $row[0];
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     * @return    string
     */
    public function getTimestampFormatter()
    {
        return "Y-m-d H:i:s O";
    }

    /**
     * Returns timestamp formatter string for use in date() function.
     *
     * @return    string
     */
    public function getTimeFormatter()
    {
        return "H:i:s O";
    }

    /**
     * @see       AbstractAdapter::applyLimit()
     *
     * @param     string   $sql
     * @param     integer  $offset
     * @param     integer  $limit
     */
    public function applyLimit(&$sql, $offset, $limit)
    {
        if ( $limit > 0 ) {
            $sql .= " LIMIT ".$limit;
        }
        if ( $offset > 0 ) {
            $sql .= " OFFSET ".$offset;
        }
    }

    /**
     * @see       AbstractAdapter::random()
     *
     * @param     string  $seed
     * @return    string
     */
    public function random($seed=NULL)
    {
        return 'random()';
    }

    /**
     * @see       PdoAdapter::getDeleteFromClause()
     *
     * @param     Propel\Runtime\Query\Criteria  $criteria
     * @param     string    $tableName
     *
     * @return    string
     */
    public function getDeleteFromClause(Criteria $criteria, $tableName)
    {
        $sql = 'DELETE ';
        if ($queryComment = $criteria->getComment()) {
            $sql .= '/* ' . $queryComment . ' */ ';
        }
        if ($realTableName = $criteria->getTableForAlias($tableName)) {
            if ($this->useQuoteIdentifier()) {
                $realTableName = $this->quoteIdentifierTable($realTableName);
            }
            $sql .= 'FROM ' . $realTableName . ' AS ' . $tableName;
        } else {
            if ($this->useQuoteIdentifier()) {
                $tableName = $this->quoteIdentifierTable($tableName);
            }
            $sql .= 'FROM ' . $tableName;
        }

        return $sql;
    }

    /**
     * @see        AbstractAdapter::quoteIdentifierTable()
     *
     * @param     string  $table
     * @return    string
     */
    public function quoteIdentifierTable($table)
    {
        // e.g. 'database.table alias' should be escaped as '"database"."table" "alias"'
        return '"' . strtr($table, array('.' => '"."', ' ' => '" "')) . '"';
    }
}
