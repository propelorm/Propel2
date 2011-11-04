<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\AbstractConnection;
use Propel\Runtime\Exception\PropelException;

use \PDO;

/**
 * Connection wrapping around a PDO connection that provides the basic fixes to PDO that are required by Propel.
 *
 */
class PropelPDO extends AbstractConnection implements ConnectionInterface
{

    /**
     * Attribute to use to set whether to cache prepared statements.
     */
    const PROPEL_ATTR_CACHE_PREPARES    = -1;

    /**
     * The underlying PDO instance
     * @var PDO
     */
    protected $pdo;
    
    /**
     * Cache of prepared statements (PDOStatement) keyed by md5 of SQL.
     *
     * @var       array  [md5(sql) => PDOStatement]
     */
    protected $preparedStatements = array();

    /**
     * Whether to cache prepared statements.
     *
     * @var       boolean
     */
    protected $cachePreparedStatements = false;

    /**
     * The default value for runtime config item "debugpdo.logging.methods".
     *
     * @var       array
     */
    protected static $defaultLogMethods = array(
        'exec',
        'query',
        'statement_execute',
    );

    /**
     * Creates a PropelPDO instance representing a connection to a database.
     *.
     * If so configured, specifies a custom PDOStatement class and makes an entry
     * to the log with the state of this object just after its initialization.
     *
     * @param     PDO  $connectionData  A PDO instance
     */
    public function doConstruct($connectionData)
    {
        $this->pdo = $connectionData;

        if ($this->useDebug) {
            $this->configureStatementClass('\Propel\Runtime\Connection\DebugPDOStatement', true);
        }
    }

    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle.
     *
     * errorCode() only retrieves error codes for operations performed directly
     * on the database handle. 
     * If you create a Statement object through Connection::prepare() or 
     * Connection::query() and invoke an error on the statement handle,
     * Connection::errorCode() will not reflect that error. You must call
     * Statement::errorCode() to return the error code for an operation performed 
     * on a particular statement handle.
     *
     * @return mixed An SQLSTATE, a five characters alphanumeric identifier defined 
     *               in the ANSI SQL-92 standard, or NULL if no operation has been
     *               run on the database handle.
     */
    public function errorCode()
    {
        return $this->pdo->errorCode();
    }
    
    /**
     * Fetch extended error information associated with the last operation on 
     * the database handle.
     * 
     * @return array An array of error information about the last operation performed
     *               by this database handle
     */
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }
    
    public function quote($string, $parameter_type = PDO::TYPE_STR)
    {
        return $this->pdo->quote($string, $parameter_type);
    }
    
    /**
     * Return an array of available Connection drivers.
     * 
     * @return array A list of Conenction driver names. 
     *               If no drivers are available, it returns an empty array.
     */
    static public function getAvailableDrivers()
    {
        return $this->pdo->getAvailableDrivers();
    }
    
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }
    
    /**
     * Proxy to PDO::beginTransaction()
     *
     * @return    boolean
     */
    protected function doBeginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Proxy to PDO::commit()
     *
     * @return    boolean
     */
    protected function doCommit()
    {
        return $this->pdo->commit();
    }

    /**
     * Proxy to PDO::rollBack()
     *
     * @return    boolean  Whether operation was successful.
     */
    protected function doRollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Sets a connection attribute.
     *
     * This is overridden here to provide support for setting Propel-specific attributes too.
     *
     * @param     integer  $attribute  The attribute to set (e.g. PropelPDO::PROPEL_ATTR_CACHE_PREPARES).
     * @param     mixed    $value  The attribute value.
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute)) {
            if (strpos($attribute, '::') === false) {
                $attribute = '\PDO::' . $attribute;
            }
            if (!defined($attribute)) {
                throw new PropelException(sprintf('Invalid PDO option/attribute name specified: "%s"', $attribute));
            }
            $attribute = constant($attribute);
        }
        switch($attribute) {
            case self::PROPEL_ATTR_CACHE_PREPARES:
                $this->cachePreparedStatements = $value;
                break;
            default:
                $this->pdo->setAttribute($attribute, $value);
        }
    }

    /**
     * Gets a connection attribute.
     *
     * This is overridden here to provide support for setting Propel-specific attributes too.
     *
     * @param     integer  $attribute  The attribute to get (e.g. PropelPDO::PROPEL_ATTR_CACHE_PREPARES).
     * @return    mixed
     */
    public function getAttribute($attribute)
    {
        switch($attribute) {
            case self::PROPEL_ATTR_CACHE_PREPARES:
                return $this->cachePreparedStatements;
                break;
            default:
                return $this->pdo->getAttribute($attribute);
        }
    }

    /**
     * Proxy to PDO::prepare()
     * Add query caching support if the PropelPDO::PROPEL_ATTR_CACHE_PREPARES was set to true.
     *
     * @param     string  $sql  This must be a valid SQL statement for the target database server.
     * @param     array   $driver_options  One $array or more key => value pairs to set attribute values
     *                                      for the PDOStatement object that this method returns.
     *
     * @return    PDOStatement
     */
    protected function doPrepare($sql, $driver_options = array())
    {
        if ($this->cachePreparedStatements) {
            if (!isset($this->preparedStatements[$sql])) {
                $return = $this->pdo->prepare($sql, $driver_options);
                $this->preparedStatements[$sql] = $return;
            } else {
                $return = $this->preparedStatements[$sql];
            }
        } else {
            $return = $this->pdo->prepare($sql, $driver_options);
        }

        return $return;
    }

    /**
     * Proxy to PDO::exec()
     *
     * @param     string  $sql
     * @return    integer The number of affected rows
     */
    protected function doExec($sql)
    {
        return $this->pdo->exec($sql);
    }

    /**
     * Proxy to PDO::query()
     * Despite its signature here, this method takes a variety of parameters.
     *
     * @see       http://php.net/manual/en/pdo.query.php for a description of the possible parameters.
     *
     * @return    PDOStatement
     */
    protected function doQuery($statement)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->pdo, 'query'), $args);
    }

    /**
     * Clears any stored prepared statements for this connection.
     */
    public function clearStatementCache()
    {
        $this->preparedStatements = array();
    }

    /**
     * Configures the PDOStatement class for this connection.
     *
     * @param     string   $class
     * @param     boolean  $suppressError  Whether to suppress an exception if the statement class cannot be set.
     *
     * @throws    PropelException if the statement class cannot be set (and $suppressError is false).
     */
    protected function configureStatementClass($class = '\PDOStatement', $suppressError = true)
    {
        // extending PDOStatement is only supported with non-persistent connections
        if (!$this->getAttribute(PDO::ATTR_PERSISTENT)) {
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array($class, array($this)));
        } elseif (!$suppressError) {
            throw new PropelException('Extending PDOStatement is not supported with persistent connections.');
        }
    }

    /**
     * Returns the number of queries this DebugPDO instance has performed on the database connection.
     *
     * When using DebugPDOStatement as the statement class, any queries by DebugPDOStatement instances
     * are counted as well.
     *
     * @throws     PropelException if persistent connection is used (since unable to override PDOStatement in that case).
     * @return     integer
     */
    public function getQueryCount()
    {
        // extending PDOStatement is not supported with persistent connections
        if ($this->getAttribute(PDO::ATTR_PERSISTENT)) {
            throw new PropelException('Extending PDOStatement is not supported with persistent connections. Count would be inaccurate, because we cannot count the PDOStatment::execute() calls. Either don\'t use persistent connections or don\'t call PropelPDO::getQueryCount()');
        }

        return parent::getQueryCount();
    }


    /**
     * Enable or disable the query debug features
     *
     * @param     boolean  $value  True to enable debug (default), false to disable it
     */
    public function useDebug($value = true)
    {
        if ($value) {
            $this->configureStatementClass('\Propel\Runtime\Connection\DebugPDOStatement', true);
        } else {
            // reset query logging
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\PDOStatement'));
        }
        $this->clearStatementCache();
        parent::useDebug($value);
    }

    public function close()
    {
        $this->pdo = null;
    }
    
    /**
     * If so configured, makes an entry to the log of the state of this object just prior to its destruction.
     * Add PropelPDO::__destruct to $defaultLogMethods to see this message
     *
     * @see       self::log()
     */
    public function __destruct()
    {
        $this->close();
        parent::__destruct();
    }
}
