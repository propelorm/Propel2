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
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Map\ColumnMap;
use Propel\Generator\Model\PropelTypes;

/**
 * Oracle adapter.
 *
 * @author David Giffin <david@giffin.org> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jon S. Stevens <jon@clearink.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Bill Schneider <bschneider@vecna.com> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
class OracleAdapter extends PdoAdapter implements SqlAdapterInterface
{
    /**
     * This method is called after a connection was created to run necessary
     * post-initialization queries or code.
     * Removes the charset query and adds the date queries
     *
     * @see parent::initConnection()
     *
     * @param \PDO  $con
     * @param array $settings
     */
    public function initConnection(ConnectionInterface $con, array $settings)
    {
        $con->exec("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD'");
        $con->exec("ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'");
        if (isset($settings['queries']) && is_array($settings['queries'])) {
            foreach ($settings['queries'] as $queries) {
                foreach ((array) $queries as $query) {
                    $con->exec($query);
                }
            }
        }
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
     * {@inheritDoc}
     */
    public function compareRegex($left, $right)
    {
        return sprintf("REGEXP_LIKE(%s, %s)", $left, $right);
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
        return "SUBSTR($s, $pos, $len)";
    }

    /**
     * Returns SQL which calculates the length (in chars) of a string.
     *
     * @param  string $s String to calculate length of.
     * @return string
     */
    public function strLength($s)
    {
        return "LENGTH($s)";
    }

    /**
     * @see AdapterInterface::applyLimit()
     *
     * @param string        $sql
     * @param integer       $offset
     * @param integer       $limit
     * @param null|Criteria $criteria
     */
    public function applyLimit(&$sql, $offset, $limit, $criteria = null)
    {
        $params = array();
        if ($criteria && $criteria->needsSelectAliases()) {
            $crit = clone $criteria;
            $selectSql = $this->createSelectSqlPart($crit, $params, true);
            $sql = $selectSql . substr($sql, strpos($sql, 'FROM') - 1);
        }
        $sql = 'SELECT B.* FROM ('
            . 'SELECT A.*, rownum AS PROPEL_ROWNUM FROM (' . $sql . ') A '
            . ') B WHERE ';

        if ($offset > 0) {
            $sql .= ' B.PROPEL_ROWNUM > ' . $offset;
            if ($limit > 0) {
                $sql .= ' AND B.PROPEL_ROWNUM <= ' . ( $offset + $limit );
            }
        } else {
            $sql .= ' B.PROPEL_ROWNUM <= ' . $limit;
        }
    }

    /**
     * @return int
     */
    protected function getIdMethod()
    {
        return AdapterInterface::ID_METHOD_SEQUENCE;
    }

    /**
     * @param ConnectionInterface $con
     * @param string              $name
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     * @return integer
     */
    public function getId(ConnectionInterface $con, $name = null)
    {
        if (null === $name) {
            throw new InvalidArgumentException('Unable to fetch next sequence ID without sequence name.');
        }

        $dataFetcher = $con->query(sprintf('SELECT %s.nextval FROM dual', $name));

        return $dataFetcher->fetchColumn();
    }

    /**
     * @param  string $seed
     * @return string
     */
    public function random($seed = null)
    {
        return 'dbms_random.value';
    }

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param  Criteria $criteria
     * @return Criteria The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria)
    {
        $selectColumns = $criteria->getSelectColumns();
        // clearSelectColumns also clears the aliases, so get them too
        $asColumns = $criteria->getAsColumns();
        $criteria->clearSelectColumns();
        $columnAliases = $asColumns;
        // add the select columns back
        foreach ($selectColumns as $id => $clause) {
            // Generate a unique alias
            $baseAlias = "ORA_COL_ALIAS_".$id;
            $alias = $baseAlias;
            // If it already exists, add a unique suffix
            $i = 0;
            while (isset($columnAliases[$alias])) {
                $i++;
                $alias = $baseAlias . '_' . $i;
            }
            // Add it as an alias
            $criteria->addAsColumn($alias, $clause);
            $columnAliases[$alias] = $clause;
        }
        // Add the aliases back, don't modify them
        foreach ($asColumns as $name => $clause) {
            $criteria->addAsColumn($name, $clause);
        }

        return $criteria;
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
        if (PropelTypes::CLOB_EMU === $cMap->getType()) {
            return $stmt->bindParam(':p'.$position, $value, $cMap->getPdoType(), strlen($value));
        }

        if ($cMap->isTemporal()) {
            $value = $this->formatTemporalValue($value, $cMap);
        } elseif (is_resource($value) && $cMap->isLob()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            rewind($value);
        }

        return $stmt->bindValue($parameter, $value, $cMap->getPdoType());
    }

    /**
     * We need to replace oracle: to oci: in connection's dsn.
     *
     * @param array $params
     * @return array
     */
    protected function prepareParams($params)
    {
        if (isset($params['dsn'])) {
            $params['dsn'] = str_replace('oracle:', 'oci:', $params['dsn']);
        }

        return parent::prepareParams($params);
    }
}
