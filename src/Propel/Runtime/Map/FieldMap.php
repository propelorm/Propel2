<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Map;

use Propel\Generator\Model\NamingTool;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Map\Exception\ForeignKeyNotFoundException;
use Propel\Generator\Model\PropelTypes;

/**
 * FieldMap is used to model a field of a entity in a database.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 */
class FieldMap
{
    /**
     * Propel type of the field
     */
    protected $type;

    /**
     * Size of the field
     */
    protected $size = 0;

    /**
     * Is it a primary key?
     *
     * @var boolean
     */
    protected $pk = false;

    /**
     * Is null value allowed?
     *
     * @var boolean
     */
    protected $notNull = false;

    /**
     * The default value for this field
     */
    protected $defaultValue;

    /**
     * Name of the entity that this field is related to
     */
    protected $relatedEntityName = '';

    /**
     * Name of the field that this field is related to
     */
    protected $relatedFieldName = '';

    /**
     * @var bool
     */
    protected $lazyLoad = false;

    /**
     * The EntityMap for this field
     */
    protected $entity;

    /**
     * The name of the field
     */
    protected $fieldName;

    /**
     * The column name of the field
     */
    protected $columnName;

    /**
     * The allowed values for an ENUM field
     *
     * @var array
     */
    protected $valueSet = array();

    /**
     * @var bool
     */
    protected $implementationDetail = false;

    /**
     * Is this a primaryString field?
     *
     * @var boolean
     */
    protected $isPkString = false;

    protected $autoIncrement = false;

    /**
     * Constructor.
     *
     * @param string                             $name            The name of the field.
     * @param      \Propel\Runtime\Map\EntityMap containingEntity EntityMap of the entity this field is in.
     */
    public function __construct($name, EntityMap $containingEntity)
    {
        $this->fieldName = $name;
        $this->entity = $containingEntity;
    }

    /**
     * @return boolean
     */
    public function isAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param boolean $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * Get the name of a field.
     *
     * @return string A String with the field name.
     */
    public function getName()
    {
        return $this->fieldName;
    }

    /**
     * @param mixed $
     */
    public function setName($name)
    {
        $this->fieldName = $name;
    }

    /**
     * @return mixed
     */
    public function getColumnName()
    {
        if (!$this->columnName) {
            return NamingTool::toUnderscore($this->getName());
        }

        return $this->columnName;
    }

    /**
     * @param mixed $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @return boolean
     */
    public function isLazyLoad()
    {
        return $this->lazyLoad;
    }

    /**
     * @param boolean $lazyLoad
     */
    public function setLazyLoad($lazyLoad)
    {
        $this->lazyLoad = $lazyLoad;
    }

    /**
     * @return boolean
     */
    public function isImplementationDetail()
    {
        return $this->implementationDetail;
    }

    /**
     * @param boolean $implementationDetail
     */
    public function setImplementationDetail($implementationDetail)
    {
        $this->implementationDetail = $implementationDetail;
    }

    /**
     * Get the entity map this field belongs to.
     *
     * @return \Propel\Runtime\Map\EntityMap
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get the name of the entity this field is in.
     *
     * @return string A String with the entity name.
     */
    public function getEntityName()
    {
        return $this->entity->getName();
    }

    /**
     * Get the entity name + field name.
     *
     * @return string A String with the full field name.
     */
    public function getFullyQualifiedName()
    {
        return $this->getEntity()->getFullClassName() . '.' . $this->getName();
    }

    /**
     * @return string
     */
    public function getFullyQualifierColumnName()
    {
        return $this->getEntity()->getFQTableName() . '.' . $this->getColumnName();
    }

    /**
     * Set the Propel type of this field.
     *
     * @param string $type A string representing the Propel type (e.g. PropelTypes::DATE).
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the Propel type of this field.
     *
     * @return string A string representing the Propel type (e.g. PropelTypes::DATE).
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the PDO type of this field.
     *
     * @return int The PDO::PARAM_* value
     */
    public function getPdoType()
    {
        return PropelTypes::getPdoType($this->type);
    }

    /**
     * Whether this is a BLOB, LONGVARBINARY, or VARBINARY.
     *
     * @return boolean
     */
    public function isLob()
    {
        return in_array(
            $this->type,
            array(
                PropelTypes::BLOB,
                PropelTypes::VARBINARY,
                PropelTypes::LONGVARBINARY,
            )
        );
    }

    /**
     * Whether this is a DATE/TIME/TIMESTAMP field.
     *
     * @return boolean
     */
    public function isTemporal()
    {
        return in_array(
            $this->type,
            array(
                PropelTypes::TIMESTAMP,
                PropelTypes::DATE,
                PropelTypes::TIME,
                PropelTypes::BU_DATE,
                PropelTypes::BU_TIMESTAMP,
            )
        );
    }

    /**
     * Whether this field is numeric (int, decimal, bigint etc).
     *
     * @return boolean
     */
    public function isNumeric()
    {
        return in_array(
            $this->type,
            array(
                PropelTypes::NUMERIC,
                PropelTypes::DECIMAL,
                PropelTypes::TINYINT,
                PropelTypes::SMALLINT,
                PropelTypes::INTEGER,
                PropelTypes::BIGINT,
                PropelTypes::REAL,
                PropelTypes::FLOAT,
                PropelTypes::DOUBLE,
            )
        );
    }

    /**
     * Whether this field is a text field (varchar, char, longvarchar).
     *
     * @return boolean
     */
    public function isText()
    {
        return in_array(
            $this->type,
            array(
                PropelTypes::VARCHAR,
                PropelTypes::LONGVARCHAR,
                PropelTypes::CHAR,
            )
        );
    }

    /**
     * Set the size of this field.
     *
     * @param int $size An int specifying the size.
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Get the size of this field.
     *
     * @return int An int specifying the size.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set if this field is a primary key or not.
     *
     * @param boolean $pk True if field is a primary key.
     */
    public function setPrimaryKey($pk)
    {
        $this->pk = (Boolean)$pk;
    }

    /**
     * Is this field a primary key?
     *
     * @return boolean True if field is a primary key.
     */
    public function isPrimaryKey()
    {
        return $this->pk;
    }

    /**
     * Set if this field may be null.
     *
     * @param boolean $nn True if field may be null.
     */
    public function setNotNull($nn)
    {
        $this->notNull = (Boolean)$nn;
    }

    /**
     * Is null value allowed ?
     *
     * @return boolean True if field may not be null.
     */
    public function isNotNull()
    {
        return $this->notNull || $this->isPrimaryKey();
    }

    /**
     * Sets the default value for this field.
     *
     * @param mixed $defaultValue the default value for the field
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets the default value for this field.
     *
     * @return mixed String or NULL
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set the foreign key for this field.
     *
     * @param string $entityName The name of the entity that is foreign.
     * @param string $fieldName  The name of the field that is foreign.
     */
    public function setForeignKey($entityName, $fieldName)
    {
        if ($entityName && $fieldName) {
            $this->relatedEntityName = $entityName;
            $this->relatedFieldName = $fieldName;
        } else {
            // @TODO to remove because it seems already done by default!
            $this->relatedEntityName = '';
            $this->relatedFieldName = '';
        }
    }

    /**
     * Is this field a foreign key?
     *
     * @return boolean True if field is a foreign key.
     */
    public function isForeignKey()
    {
        return !empty($this->relatedEntityName);
    }

    /**
     * Get the RelationMap object for this foreign key
     */
    public function getRelation()
    {
        if (!$this->relatedEntityName) {
            return null;
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            if (RelationMap::MANY_TO_ONE === $relation->getType()) {
                if ($relation->getForeignEntity()->getName() === $this->getRelatedEntityName()
                    && array_key_exists($this->getFullyQualifiedName(), $relation->getFieldMappings())
                ) {
                    return $relation;
                }
            }
        }
    }

    /**
     * Get the entity.field that this field is related to.
     *
     * @return string A String with the full name for the related field.
     */
    public function getRelatedName()
    {
        return $this->relatedEntityName . '.' . $this->relatedFieldName;
    }

    /**
     * Get the entity name that this field is related to.
     *
     * @return string A String with the name for the related entity.
     */
    public function getRelatedEntityName()
    {
        return $this->relatedEntityName;
    }

    /**
     * Get the field name that this field is related to.
     *
     * @return string A String with the name for the related field.
     */
    public function getRelatedFieldName()
    {
        return $this->relatedFieldName;
    }

    /**
     * Get the EntityMap object that this field is related to.
     *
     * @return \Propel\Runtime\Map\EntityMap                              The related EntityMap object
     * @throws \Propel\Runtime\Map\Exception\ForeignKeyNotFoundException when called on a field with no foreign key
     */
    public function getRelatedEntity()
    {
        if (!$this->relatedEntityName) {
            throw new ForeignKeyNotFoundException(
                sprintf('Cannot fetch RelatedEntity for field with no foreign key: %s.', $this->fieldName)
            );
        }

        return $this->entity->getDatabaseMap()->getEntity($this->relatedEntityName);
    }

    /**
     * Get the EntityMap object that this field is related to.
     *
     * @return \Propel\Runtime\Map\FieldMap                             The related FieldMap object
     * @throws \Propel\Runtime\Map\Exception\ForeignKeyNotFoundException when called on a field with no foreign key
     */
    public function getRelatedField()
    {
        return $this->getRelatedEntity()->getField($this->relatedFieldName);
    }

    /**
     * Set the valueSet of this field (only valid for ENUM fields).
     *
     * @param array $values A list of allowed values
     */
    public function setValueSet($values)
    {
        $this->valueSet = $values;
    }

    /**
     * Get the valueSet of this field (only valid for ENUM fields).
     *
     * @return array A list of allowed values
     */
    public function getValueSet()
    {
        return $this->valueSet;
    }

    public function isInValueSet($value)
    {
        return in_array($value, $this->valueSet);
    }

    public function getValueSetKey($value)
    {
        return array_search($value, $this->valueSet);
    }

    /**
     * Performs DB-specific ignore case, but only if the field type necessitates it.
     *
     * @param string           $str The expression we want to apply the ignore case formatting to (e.g. the field name).
     * @param AdapterInterface $db
     *
     * @return string
     */
    public function ignoreCase($str, AdapterInterface $db)
    {
        if ($this->isText()) {
            return $db->ignoreCase($str);
        }

        return $str;
    }

    /**
     * Normalizes the field name, removing entity prefix.
     *
     * article.first_name becomes first_name
     *
     * @param  string $name
     *
     * @return string Normalized field name.
     */
    public static function normalizeName($name)
    {
        if (false !== ($pos = strrpos($name, '.'))) {
            $name = substr($name, $pos + 1);
        }

        return $name;
    }

    /**
     * Set this field to be a primaryString field.
     *
     * @param boolean $pkString
     */
    public function setPrimaryString($pkString)
    {
        $this->isPkString = (Boolean)$pkString;
    }

    /**
     * Is this field a primaryString field?
     *
     * @return boolean True, if this field is the primaryString field.
     */
    public function isPrimaryString()
    {
        return $this->isPkString;
    }
}
