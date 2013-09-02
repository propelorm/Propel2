<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\DataFetcher\PDODataFetcher;

/**
 * PDO extension that implements ConnectionInterface and builds statements implementing StatementInterface.
 */
class PdoConnection extends \PDO implements SqlConnectionInterface
{
    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * @param string $name The datasource name associated to this connection
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Creates a PDO instance representing a connection to a database.
     */
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);

        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\Propel\Runtime\Adapter\Pdo\PdoStatement', array()));
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Sets a connection attribute.
     *
     * This is overridden here to allow names corresponding to PDO constant names.
     *
     * @param integer $attribute The attribute to set (e.g. 'PDO::ATTR_CASE', or more simply 'ATTR_CASE').
     * @param mixed   $value     The attribute value.
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute) && false === strpos($attribute, '::')) {
            $attribute = '\PDO::' . $attribute;
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf('Invalid PDO option/attribute name specified: "%s"', $attribute));
            }
            $attribute = constant($attribute);
        }

        return parent::setAttribute($attribute, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataFetcher($data)
    {
        return new PDODataFetcher($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getSingleDataFetcher($data)
    {
        return $this->getDataFetcher($data);
    }

    /**
     * {@inheritDoc}
     */
    public function query($statement)
    {
        return parent::query($statement);
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement)
    {
        $stmt = parent::exec($statement);

        return $this->getDataFetcher($stmt);
    }

    /**
     * Executes the given callable within a transaction.
     * This helper method takes care to commit or rollback the transaction.
     *
     * In case you want the transaction to rollback just throw an Exception of any type.
     *
     * @param callable $callable A callable to be wrapped in a transaction
     *
     * @return bool|mixed Returns the result of the callable on success, or <code>true</code> when the callable doesn't return anything.
     *
     * @throws Exception Re-throws a possible <code>Exception</code> triggered by the callable.
     */
    public function transaction(callable $callable)
    {
        $this->beginTransaction();

        try {
            $result = call_user_func($callable);

            $this->commit();

            if ($result) {
                return $result;
            }

            return true;
        } catch (\Exception $e) {
            $this->rollBack();

            throw $e;
        }
    }
}
