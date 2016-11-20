<?php

namespace Propel\Runtime;


use Propel\Runtime\Map\EntityMap;

trait ActiveRecordTrait
{
    /**
     * @return bool
     */
    public function isNew()
    {
        return Configuration::getCurrentConfiguration()->getSession()->isNew($this);
    }

    /**
     * @return Repository\Repository
     */
    public function getRepository()
    {
        return Configuration::getCurrentConfiguration()->getRepositoryForEntity($this);
    }

    public function getPropelConfiguration()
    {
        return Configuration::getCurrentConfiguration();
    }

    /**
     * @return mixed
     */
    public function save()
    {
        Configuration::getCurrentConfiguration()->getRepositoryForEntity($this)->save($this);
    }

    /**
     * @return mixed
     */
    public function reload()
    {
        Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->reload($this);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        Configuration::getCurrentConfiguration()->getRepositoryForEntity($this)->remove($this);
    }

    public function getPrimaryKey()
    {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->getPrimaryKey($this);
    }

    /**
     * Populates the object using an array.
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the field
     * names and sets all values through its setter or directly into the property.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME,
     * EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
     * The default key type is the column's EntityMap::TYPE_FIELDNAME.
     *
     * @param array $arr
     * @param string $keyType The type of fieldname the $name is of:
     * one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
     * EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
     * Defaults to EntityMap::TYPE_FIELDNAME.
     */
    public function fromArray(array $arr, $keyType = EntityMap::TYPE_FIELDNAME) {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->fromArray($arr, $keyType, $this);
    }

    /**
     * Retrieves a field from the object by name passed in as a string
     *
     * @param string $name name of the field
     * @param string $type The type of fieldname the $name is of:
     * one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
     * EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
     * Defaults to EntityMap::TYPE_FIELDNAME.
     * @return $this
     */
    public function getByName($name, $type = EntityMap::TYPE_FIELDNAME) {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->getByName($this, $name, $type);
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema. Zero-based
     *
     * @param integer $pos position in xml schema
     * @return $this
     */
    public function getByPosition($pos) {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->getByPosition($this, $pos);
    }


    /**
     * Sets a field from the object by Position as specified in the xml schema. Zero-based
     *
     * @param string $name name of the field
     * @param mixed $value field value
     * @param string $type The type of fieldname the $name is of:
     * one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
     * EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
     * Defaults to EntityMap::TYPE_FIELDNAME.
     * @return $this
     */
    public function setByName($name, $value, $type = EntityMap::TYPE_FIELDNAME) {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->setByName($this, $name, $value, $type);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema. Zero-based
     *
     * @param integer $pos position in xml schema
     * @param mixed $value field value
     * @return $this
     */
    public function setByPosition($pos, $value) {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->setByPosition($this, $pos, $value);
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants. The default key type is the column's EntityMap::TYPE_FIELDNAME.
     *
     * @param string $keyType The type of fieldname the $name is of:
     * one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
     * EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
     * Defaults to EntityMap::TYPE_FIELDNAME.
     * @param boolean $includeLazyLoadColumns Whether to include lazy loaded columns
     * @param boolean $includeForeignObjects Whether to include hydrated related objects
     * @param object $alreadyDumpedObjectsWatcher
     *
     * @return array
     */
    public function toArray($keyType = EntityMap::TYPE_FIELDNAME, $includeLazyLoadColumns = true, $includeForeignObjects = false, $alreadyDumpedObjectsWatcher = null) {
        return Configuration::getCurrentConfiguration()->getEntityMapForEntity($this)->toArray($this, $keyType, $includeLazyLoadColumns, $includeForeignObjects, $alreadyDumpedObjectsWatcher);
    }

    /**
     * Returns all properties on the current object that were dynamically added. Also called virtual column. (Query class using withColumn)
     *
     * @return array
     */
    public function getVirtualFields()
    {
        $reflection = new \ReflectionClass($this);

        $result = [];
        foreach (get_object_vars($this) as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getVirtualField($name)
    {
        if (!$this->hasVirtualField($name)) {
            throw new \InvalidArgumentException("Virtual field $name does not exist.");
        }

        return $this->getVirtualFields()[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setVirtualColumn($name, $value)
    {
        $this->$name = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasVirtualField($name)
    {
        $fields = $this->getVirtualFields();
        return array_key_exists($name, $fields);
    }
}