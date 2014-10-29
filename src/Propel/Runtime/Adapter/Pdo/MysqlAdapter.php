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
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\FieldMap;

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
     * Locks the specified entity.
     *
     * @param ConnectionInterface $con   The Propel connection to use.
     * @param string              $entity The name of the entity to lock.
     *
     * @throws \PDOException No Statement could be created or executed.
     */
    public function lockEntity($con, $entity)
    {
        $con->exec("LOCK TABLE $entity WRITE");
    }

    /**
     * Unlocks the specified entity.
     *
     * @param ConnectionInterface $con   The Propel connection to use.
     * @param string              $entity The name of the entity to unlock.
     *
     * @throws \PDOException No Statement could be created or executed.
     */
    public function unlockEntity($con, $entity)
    {
        $con->exec('UNLOCK TABLES');
    }

    /**
     * @see AdapterInterface::quoteIdentifier()
     *
     * @param  string $text
     * @return string
     */
    public function quoteIdentifier($text)
    {
        return '`' . $text . '`';
    }

    /**
     * @see AdapterInterface::quoteIdentifierEntity()
     *
     * @param  string $entity
     * @return string
     */
    public function quoteIdentifierEntity($entity)
    {
        // e.g. 'database.entity alias' should be escaped as '`database`.`entity` `alias`'
        return '`' . strtr($entity, array('.' => '`.`', ' ' => '` `')) . '`';
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
     * @param FieldMap          $cMap
     * @param null|integer       $position
     *
     * @return boolean
     */
    public function bindValue(StatementInterface $stmt, $parameter, $value, FieldMap $cMap, $position = null)
    {
        $pdoType = $cMap->getPdoType();
        // FIXME - This is a temporary hack to get around apparent bugs w/ PDO+MYSQL
        // See http://pecl.php.net/bugs/bug.php?id=9919
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
     * Prepare the parameters for a PDO connection.
     * Protects MySQL from charset injection risk.
     * @see http://www.propelorm.org/ticket/1360
     *
     * @param array $params the connection parameters from the configuration
     *
     * @return array the modified parameters
     */
    protected function prepareParams($params)
    {
        if (isset($params['settings']['charset'])) {
            if (false === strpos($params['dsn'], ';charset=')) {
                $params['dsn'] .= ';charset=' . $params['settings']['charset'];
                unset($params['settings']['charset']);
            }
        }

        return parent::prepareParams($params);
    }
}
