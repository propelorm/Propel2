<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
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
        $con->transaction(function () use ($con) {
            $obj = $this->getWorkerObject();
            foreach ($this as $element) {
                $obj->clear();
                $obj->fromArray($element);
                $obj->setNew($obj->isPrimaryKeyNull());
                $obj->save($con);
            }
        });
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
        $con->transaction(function () use ($con) {
            foreach ($this as $element) {
                $obj = $this->getWorkerObject();
                $obj->setDeleted(false);
                $obj->fromArray($element);
                $obj->delete($con);
            }
        });
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
        $callable = array($this->getEntityMapClass(), 'getPrimaryKeyFromRow');

        foreach ($this as $key => $element) {
            $key       = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = call_user_func($callable, array_values($element));
        }

        return $ret;
    }

    /**
     * Populates the collection from an array
     * Uses the object model to force the field types
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
     * @param string  $keyField If null, the returned array uses an incremental index.
     *                           Otherwise, the array is indexed using the specified field
     * @param boolean $usePrefix If true, the returned array prefixes keys
     *                           with the model class name ('Article_0', 'Article_1', etc).
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
    public function toArray($keyField = null, $usePrefix = false)
    {
        $ret = array();
        foreach ($this as $key => $element) {
            $key = null === $keyField ? $key : $element[$keyField];
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $element;
        }

        return $ret;
    }

    /**
     * Synonym for toArray(), to provide a similar interface to PropelObjectCollection
     *
     * @param string  $keyField
     * @param boolean $usePrefix
     *
     * @return array
     */
    public function getArrayCopy($keyField = null, $usePrefix = false)
    {
        if (null === $keyField && false === $usePrefix) {
            return parent::getArrayCopy();
        }

        return $this->toArray($keyField, $usePrefix);
    }

    /**
     * Get an associative array representation of the collection
     * The first parameter specifies the field to be used for the key,
     * And the second for the value.
     * <code>
     * $res = $coll->toKeyValue('Id', 'Name');
     * </code>
     *
     * @param string $keyField
     * @param string $valueField
     *
     * @return array
     */
    public function toKeyValue($keyField, $valueField)
    {
        $ret = array();
        foreach ($this as $obj) {
            $ret[$obj[$keyField]] = $obj[$valueField];
        }

        return $ret;
    }

    /**
     * @throws PropelException
     * @return ActiveRecordInterface
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
