<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use PDO;
use Propel\Runtime\DataFetcher\PDODataFetcher;
use Propel\Runtime\Exception\InvalidArgumentException;

/**
 * PDO extension that implements ConnectionInterface and builds StatementInterface statements.
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
     * Forward any calls to an inaccessible method to the proxied connection.
     *
     * @param string $method
     * @param mixed $args
     *
     * @return mixed
     */
    public function __call(string $method, $args)
    {
        return call_user_func_array([$this->pdo, $method], $args);
    }

    /**
     * @param string $name The datasource name associated to this connection
     *
     * @return void
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
        // Convert option keys from a string to a PDO:: constant
        $pdoOptions = [];
        if (is_array($options)) {
            foreach ($options as $key => $option) {
                $index = (is_numeric($key)) ? $key : constant('PDO::' . $key);
                $pdoOptions[$index] = $option;
            }
        }

        $this->pdo = new PDO($dsn, $user, $password, $pdoOptions);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Sets a connection attribute.
     *
     * This is overridden here to allow names corresponding to PDO constant names.
     *
     * @param int|string $attribute The attribute to set (e.g. 'PDO::ATTR_CASE', or more simply 'ATTR_CASE').
     * @param mixed $value The attribute value.
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute) && strpos($attribute, '::') === false) {
            $attribute = '\PDO::' . $attribute;
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf('Invalid PDO option/attribute name specified: "%s"', $attribute));
            }
            $attribute = constant($attribute);
        }

        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * @inheritDoc
     */
    public function getDataFetcher($data)
    {
        return new PDODataFetcher($data);
    }

    /**
     * @inheritDoc
     */
    public function getSingleDataFetcher($data)
    {
        return $this->getDataFetcher($data);
    }

    /**
     * @inheritDoc
     *
     * @return \PDOStatement|bool
     */
    public function query($statement)
    {
        return $this->pdo->query($statement);
    }

    /**
     * @inheritDoc
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function exec($statement)
    {
        $stmt = $this->pdo->exec($statement);

        return $this->getDataFetcher($stmt);
    }

    /**
     * @inheritDoc
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(int $attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }

    /**
     * @param string|null $name
     *
     * @return string|int
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     *
     * @param string $statement
     * @param array $driverOptions
     *
     * @return \PDOStatement|bool
     */
    public function prepare(string $statement, array $driverOptions = [])
    {
        return $this->pdo->prepare($statement, $driverOptions);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     *
     * @param string $string
     * @param int $parameterType
     *
     * @return string
     */
    public function quote($string, $parameterType = PDO::PARAM_STR)
    {
        return $this->pdo->quote($string, $parameterType);
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}
