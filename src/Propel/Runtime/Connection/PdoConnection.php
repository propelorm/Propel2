<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use PDO;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
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
        return $this->pdo->$method(...$args);
    }

    /**
     * @param string $name The datasource name associated to this connection
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null The datasource name associated to this connection
     */
    public function getName(): ?string
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
        if ($options) {
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
     * @param string|int $attribute The attribute to set (e.g. 'PDO::ATTR_CASE', or more simply 'ATTR_CASE').
     * @param mixed $value The attribute value.
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return bool
     */
    public function setAttribute($attribute, $value): bool
    {
        if (is_string($attribute) && strpos($attribute, '::') === false) {
            $attribute = '\PDO::' . $attribute;
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf('Invalid PDO option/attribute name specified: `%s`', $attribute));
            }
            $attribute = constant($attribute);
        }

        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * @inheritDoc
     */
    public function getDataFetcher($data): DataFetcherInterface
    {
        return new PDODataFetcher($data);
    }

    /**
     * @inheritDoc
     */
    public function getSingleDataFetcher($data): DataFetcherInterface
    {
        return $this->getDataFetcher($data);
    }

    /**
     * @inheritDoc
     *
     * @return \PDOStatement|false
     */
    public function query(string $statement)
    {
        return $this->pdo->query($statement);
    }

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function exec($statement): int
    {
        return (int)$this->pdo->exec($statement);
    }

    /**
     * @inheritDoc
     */
    public function inTransaction(): bool
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
     * @return string|false
     */
    public function lastInsertId(?string $name = null)
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     *
     * @param string $statement
     * @param array $driverOptions
     *
     * @return \PDOStatement|false
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
    public function quote(string $string, int $parameterType = PDO::PARAM_STR): string
    {
        return $this->pdo->quote($string, $parameterType);
    }

    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
