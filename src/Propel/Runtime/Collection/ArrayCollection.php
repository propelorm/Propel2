<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Class for iterating over a list of Propel objects stored as arrays
 *
 * @author Francois Zaninotto
 */
class ArrayCollection extends Collection
{
    /**
     * @var
     */
    protected $workerObject;

    /**
     * Save all the elements in the collection
     *
     * @param ConnectionInterface $con
     *
     * @throws ReadOnlyModelException
     * @throws PropelException
     */
    public function save($con = null)
    {
        if (!method_exists($this->getFullyQualifiedModel(), 'save')) {
            throw new ReadOnlyModelException('Cannot save objects on a read-only model');
        }
        if (null === $con) {
            $con = $this->getWriteConnection();
        }
        $con->beginTransaction();
        try {
            $obj = $this->getWorkerObject();
            foreach ($this as $element) {
                $obj->clear();
                $obj->fromArray($element);
                $obj->setNew($obj->isPrimaryKeyNull());
                $obj->save($con);
            }
            $con->commit();
        } catch (PropelException $e) {
            $con->rollback();
        }
    }

    /**
     * Delete all the elements in the collection
     *
     * @param ConnectionInterface $con
     *
     * @throws ReadOnlyModelException
     * @throws PropelException
     */
    public function delete($con = null)
    {
        if (!method_exists($this->getFullyQualifiedModel(), 'delete')) {
            throw new ReadOnlyModelException('Cannot delete objects on a read-only model');
        }
        if (null === $con) {
            $con = $this->getWriteConnection();
        }
        $con->beginTransaction();
        try {
            foreach ($this as $element) {
                $obj = $this->getWorkerObject();
                $obj->setDeleted(false);
                $obj->fromArray($element);
                $obj->delete($con);
            }
            $con->commit();
        } catch (PropelException $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Get an array of the primary keys of all the objects in the collection
     *
     * @param  boolean $usePrefix
     * @return array   The list of the primary keys of the collection
     */
    public function getPrimaryKeys($usePrefix = true)
    {
        $ret      = array();
        $callable = array($this->getTableMapClass(), 'getPrimaryKeyFromRow');

        foreach ($this as $key => $element) {
            $key       = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = call_user_func($callable, array_values($element));
        }

        return $ret;
    }

    /**
     * Populates the collection from an array
     * Uses the object model to force the column types
     * Does not empty the collection before adding the data from the array
     *
     * @param array $arr
     */
    public function fromArray($arr)
    {
        $obj  = $this->getWorkerObject();
        foreach ($arr as $element) {
            $obj->clear();
            $obj->fromArray($element);
            $this->append($obj->toArray());
        }
    }

    /**
     * Get an array representation of the collection
     * This is not an alias for getData(), since it returns a copy of the data
     *
     * @param string $keyColumn If null, the returned array uses an incremental index.
     *                                 Otherwise, the array is indexed using the specified column
     * @param boolean $usePrefix If true, the returned array prefixes keys
     *                                 with the model class name ('Article_0', 'Article_1', etc).
     *
     * <code>
     * $bookCollection->toArray();
     * array(
     *  0 => array('Id' => 123, 'Title' => 'War And Peace'),
     *  1 => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * $bookCollection->toArray('Id');
     * array(
     *  123 => array('Id' => 123, 'Title' => 'War And Peace'),
     *  456 => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * $bookCollection->toArray(null, true);
     * array(
     *  'Book_0' => array('Id' => 123, 'Title' => 'War And Peace'),
     *  'Book_1' => array('Id' => 456, 'Title' => 'Don Juan'),
     * )
     * </code>
     *
     * @return array
     */
    public function toArray($keyColumn = null, $usePrefix = false)
    {
        $ret = array();
        foreach ($this as $key => $element) {
            $key = null === $keyColumn ? $key : $element[$keyColumn];
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $element;
        }

        return $ret;
    }

    /**
     * Synonym for toArray(), to provide a similar interface to PropelObjectCollection
     *
     * @param string  $keyColumn
     * @param boolean $usePrefix
     *
     * @return array
     */
    public function getArrayCopy($keyColumn = null, $usePrefix = false)
    {
        if (null === $keyColumn && false === $usePrefix) {
            return parent::getArrayCopy();
        }

        return $this->toArray($keyColumn, $usePrefix);
    }

    /**
     * Get an associative array representation of the collection
     * The first parameter specifies the column to be used for the key,
     * And the second for the value.
     * <code>
     * $res = $coll->toKeyValue('Id', 'Name');
     * </code>
     *
     * @param string $keyColumn
     * @param string $valueColumn
     *
     * @return array
     */
    public function toKeyValue($keyColumn, $valueColumn)
    {
        $ret = array();
        foreach ($this as $obj) {
            $ret[$obj[$keyColumn]] = $obj[$valueColumn];
        }

        return $ret;
    }

    /**
     * @throws PropelException
     * @return BaseObject
     */
    protected function getWorkerObject()
    {
        if (null === $this->workerObject) {
            $model = $this->getModel();
            if (empty($model)) {
                throw new PropelException('You must set the collection model before interacting with it');
            }
            $class = $this->getFullyQualifiedModel();
            $this->workerObject = new $class();
        }

        return $this->workerObject;
    }
}
