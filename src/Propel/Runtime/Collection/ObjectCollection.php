<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Collection;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Propel\Runtime\Collection\Exception\ReadOnlyModelException;
use Propel\Runtime\Collection\Exception\UnsupportedRelationException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\EntityMap;
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
     * Save all the elements in the collection.
     *
     * Only works with ActiveRecord activated.
     */
    public function save($con = null)
    {
        foreach ($this as $element) {
            $element->save($con);
        }
    }

    /**
     * Delete all the elements in the collection
     *
     * Only works with ActiveRecord activated.
     */
    public function delete($con = null)
    {
        foreach ($this as $element) {
            $element->delete($con);
        }
    }

    /**
     * Get an array of the primary keys of all the objects in the collection
     *
     * Only works with ActiveRecord activated.
     *
     * @param  boolean $usePrefix
     *
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
     * Only works with ActiveRecord activated.
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
     * Only works with ActiveRecord activated.
     *
     * @param string  $keyField              If null, the returned array uses an incremental index.
     *                                        Otherwise, the array is indexed using the specified field
     * @param boolean $usePrefix              If true, the returned array prefixes keys
     *                                        with the model class name ('Article_0', 'Article_1', etc).
     * @param string  $keyType                (optional) One of the class type constants EntityMap::TYPE_FIELDNAME,
     *                                        EntityMap::TYPE_COLNAME, EntityMap::TYPE_FULLCOLNAME, EntityMap::TYPE_FIELDNAME,
     *                                        EntityMap::TYPE_NUM. Defaults to EntityMap::TYPE_FIELDNAME.
     * @param boolean $includeLazyLoadFields (optional) Whether to include lazy loaded fields. Defaults to TRUE.
     * @param object  $alreadyDumpedObjectsWatcher Internal struct to detect recursion.
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
    public function toArray($keyField = null, $usePrefix = false, $keyType = EntityMap::TYPE_FIELDNAME, $includeLazyLoadFields = true, $alreadyDumpedObjectsWatcher = null)
    {
        $ret = array();
        $keyGetterMethod = 'get' . $keyField;

        /** @var $obj ActiveRecordInterface */
        foreach ($this->data as $key => $obj) {
            $key = null === $keyField ? $key : $obj->$keyGetterMethod();
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj->toArray($keyType, $includeLazyLoadFields, true, $alreadyDumpedObjectsWatcher);
        }

        return $ret;
    }

    /**
     * Get an array representation of the collection
     *
     * @param string  $keyField If null, the returned array uses an incremental index.
     *                           Otherwise, the array is indexed using the specified field
     * @param boolean $usePrefix If true, the returned array prefixes keys
     *                           with the model class name ('Article_0', 'Article_1', etc).
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
    public function getArrayCopy($keyField = null, $usePrefix = false)
    {
        if (null === $keyField && false === $usePrefix) {
            return parent::getArrayCopy();
        }
        $ret = array();
        $keyGetterMethod = 'get' . $keyField;
        foreach ($this as $key => $obj) {
            $key = null === $keyField ? $key : $obj->$keyGetterMethod();
            $key = $usePrefix ? ($this->getModel() . '_' . $key) : $key;
            $ret[$key] = $obj;
        }

        return $ret;
    }

    /**
     * Get an associative array representation of the collection
     * The first parameter specifies the field to be used for the key,
     * And the second for the value.
     *
     * <code>
     *   $res = $coll->toKeyValue('Id', 'Name');
     * </code>
     *
     * @param string $keyField
     * @param string $valueField
     *
     * @return array
     */
    public function toKeyValue($keyField = 'PrimaryKey', $valueField = null)
    {
        $ret = array();
        $keyGetterMethod = 'get' . $keyField;
        $valueGetterMethod = (null === $valueField) ? '__toString' : ('get' . $valueField);
        foreach ($this as $obj) {
            $ret[$obj->$keyGetterMethod()] = $obj->$valueGetterMethod();
        }

        return $ret;
    }

    /**
     * Get an associative array representation of the collection.
     * The first parameter specifies the field to be used for the key.
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
     * @param string $keyField
     *
     * @return array
     */
    public function toKeyIndex($keyField = 'PrimaryKey')
    {
        $ret = array();
        $keyGetterMethod = 'get' . ucfirst($keyField);
        foreach ($this as $obj) {
            $ret[$obj->$keyGetterMethod()] = $obj;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function search($element)
    {
        $hashCode = $this->getHashCode($element);
        foreach ($this as $pos => $obj) {
            if ($hashCode === $this->getHashCode($obj)) {
                return $pos;
            }
        }

        return false;
    }

    /**
     * @param $instance
     *
     * @return mixed
     */
    protected function getHashCode($instance)
    {
        if (method_exists($instance, 'hashCode')) {
            return $instance->hashCode();
        }

        return spl_object_hash($instance);
    }

    /**
     * @param $element
     */
    public function removeObject($element)
    {
        if (false !== ($pos = $this->search($element))) {
            $this->remove($pos);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        return false !== $this->search($element);
    }
}
