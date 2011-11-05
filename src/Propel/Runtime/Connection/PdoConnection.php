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
use Propel\Runtime\Exception\PropelException;

use \PDO;

/**
 * Connection wrapping around a PDO connection that provides the basic fixes to PDO that are required by Propel.
 *
 */
class PdoConnection extends PDO implements ConnectionInterface
{
    protected $useDebug = false;

    /**
     * Attribute to use to set whether to cache prepared statements.
     */
    const PROPEL_ATTR_CACHE_PREPARES    = -1;
    
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
     * Sets a connection attribute.
     *
     * This is overridden here to provide support for setting Propel-specific attributes too.
     *
     * @param     integer  $attribute  The attribute to set (e.g. PropelPDO::PROPEL_ATTR_CACHE_PREPARES).
     * @param     mixed    $value  The attribute value.
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute) && strpos($attribute, '::') === false) {
            $attribute = '\PDO::' . $attribute;
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
                parent::setAttribute($attribute, $value);
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
                return parent::getAttribute($attribute);
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
    public function prepare($sql, $driver_options = array())
    {
        if ($this->cachePreparedStatements) {
            if (!isset($this->preparedStatements[$sql])) {
                $return = parent::prepare($sql, $driver_options);
                $this->preparedStatements[$sql] = $return;
            } else {
                $return = $this->preparedStatements[$sql];
            }
        } else {
            $return = parent::prepare($sql, $driver_options);
        }

        return $return;
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
    }

}
