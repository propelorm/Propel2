<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter\Pdo;

use PDO;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Lock;
use Propel\Runtime\Adapter\SqlAdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;

/**
 * This is used in order to connect to a MySQL database.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jon S. Stevens <jon@clearink.com> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 */
class MysqlAdapter extends PdoAdapter implements SqlAdapterInterface
{
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
        return "SUBSTRING($s, $pos, $len)";
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
        return "CHAR_LENGTH($s)";
    }

    /**
     * Locks the specified table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The Propel connection to use.
     * @param string $table The name of the table to lock.
     *
     * @return void
     */
    public function lockTable(ConnectionInterface $con, string $table): void
    {
        $con->exec("LOCK TABLE $table WRITE");
    }

    /**
     * Unlocks the specified table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $con The Propel connection to use.
     * @param string $table The name of the table to unlock.
     *
     * @return void
     */
    public function unlockTable(ConnectionInterface $con, string $table): void
    {
        $con->exec('UNLOCK TABLES');
    }

    /**
     * @see SqlAdapterInterface::quoteIdentifier()
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        return '`' . $text . '`';
    }

    /**
     * @see SqlAdapterInterface::quoteIdentifierTable()
     *
     * @param string $table
     *
     * @return string
     */
    public function quoteIdentifierTable(string $table): string
    {
        // e.g. 'database.table alias' should be escaped as '`database`.`table` `alias`'
        return '`' . strtr($table, ['.' => '`.`', ' ' => '` `']) . '`';
    }

    /**
     * @see SqlAdapterInterface::applyLimit()
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
        if ($limit >= 0) {
            $sql .= ' LIMIT ' . ($offset > 0 ? $offset . ', ' : '') . $limit;
        } elseif ($offset > 0) {
            $sql .= ' LIMIT ' . $offset . ', 18446744073709551615';
        }
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
        return 'rand(' . ((int)$seed) . ')';
    }

    /**
     * @see SqlAdapterInterface::bindValue()
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
        $pdoType = $cMap->getPdoType();
        // FIXME - This is a temporary hack to get around apparent bugs w/ PDO+MYSQL
        // See http://pecl.php.net/bugs/bug.php?id=9919
        if ($pdoType === PDO::PARAM_BOOL) {
            $value = (int)$value;
            $pdoType = PDO::PARAM_INT;

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
     * Prepare the parameters for a PDO connection.
     * Protects MySQL from charset injection risk.
     *
     * @see http://www.propelorm.org/ticket/1360
     *
     * @param array $params the connection parameters from the configuration
     *
     * @return array the modified parameters
     */
    protected function prepareParams(array $params): array
    {
        if (isset($params['settings']['charset'])) {
            if (strpos($params['dsn'], ';charset=') === false) {
                $params['dsn'] .= ';charset=' . $params['settings']['charset'];
                unset($params['settings']['charset']);
            }
        }

        return parent::prepareParams($params);
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
        $type = $lock->getType();

        if ($type === Lock::SHARED) {
            $sql .= ' LOCK IN SHARE MODE';
        } elseif ($type === Lock::EXCLUSIVE) {
            $sql .= ' FOR UPDATE';
        }
    }
}
