<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Collection\Exception\UnsupportedRelationException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\ActiveQuery\PropelQuery;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

/**
 * Class for iterating over a list of Propel objects
 *
 * @author Francois Zaninotto
 */
class ObjectCollection extends Collection
{
    /**
     * Save all the elements in the collection
     *
     * @param ConnectionInterface $con
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
            /** @var $element ActiveRecordInterface */
            foreach ($this as $element) {
                $element->save($con);
            }
            $con->commit();
        } catch (PropelException $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Delete all the elements in the collection
     *
     * @param ConnectionInterface $con
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
            /** @var $element ActiveRecordInterface */
            foreach ($this as $element) {
                $element->delete($con);
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
        $ret = array();

        /** @var $obj ActiveRecordInterface */
        foreach ($this as $key => $obj) {
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key]= $obj->getPrimaryKey();
        }

        return $ret;
    }

    /**
     * Populates the collection from an array
     * Each object is populated from an array and the result is stored
     * Does not empty the collection before adding the data from the array
     *
     * @param array $arr
     */
    public function fromArray($arr)
    {
        $class = $this->getFullyQualifiedModel();
        foreach ($arr as $element) {
            /** @var $obj ActiveRecordInterface */
            $obj = new $class();
            $obj->fromArray($element);
            $this->append($obj);
        }
    }

    /**
     * Get an array representation of the collection
     * Each object is turned into an array and the result is returned
     *
     * @param string $keyColumn If null, the returned array uses an incremental index.
     *                               Otherwise, the array is indexed using the specified column
     * @param boolean $usePrefix If true, the returned array prefixes keys
     *                               with the model class name ('Article_0', 'Article_1', etc).
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME,
     *                               TableMap::TYPE_STUDLYPHPNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME,
     *                               TableMap::TYPE_NUM. Defaults to TableMap::TYPE_PHPNAME.
     * @param boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param array   $alreadyDumpedObjects   List of objects to skip to avoid recursion
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
    public function toArray($keyColumn = null, $usePrefix = false, $keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array())
    {
        $ret = array();
        $keyGetterMethod = 'get' . $keyColumn;

        /** @var $obj ActiveRecordInterface */
        foreach ($this->data as $key => $obj) {
            $key = null === $keyColumn ? $key : $obj->$keyGetterMethod();
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj->toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, true);
        }

        return $ret;
    }

    /**
     * Get an array representation of the collection
     *
     * @param string $keyColumn If null, the returned array uses an incremental index.
     *                                 Otherwise, the array is indexed using the specified column
     * @param boolean $usePrefix If true, the returned array prefixes keys
     *                                 with the model class name ('Article_0', 'Article_1', etc).
     *
     * <code>
     *   $bookCollection->getArrayCopy();
     *   array(
     *    0 => $book0,
     *    1 => $book1,
     *   )
     *   $bookCollection->getArrayCopy('Id');
     *   array(
     *    123 => $book0,
     *    456 => $book1,
     *   )
     *   $bookCollection->getArrayCopy(null, true);
     *   array(
     *    'Book_0' => $book0,
     *    'Book_1' => $book1,
     *   )
     * </code>
     *
     * @return array
     */
    public function getArrayCopy($keyColumn = null, $usePrefix = false)
    {
        if (null === $keyColumn && false === $usePrefix) {
            return parent::getArrayCopy();
        }
        $ret = array();
        $keyGetterMethod = 'get' . $keyColumn;
        foreach ($this as $key => $obj) {
            $key = null === $keyColumn ? $key : $obj->$keyGetterMethod();
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
     * @param string $valueColumn
     *
     * @return array
     */
    public function toKeyValue($keyColumn = 'PrimaryKey', $valueColumn = null)
    {
        $ret = array();
        $keyGetterMethod = 'get' . $keyColumn;
        $valueGetterMethod = (null === $valueColumn) ? '__toString' : ('get' . $valueColumn);
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
        $ret = array();
        $keyGetterMethod = 'get' . ucfirst($keyColumn);
        foreach ($this as $obj) {
            $ret[$obj->$keyGetterMethod()] = $obj;
        }

        return $ret;
    }

    /**
     * Makes an additional query to populate the objects related to the collection objects
     * by a certain relation
     *
     * @param string              $relation Relation name (e.g. 'Book')
     * @param Criteria            $criteria Optional Criteria object to filter the related object collection
     * @param ConnectionInterface $con      Optional connection object
     *
     * @return ObjectCollection The list of related objects
     */
    public function populateRelation($relation, $criteria = null, $con = null)
    {
        if (!Propel::isInstancePoolingEnabled()) {
            throw new RuntimeException(__METHOD__ .' needs instance pooling to be enabled prior to populating the collection');
        }
        $relationMap = $this->getFormatter()->getTableMap()->getRelation($relation);
        if ($this->isEmpty()) {
            // save a useless query and return an empty collection
            $coll = new ObjectCollection();
            $coll->setModel($relationMap->getRightTable()->getClassName());

            return $coll;
        }
        $symRelationMap = $relationMap->getSymmetricalRelation();

        $query = PropelQuery::from($relationMap->getRightTable()->getClassName());
        if (null !== $criteria) {
            $query->mergeWith($criteria);
        }
        // query the db for the related objects
        $filterMethod = 'filterBy' . $symRelationMap->getName();
        $relatedObjects = $query
            ->$filterMethod($this)
            ->find($con)
        ;

        if (RelationMap::ONE_TO_MANY === $relationMap->getType()) {
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
        } elseif (RelationMap::MANY_TO_ONE === $relationMap->getType()) {
            // nothing to do; the instance pool will catch all calls to getRelatedObject()
            // and return the object in memory
        } else {
            throw new UnsupportedRelationException(__METHOD__ .' does not support this relation type');
        }

        return $relatedObjects;
    }

    /**
     * {@inheritdoc}
     */
    public function search($element)
    {
        if ($element instanceof ActiveRecordInterface) {
            if (null !== $elt = $this->getIdenticalObject($element)) {
                $element = $elt;
            }
        }

        return parent::search($element);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        if ($element instanceof ActiveRecordInterface) {
            if (null !== $elt = $this->getIdenticalObject($element)) {
                $element = $elt;
            }
        }

        return parent::contains($element);
    }

    private function getIdenticalObject(ActiveRecordInterface $object)
    {
        foreach ($this as $obj) {
            if ($obj instanceof ActiveRecordInterface && $obj->hashCode() === $object->hashCode()) {
                return $obj;
            }
        }

        return null;
    }
}
