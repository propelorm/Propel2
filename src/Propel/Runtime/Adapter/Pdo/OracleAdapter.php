<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter\Pdo;

use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Lock;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Map\ColumnMap;
use RuntimeException;

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
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param array $settings
     *
     * @return void
     */
    public function initConnection(ConnectionInterface $con, array $settings): void
    {
        $con->exec("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD'");
        $con->exec("ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'");
        if (isset($settings['queries']) && is_array($settings['queries'])) {
            foreach ($settings['queries'] as $queries) {
                foreach ((array)$queries as $query) {
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
    public function concatString(string $s1, string $s2): string
    {
        return "CONCAT($s1, $s2)";
    }

    /**
     * @inheritDoc
     */
    public function compareRegex($left, $right): string
    {
        return sprintf('REGEXP_LIKE(%s, %s)', $left, $right);
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
        return "SUBSTR($s, $pos, $len)";
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
        return "LENGTH($s)";
    }

    /**
     * @see AdapterInterface::applyLimit()
     *
     * @param string $sql
     * @param int $offset
     * @param int $limit
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     *
     * @return void
     */
    public function applyLimit(string &$sql, int $offset, int $limit, ?Criteria $criteria = null): void
    {
        $params = [];
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
    protected function getIdMethod(): int
    {
        return AdapterInterface::ID_METHOD_SEQUENCE;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     * @param string|null $name
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return int
     */
    public function getId(ConnectionInterface $con, ?string $name = null): int
    {
        if ($name === null) {
            throw new InvalidArgumentException('Unable to fetch next sequence ID without sequence name.');
        }

        $dataFetcher = $con->query(sprintf('SELECT %s.nextval FROM dual', $name));
        if ($dataFetcher === false) {
            throw new RuntimeException('Query returned no statement.');
        }

        return $dataFetcher->fetchColumn();
    }

    /**
     * @param string|null $seed
     *
     * @return string
     */
    public function random(?string $seed = null): string
    {
        return 'dbms_random.value';
    }

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return \Propel\Runtime\ActiveQuery\Criteria The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria): Criteria
    {
        $selectColumns = $criteria->getSelectColumns();
        // clearSelectColumns also clears the aliases, so get them too
        $asColumns = $criteria->getAsColumns();
        $criteria->clearSelectColumns();
        $columnAliases = $asColumns;
        // add the select columns back
        foreach ($selectColumns as $id => $clause) {
            // Generate a unique alias
            $baseAlias = 'ORA_COL_ALIAS_' . $id;
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
     * @param \Propel\Runtime\Connection\StatementInterface $stmt
     * @param string $parameter
     * @param mixed $value
     * @param \Propel\Runtime\Map\ColumnMap $cMap
     * @param int|null $position
     *
     * @return bool
     */
    public function bindValue(StatementInterface $stmt, string $parameter, $value, ColumnMap $cMap, ?int $position = null): bool
    {
        if ($cMap->getType() === PropelTypes::CLOB_EMU) {
            return $stmt->bindParam(':p' . $position, $value, $cMap->getPdoType(), strlen($value));
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
     *
     * @return array
     */
    protected function prepareParams(array $params): array
    {
        if (isset($params['dsn'])) {
            $params['dsn'] = str_replace('oracle:', 'oci:', $params['dsn']);
        }

        return parent::prepareParams($params);
    }

    /**
     * @see AdapterInterface::applyLock()
     *
     * @param string $sql
     * @param \Propel\Runtime\ActiveQuery\Lock $lock
     *
     * @return void
     */
    public function applyLock(string &$sql, Lock $lock): void
    {
        $type = $lock->getType();

        if ($type === Lock::SHARED) {
            $sql .= ' LOCK IN SHARE MODE';
        } elseif ($type === Lock::EXCLUSIVE) {
            $sql .= ' FOR UPDATE';
        }
    }
}
