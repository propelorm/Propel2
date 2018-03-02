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
use Propel\Runtime\Adapter\Exception\UnsupportedEncodingException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;

/**
 * This is used to connect to a MSSQL database using pdo_sqlsrv driver.
 *
 * @author Benjamin Runnels
 */
class SqlsrvAdapter extends MssqlAdapter implements SqlAdapterInterface
{
    /**
     * @see parent::initConnection()
     *
     * @param ConnectionInterface $con
     * @param array               $settings An array of settings.
     */
    public function initConnection(ConnectionInterface $con, array $settings)
    {
        $con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $con->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

        parent::initConnection($con, $settings);
    }

    /**
     * @see parent::setCharset()
     *
     * @param ConnectionInterface $con
     * @param string              $charset
     *
     * @throws \Propel\Runtime\Adapter\Exception\UnsupportedEncodingException
     */
    public function setCharset(ConnectionInterface $con, $charset)
    {
        switch (strtolower($charset)) {
            case 'utf-8':
                $con->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8);
                break;
            case 'system':
                $con->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_SYSTEM);
                break;
            default:
                throw new UnsupportedEncodingException('only utf-8 or system encoding are supported by the pdo_sqlsrv driver');
        }
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
        foreach ($params as $param) {
            $tableName = $param['table'];
            $columnName = $param['column'];
            $value = $param['value'];

            // this is to workaround for a bug with pdo_sqlsrv inserting or updating blobs with null values
            // http://social.msdn.microsoft.com/Forums/en-US/sqldriverforphp/thread/5a755bdd-41e9-45cb-9166-c9da4475bb94
            if (null !== $tableName) {
                $cMap = $dbMap->getTable($tableName)->getColumn($columnName);
                if (null === $value && $cMap->isLob()) {
                    $sql = str_replace(":p$i", "CONVERT(VARBINARY(MAX), :p$i)", $sql);
                }
            }
            $i++;
        }
    }

    /**
     * @see AdapterInterface::bindValue()
     *
     * @param \PDOStatement $stmt
     * @param string        $parameter
     * @param mixed         $value
     * @param ColumnMap     $cMap
     * @param null|integer  $position
     *
     * @return boolean
     */
    public function bindValue(\PDOStatement $stmt, $parameter, $value, ColumnMap $cMap, $position = null)
    {
        if ($cMap->isTemporal()) {
            $value = $this->formatTemporalValue($value, $cMap);
        } elseif (is_resource($value) && $cMap->isLob()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            rewind($value);
            // pdo_sqlsrv must have bind binaries using bindParam so that the PDO::SQLSRV_ENCODING_BINARY
            // driver option can be utilized. This requires a unique blob parameter because the bindParam
            // value is passed by reference and if we didn't do this then the referenced parameter value
            // would change on the next loop
            $blob = 'blob'.$position;
            $$blob = $value;

            return $stmt->bindParam($parameter, ${$blob}, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        }

        return $stmt->bindValue($parameter, $value, $cMap->getPdoType());
    }
}
