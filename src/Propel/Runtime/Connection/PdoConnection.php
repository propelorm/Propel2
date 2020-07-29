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
 * PDO extension that implements ConnectionInterface and builds \PDOStatement statements.
 */
class PdoConnection extends PDO implements ConnectionInterface
{
    use TransactionTrait;

    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

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
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        // Convert option keys from a string to a \PDO:: constant
        $pdoOptions = [];
        if (is_array($options)) {
            foreach ($options as $key => $option) {
                $index = (is_numeric($key)) ? $key : constant('self::' . $key);
                $pdoOptions[$index] = $option;
            }
        }

        parent::__construct($dsn, $user, $password, $pdoOptions);

        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Sets a connection attribute.
     *
     * This is overridden here to allow names corresponding to PDO constant names.
     *
     * @param int $attribute The attribute to set (e.g. 'PDO::ATTR_CASE', or more simply 'ATTR_CASE').
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

        return parent::setAttribute($attribute, $value);
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
     */
    public function query($statement)
    {
        return parent::query($statement);
    }

    /**
     * @inheritDoc
     */
    public function exec($statement)
    {
        $stmt = parent::exec($statement);

        return $this->getDataFetcher($stmt);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     *
     * @return bool|void
     */
    public function inTransaction()
    {
        return parent::inTransaction();
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     *
     * @param string|null $name
     *
     * @return string|int
     */
    public function lastInsertId($name = null)
    {
        return parent::lastInsertId($name);
    }

    /**
     * Overwrite. Fixes HHVM strict issue.
     *
     * @param string $statement
     * @param array|null $driver_options
     *
     * @return bool|\PDOStatement|void
     */
    public function prepare($statement, $driver_options = null)
    {
        return parent::prepare($statement, $driver_options ?: []);
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
        return parent::quote($string, $parameterType);
    }
}
