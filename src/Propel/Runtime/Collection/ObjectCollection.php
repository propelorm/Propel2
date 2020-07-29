<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Collection\Exception\UnsupportedRelationException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;

/**
 * Class for iterating over a list of Propel objects
 *
 * @author Francois Zaninotto
 */
class ObjectCollection extends Collection
{
    /**
     * @var array
     */
    protected $index = [];

    /**
     * @var array
     */
    protected $indexSplHash = [];

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->rebuildIndex();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    public function exchangeArray($input)
    {
        $this->data = $input;
        $this->rebuildIndex();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function setData($data)
    {
        parent::setData($data);
        $this->rebuildIndex();
    }

    /**
     * Save all the elements in the collection
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function save($con = null)
    {
        if (!method_exists($this->getFullyQualifiedModel(), 'save')) {
            throw new ReadOnlyModelException('Cannot save objects on a read-only model');
        }
        if ($con === null) {
            $con = $this->getWriteConnection();
        }
        $con->transaction(function () use ($con) {
            /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $element */
            foreach ($this as $element) {
                $element->save($con);
            }
        });
    }

    /**
     * Delete all the elements in the collection
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @throws \Propel\Runtime\Collection\Exception\ReadOnlyModelException
     *
     * @return void
     */
    public function delete($con = null)
    {
        if (!method_exists($this->getFullyQualifiedModel(), 'delete')) {
            throw new ReadOnlyModelException('Cannot delete objects on a read-only model');
        }
        if ($con === null) {
            $con = $this->getWriteConnection();
        }
        $con->transaction(function () use ($con) {
            /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $element */
            foreach ($this as $element) {
                $element->delete($con);
            }
        });
    }

    /**
     * Get an array of the primary keys of all the objects in the collection
     *
     * @param bool $usePrefix
     *
     * @return array The list of the primary keys of the collection
     */
    public function getPrimaryKeys($usePrefix = true)
    {
        $ret = [];

        /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $obj */
        foreach ($this as $key => $obj) {
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj->getPrimaryKey();
        }

        return $ret;
    }

    /**
     * Populates the collection from an array
     * Each object is populated from an array and the result is stored
     * Does not empty the collection before adding the data from the array
     *
     * @param array $arr
     *
     * @return void
     */
    public function fromArray($arr)
    {
        $class = $this->getFullyQualifiedModel();
        foreach ($arr as $element) {
            /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $obj */
            $obj = new $class();
            $obj->fromArray($element);
            $this->append($obj);
        }
    }

    /**
     * Get an array representation of the collection
     * Each object is turned into an array and the result is returned
     *
     * @param string|null $keyColumn If null, the returned array uses an incremental index.
     *                                        Otherwise, the array is indexed using the specified column
     * @param bool $usePrefix If true, the returned array prefixes keys
     * with the model class name ('Article_0', 'Article_1', etc).
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME,
     *                                        TableMap::TYPE_CAMELNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME,
     *                                        TableMap::TYPE_NUM. Defaults to TableMap::TYPE_PHPNAME.
     * @param bool $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param array $alreadyDumpedObjects List of objects to skip to avoid recursion
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
    public function toArray(
        $keyColumn = null,
        $usePrefix = false,
        $keyType = TableMap::TYPE_PHPNAME,
        $includeLazyLoadColumns = true,
        $alreadyDumpedObjects = []
    ) {
        $ret = [];
        $keyGetterMethod = 'get' . $keyColumn;

        /** @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface $obj */
        foreach ($this->data as $key => $obj) {
            $key = $keyColumn === null ? $key : $obj->$keyGetterMethod();
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj->toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, true);
        }

        return $ret;
    }

    /**
     * Get an array representation of the collection
     *
     * @param string|null $keyColumn If null, the returned array uses an incremental index.
     *                           Otherwise, the array is indexed using the specified column
     * @param bool $usePrefix If true, the returned array prefixes keys
     * with the model class name ('Article_0', 'Article_1', etc).
     * <code>
     * $bookCollection->getArrayCopy();
     * array(
     * 0 => $book0,
     * 1 => $book1,
     * )
     * $bookCollection->getArrayCopy('Id');
     * array(
     * 123 => $book0,
     * 456 => $book1,
     * )
     * $bookCollection->getArrayCopy(null, true);
     * array(
     * 'Book_0' => $book0,
     * 'Book_1' => $book1,
     * )
     * </code>
     *
     * @return array
     */
    public function getArrayCopy($keyColumn = null, $usePrefix = false)
    {
        if ($keyColumn === null && $usePrefix === false) {
            return parent::getArrayCopy();
        }
        $ret = [];
        $keyGetterMethod = 'get' . $keyColumn;
        foreach ($this as $key => $obj) {
            $key = $keyColumn === null ? $key : $obj->$keyGetterMethod();
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj;
        }

        return $ret;
    }

    /**
     * Get an associative array representation of the collection
     * The first parameter specifies the column to be used for the key,
     * And the second for the value.
     *
     * <code>
     *   $res = $coll->toKeyValue('Id', 'Name');
     * </code>
     *
     * @param string $keyColumn
     * @param string|null $valueColumn
     *
     * @return array
     */
    public function toKeyValue($keyColumn = 'PrimaryKey', $valueColumn = null)
    {
        $ret = [];
        $keyGetterMethod = 'get' . $keyColumn;
        $valueGetterMethod = ($valueColumn === null) ? '__toString' : ('get' . $valueColumn);
        foreach ($this as $obj) {
            $ret[$obj->$keyGetterMethod()] = $obj->$valueGetterMethod();
        }

        return $ret;
    }

    /**
     * Get an associative array representation of the collection.
     * The first parameter specifies the column to be used for the key.
     *
     * <code>
     *   $res = $userCollection->toKeyIndex('Name');
     *
     *   $res = array(
     *       'peter' => class User #1 {$name => 'peter', ...},
     *       'hans' => class User #2 {$name => 'hans', ...},
     *       ...
     *   )
     * </code>
     *
     * @param string $keyColumn
     *
     * @return array
     */
    public function toKeyIndex($keyColumn = 'PrimaryKey')
    {
        $ret = [];
        $keyGetterMethod = 'get' . ucfirst($keyColumn);
        foreach ($this as $obj) {
            $ret[$obj->$keyGetterMethod()] = $obj;
        }

        return $ret;
    }

    /**
     * Get an array representation of the column.
     *
     * <code>
     *   $res = $userCollection->toKeyIndex('Name');
     *
     *   $res = array(
     *       'peter',
     *       'hans',
     *       ...
     *   )
     * </code>
     *
     * @param string $columnName
     *
     * @return array
     */
    public function getColumnValues($columnName = 'PrimaryKey')
    {
        $ret = [];
        $keyGetterMethod = 'get' . ucfirst($columnName);
        foreach ($this as $obj) {
            $ret[] = $obj->$keyGetterMethod();
        }

        return $ret;
    }

    /**
     * Makes an additional query to populate the objects related to the collection objects
     * by a certain relation
     *
     * @param string $relation Relation name (e.g. 'Book')
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria Optional Criteria object to filter the related object collection
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con Optional connection object
     *
     * @throws \Propel\Runtime\Exception\RuntimeException
     * @throws \Propel\Runtime\Collection\Exception\UnsupportedRelationException
     *
     * @return \Propel\Runtime\Collection\ObjectCollection The list of related objects
     */
    public function populateRelation($relation, $criteria = null, $con = null)
    {
        if (!Propel::isInstancePoolingEnabled()) {
            throw new RuntimeException(__METHOD__ . ' needs instance pooling to be enabled prior to populating the collection');
        }
        $relationMap = $this->getFormatter()->getTableMap()->getRelation($relation);
        if ($this->isEmpty()) {
            // save a useless query and return an empty collection
            $relationClassName = $relationMap->getRightTable()->getClassName();
            $collectionClassName = $relationMap->getRightTable()->getCollectionClassName();

            $coll = new $collectionClassName();
            $coll->setModel($relationClassName);
            $coll->setFormatter($this->getFormatter());

            return $coll;
        }
        $symRelationMap = $relationMap->getSymmetricalRelation();

        $query = PropelQuery::from($relationMap->getRightTable()->getClassName());
        if ($criteria !== null) {
            $query->mergeWith($criteria);
        }
        // query the db for the related objects
        $filterMethod = 'filterBy' . $symRelationMap->getName();
        $relatedObjects = $query
            ->$filterMethod($this)
            ->find($con);

        if ($relationMap->getType() === RelationMap::ONE_TO_MANY) {
            // initialize the embedded collections of the main objects
            $relationName = $relationMap->getName();
            foreach ($this as $mainObj) {
                $mainObj->initRelation($relationName);
            }
            // associate the related objects to the main objects
            $getMethod = 'get' . $symRelationMap->getName();
            $addMethod = 'add' . $relationName;
            foreach ($relatedObjects as $object) {
                $mainObj = $object->$getMethod();  // instance pool is used here to avoid a query
                $mainObj->$addMethod($object);
            }
        } elseif ($relationMap->getType() === RelationMap::MANY_TO_ONE) {
            // nothing to do; the instance pool will catch all calls to getRelatedObject()
            // and return the object in memory
        } else {
            throw new UnsupportedRelationException(__METHOD__ . ' does not support this relation type');
        }

        return $relatedObjects;
    }

    /**
     * @inheritDoc
     */
    public function search($element)
    {
        if (isset($this->indexSplHash[$splHash = spl_object_hash($element)])) {
            return $this->index[$this->indexSplHash[$splHash]];
        }

        $hashCode = $this->getHashCode($element);
        if (isset($this->index[$hashCode])) {
            return $this->index[$hashCode];
        }

        return false;
    }

    /**
     * @return void
     */
    protected function rebuildIndex()
    {
        $this->index = [];
        $this->indexSplHash = [];
        foreach ($this->data as $idx => $value) {
            $hashCode = $this->getHashCode($value);
            $this->index[$hashCode] = $idx;
            $this->indexSplHash[spl_object_hash($value)] = $hashCode;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (isset($this->data[$offset])) {
            if (is_object($this->data[$offset])) {
                unset($this->indexSplHash[spl_object_hash($this->data[$offset])]);
                unset($this->index[$this->getHashCode($this->data[$offset])]);
            }
            unset($this->data[$offset]);
        }
    }

    /**
     * @param mixed $element
     *
     * @return void
     */
    public function removeObject($element)
    {
        if (($pos = $this->search($element)) !== false) {
            $this->remove($pos);
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function append($value)
    {
        if (!is_object($value)) {
            parent::append($value);

            return;
        }

        $this->data[] = $value;
        end($this->data);
        $pos = key($this->data);

        $hashCode = $this->getHashCode($value);
        $this->index[$hashCode] = $pos;
        $this->indexSplHash[spl_object_hash($value)] = $hashCode;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!is_object($value)) {
            parent::offsetSet($offset, $value);

            return;
        }

        $hashCode = $this->getHashCode($value);

        if ($offset === null) {
            $this->data[] = $value;
            end($this->data);
            $pos = key($this->data);

            $this->index[$hashCode] = $pos;
            $this->indexSplHash[spl_object_hash($value)] = $hashCode;
        } else {
            if (isset($this->data[$offset])) {
                unset($this->indexSplHash[spl_object_hash($this->data[$offset])]);
                unset($this->index[$this->getHashCode($this->data[$offset])]);
            }

            $this->index[$hashCode] = $offset;
            $this->indexSplHash[spl_object_hash($value)] = $hashCode;
            $this->data[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function contains($element)
    {
        if (!is_object($element)) {
            return parent::contains($element);
        }

        return isset($this->indexSplHash[spl_object_hash($element)]) || isset($this->index[$this->getHashCode($element)]);
    }

    /**
     * Returns the result of $object->hashCode() if available or uses spl_object_hash($object).
     *
     * @param mixed $object
     *
     * @return string
     */
    protected function getHashCode($object)
    {
        if (is_object($object) && is_callable([$object, 'hashCode'])) {
            return $object->hashCode();
        }

        return spl_object_hash($object);
    }
}
