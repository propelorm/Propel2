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
 * PDO extension that provides prepared statement caching.
 */
class ConnectionPdo extends PDO implements ConnectionInterface
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
     * Creates a PDO instance representing a connection to a database.
     */
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\Propel\Runtime\Connection\StatementPDO', array()));
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
}
