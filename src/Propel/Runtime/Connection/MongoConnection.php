<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

use \MongoCollection;
use Propel\Runtime\Formatter\MongoDataFetcher;

class MongoConnection extends \MongoClient implements ConnectionInterface
{
    /**
     * @var MongoCollection
     */
    private $collection;

    /**
     * @var MongoDB
     */
    private $db;

    public function __construct($server = "mongodb://localhost:27017", $database, $options = array("database" => "", "connect" => true))
    {
        parent::__construct($server, $options ?: array());

        if ($database) {
            $this->db = $this->selectDB($database);
        }

        $this->connection = $this;
    }

    public function getCollection($entity){
        if (null === $this->collection) {
            $this->collection = new MongoCollection($this->db, $entity);
        }
        return $this->collection;
    }

    /**
     * @param $data
     *
     * @return MongoDataFetcher
     */
    public function getDataFetcher($data){
        return new MongoDataFetcher($data);
    }

    /**
     * @param $data
     *
     * @return MongoDataFetcher
     */
    public function getSingleDataFetcher($data){
        return $this->getDataFetcher($data);
    }

    /**
     * @param string $name The datasource name associated to this connection
     */
    public function setName($name)
    {
        // TODO: Implement setName() method.
    }

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName()
    {
        // TODO: Implement getName() method.
    }

    /**
     * Turns off autocommit mode.
     *
     * While autocommit mode is turned off, changes made to the database via
     * the Connection object instance are not committed until you end the
     * transaction by calling Connection::commit().
     * Calling Connection::rollBack() will roll back all changes to the database
     * and return the connection to autocommit mode.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function beginTransaction()
    {
        // TODO: Implement beginTransaction() method.
    }

    /**
     * Commits a transaction.
     *
     * commit() returns the database connection to autocommit mode until the
     * next call to connection::beginTransaction() starts a new transaction.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function commit()
    {
        // TODO: Implement commit() method.
    }

    /**
     * Rolls back a transaction.
     *
     * Rolls back the current transaction, as initiated by beginTransaction().
     * It is an error to call this method if no transaction is active.
     * If the database was set to autocommit mode, this function will restore
     * autocommit mode after it has rolled back the transaction.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function rollBack()
    {
        // TODO: Implement rollBack() method.
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction()
    {
        // TODO: Implement inTransaction() method.
    }

    /**
     * Retrieve a database connection attribute.
     *
     * @param string $attribute The name of the attribute to retrieve,
     *                          e.g. PDO::ATTR_AUTOCOMMIT
     *
     * @return mixed A successful call returns the value of the requested attribute.
     *               An unsuccessful call returns null.
     */
    public function getAttribute($attribute)
    {
        // TODO: Implement getAttribute() method.
    }

    /**
     * Set an attribute.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setAttribute($attribute, $value)
    {
        // TODO: Implement setAttribute() method.
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * Returns the ID of the last inserted row, or the last value from a sequence
     * object, depending on the underlying driver. For example, PDO_PGSQL()
     * requires you to specify the name of a sequence object for the name parameter.
     *
     * @param string $name Name of the sequence object from which the ID should be
     *                     returned.
     *
     * @return string If a sequence name was not specified for the name parameter,
     *                returns a string representing the row ID of the last row that was
     *                inserted into the database.
     *                If a sequence name was specified for the name parameter, returns
     *                a string representing the last value retrieved from the specified
     *                sequence object.
     */
    public function lastInsertId($name = null)
    {
        // TODO: Implement lastInsertId() method.
    }


}
