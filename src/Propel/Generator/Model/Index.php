<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * Information about indices of a entity.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Index extends MappingModel
{
    /**
     * The Entity instance.
     *
     * @var Entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $fields;

    /**
     * @var Field[]
     */
    protected $fieldObjects = [];

    /**
     * @var string[]
     */
    protected $fieldsSize;

    /**
     * @var bool
     */
    protected $autoNaming = false;

    /**
     * Creates a new Index instance.
     *
     * @param string $name Name of the index
     */
    public function __construct($name = null)
    {
        parent::__construct();

        $this->fields     = [];
        $this->fieldsSize = [];

        if (null !== $name) {
            $this->setName($name);
        }
    }

    /**
     * Returns the uniqueness of this index.
     *
     * @return boolean
     */
    public function isUnique()
    {
        return false;
    }

    /**
     * Get the name of the Index object. If none given, it uses the autonaming and the name.
     *
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            $this->setName($this->getSqlName());
        }

        return parent::getName();
    }

    public function setName($value)
    {
        if (!$value) {
            $value = $this->getSqlName();
        }

        parent::setName($value);
    }

    /**
     * Sets the index name.
     *
     * @param string $name
     */
    public function setSqlName($name)
    {
        if (!$name) {
            if ($this->sqlName) {
                return;
            }
            if (!$this->name) {
                $this->autoNaming = true;
            } else {
                $name = NamingTool::toUnderscore($this->name);
            }
        }

        $this->sqlName = $name;
    }

    /**
     * Returns the index name.
     *
     * @param bool $fq If return fully qualified name
     * @return string
     */
    public function getSqlName($fq = false)
    {
        $this->doNaming();
        $sqlName = '';
        $entity = $this->getEntity();

        if ($entity && $database = $entity->getDatabase()) {
            $sqlName = substr($this->sqlName, 0, $database->getMaxFieldNameLength());

            if ($fq && ($entity->getSchema() || $entity->getDatabase()->getSchema())
                && $entity->getDatabase()->getPlatform()
                && $entity->getDatabase()->getPlatform()->supportsSchemas()
            ) {
                return ($entity->getSchema() ?: $entity->getDatabase()->getSchema()) . '.' . $sqlName;
            }

            return $sqlName;
        }

        return $this->sqlName;
    }

    protected function doNaming()
    {
        if (!$this->sqlName) {
            $this->sqlName = NamingTool::toUnderscore($this->name);
        }

        if (!$this->sqlName || $this->autoNaming) {
            $newName = sprintf('%s_', $this instanceof Unique ? 'u' : 'i');

            if ($this->fields) {
                $hash = [];
                $hash[] = implode(',', (array)$this->fields);
                $hash[] = implode(',', (array)$this->fieldsSize);

                $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);
            } else {
                $newName .= 'no_fields';
            }

            if ($this->entity) {
                $newName = $this->entity->getSqlName(false) . '_' . $newName;
            }

            $this->sqlName = $newName;
            $this->autoNaming = true;
        }
    }

    /**
     * Sets the index parent Entity.
     *
     * @param Entity $entity
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Returns the index parent entity.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns the name of the index parent entity.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entity->getName();
    }

    /**
     * Adds a new field to the index.
     *
     * @param Field|array $data Field or attributes from XML.
     */
    public function addField($data)
    {
        if ($data instanceof Field) {
            $field = $data;
            $this->fields[] = $field->getName();
            if ($field->getSize()) {
                $this->fieldsSize[$field->getName()] = $field->getSize();
            }
            $this->fieldObjects[] = $field;
        } else {
            $this->fields[] = $name = $data['name'];
            if (isset($data['size']) && $data['size'] > 0) {
                $this->fieldsSize[$name] = $data['size'];
            }
            if ($this->getEntity()) {
                $this->fieldObjects[] = $this->getEntity()->getField($name);
            }
        }
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasField($name)
    {
        return in_array($name, $this->fields);
    }

    /**
     * Sets an array of fields to use for the index.
     *
     * @param array $fields array of array definitions $fields[]['name'] = 'fieldName'
     */
    public function setFields(array $fields)
    {
        $this->fields     = [];
        $this->fieldsSize = [];
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * Returns whether or not there is a size for the specified field.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasFieldSize($name)
    {
        return isset($this->fieldsSize[$name]);
    }

    /**
     * Returns the size for the specified field.
     *
     * @param  string  $name
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function getFieldSize($name, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            foreach ($this->fieldsSize as $forName => $size) {
                if (0 === strcasecmp($forName, $name)) {
                    return $size;
                }
            }
            return null;
        }
        return isset($this->fieldsSize[$name]) ? $this->fieldsSize[$name] : null;
    }

    /**
     * Resets the fields sizes.
     *
     * This method is useful for generated indices for FKs.
     */
    public function resetFieldsSize()
    {
        $this->fieldsSize = [];
    }

    /**
     * Returns whether or not this index has a given field at a given position.
     *
     * @param  integer $pos             Position in the field list
     * @param  string  $name            Field name
     * @param  integer $size            Optional size check
     * @param  boolean $caseInsensitive Whether or not the comparison is case insensitive (false by default)
     * @return boolean
     */
    public function hasFieldAtPosition($pos, $name, $size = null, $caseInsensitive = false)
    {
        if (!isset($this->fields[$pos])) {
            return false;
        }

        if ($caseInsensitive) {
            $test = 0 === strcasecmp($this->fields[$pos], $name);
        } else {
            $test = $this->fields[$pos] == $name;
        }

        if (!$test) {
            return false;
        }

        if ($this->getFieldSize($name, $caseInsensitive) != $size) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether or not the index has fields.
     *
     * @return boolean
     */
    public function hasFields()
    {
        return count($this->fields) > 0;
    }

    /**
     * Returns the list of local fields.
     *
     * You should not edit this list.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    protected function setupObject()
    {
        $this->setName($this->getAttribute('name'));
        $this->setSqlName($this->getAttribute('sqlName'));
    }

    /**
     * @return Field[]
     */
    public function getFieldObjects()
    {
        return $this->fieldObjects;
    }

    /**
     * @param Field[] $fieldObjects
     */
    public function setFieldObjects($fieldObjects)
    {
        $this->fieldObjects = $fieldObjects;
    }
}
