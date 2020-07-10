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
 * PDO extension that implements ConnectionInterface and builds \PDOStatement statements.
 */
class PdoConnection implements ConnectionInterface
{
    use TransactionTrait;

    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * @var \PDO
     */
    protected $pdo;

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
     *
     * @param string $dsn
     * @param string|null $user
     * @param string|null $password
     * @param array|null $options
     */
    public function __construct(string $dsn, ?string $user = null, ?string $password = null, ?array $options = null)
    {
        // Convert option keys from a string to a \PDO:: constant
        $pdoOptions = [];
        if (is_array($options)) {
            foreach ($options as $key => $option) {
                $index = (is_numeric($key)) ? $key : constant('self::' . $key);
                $pdoOptions[$index] = $option;
            }
        }

        $this->pdo = new \PDO($dsn, $user, $password, $pdoOptions);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Sets a connection attribute.
     *
     * This is overridden here to allow names corresponding to PDO constant names.
     *
     * @param int|string $attribute The attribute to set (e.g. 'PDO::ATTR_CASE', or more simply 'ATTR_CASE').
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

        $this->pdo->setAttribute($attribute, $value);
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
        $this->pdo->query($statement);
    }

    /**
     * {@inheritDoc}
     */
    public function exec($statement)
    {
        $stmt = $this->pdo->exec($statement);

        return $this->getDataFetcher($stmt);
    }

    public function inTransaction()
    {
        // TODO: Implement inTransaction() method.
    }

    public function getAttribute(int $attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }

    public function lastInsertId(?string $name = null)
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @param string $statement
     * @param array $driver_options
     *
     * @return bool|\PDOStatement
     */
    public function prepare(string $statement, array $driver_options = [])
    {
        return $this->pdo->prepare($statement, $driver_options);
    }

    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return $this->pdo->quote($string, $parameter_type);
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}
