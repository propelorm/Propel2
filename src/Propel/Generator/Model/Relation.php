<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Platform\PlatformInterface;

/**
 * A class for information about table foreign keys.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Fedor <fedor.karpelevitch@home.com>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Ulf Hermann <ulfhermann@kulturserver.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Relation extends MappingModel
{
    /**
     * These constants are the uppercase equivalents of the onDelete / onUpdate
     * values in the schema definition.
     *
     */
    const NONE = '';           // No 'ON [ DELETE | UPDATE]' behavior
    const NOACTION = 'NO ACTION';
    const CASCADE = 'CASCADE';
    const RESTRICT = 'RESTRICT';
    const SETDEFAULT = 'SET DEFAULT';
    const SETNULL = 'SET NULL';

    /**
     * @var string
     */
    private $foreignEntityName;

    /**
     * If foreignEntityName is not given getForeignEntity() uses this entity directly.
     *
     * @var Entity|null
     */
    private $foreignEntity;
//
//    /**
//     * @var string
//     */
//    private $foreignSchemaName;

    /**
     * @var string
     */
    private $name;

    private $field;
    private $refField;

    /**
     * @var string
     */
    private $refName;

    /**
     * @var string
     */
    private $defaultJoin;

    /**
     * @var string
     */
    private $onUpdate = '';

    /**
     * @var string
     */
    private $onDelete = '';

    /**
     * @var Entity
     */
    private $parentEntity;

    /**
     * @var string[]
     */
    private $localFields;

    /**
     * @var string[]
     */
    private $foreignFields;

    /**
     * @var bool
     */
    private $skipSql;

    /**
     * @var bool
     */
    private $skipCodeGeneration = false;

    /**
     * @var bool
     */
    private $autoNaming = false;

    /**
     * Constructs a new Relation object.
     *
     * @param string $name
     */
    public function __construct($name = null)
    {
        parent::__construct();

        if (null !== $name) {
            $this->setName($name);
        }

        $this->onUpdate = self::NONE;
        $this->onDelete = self::NONE;
        $this->localFields = [];
        $this->foreignFields = [];
        $this->skipSql = false;
    }

    protected function setupObject()
    {
        $this->foreignEntityName = $this->getAttribute('target') ?: $this->getAttribute('target');

//        $this->foreignSchemaName = $this->getAttribute('targetSchema');

        $this->name = $this->getAttribute('name');
        $this->field = $this->getAttribute('field');

        if (!$this->field) {
            $this->field = $this->name;
        }

        if (!$this->field) {
            $this->field = lcfirst($this->getAttribute('target'));
        }

        if (!$this->field) {
            throw new \InvalidArgumentException('field or target value empty for relation');
        }

        $this->refName = $this->getAttribute('refName') ?: lcfirst($this->getEntity()->getName());
        $this->refField = $this->getAttribute('refField') ?: ($this->refName ?: $this->getAttribute('target'));

        $this->defaultJoin = $this->getAttribute('defaultJoin');
        $this->onUpdate = $this->normalizeFKey($this->getAttribute('onUpdate'));
        $this->onDelete = $this->normalizeFKey($this->getAttribute('onDelete'));
        $this->skipSql = $this->booleanValue($this->getAttribute('skipSql'));
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getRefField()
    {
        return $this->refField;
    }

    /**
     * @param string $refField
     */
    public function setRefField($refField)
    {
        $this->refField = $refField;
    }
    /**
     * Returns the normalized input of onDelete and onUpdate behaviors.
     *
     * @param  string $behavior
     *
     * @return string
     */
    public function normalizeFKey($behavior)
    {
        if (null === $behavior) {
            return self::NONE;
        }

        $behavior = strtoupper($behavior);

        if ('NONE' === $behavior) {
            return self::NONE;
        }

        if ('SETNULL' === $behavior) {
            return self::SETNULL;
        }

        return $behavior;
    }

    /**
     * Returns whether or not the onUpdate behavior is set.
     *
     * @return boolean
     */
    public function hasOnUpdate()
    {
        return self::NONE !== $this->onUpdate;
    }

    /**
     * Returns whether or not the onDelete behavior is set.
     *
     * @return boolean
     */
    public function hasOnDelete()
    {
        return self::NONE !== $this->onDelete;
    }

    /**
     * @return boolean
     */
    public function isSkipCodeGeneration()
    {
        return $this->skipCodeGeneration;
    }

    /**
     * @param boolean $skipCodeGeneration
     */
    public function setSkipCodeGeneration($skipCodeGeneration)
    {
        $this->skipCodeGeneration = $skipCodeGeneration;
    }

    /**
     * Returns true if $field is in our local fields list.
     *
     * @param  Field $field
     *
     * @return boolean
     */
    public function hasLocalField(Field $field)
    {
        return in_array($field, $this->getLocalFieldObjects(), true);
    }

    /**
     * Returns the onUpdate behavior.
     *
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    /**
     * Returns the onDelete behavior.
     *
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * Sets the onDelete behavior.
     *
     * @param string $behavior
     */
    public function setOnDelete($behavior)
    {
        $this->onDelete = $this->normalizeFKey($behavior);
    }

    /**
     * Sets the onUpdate behavior.
     *
     * @param string $behavior
     */
    public function setOnUpdate($behavior)
    {
        $this->onUpdate = $this->normalizeFKey($behavior);
    }

    /**
     * Returns the foreign key name.
     *
     * @return string
     */
    public function getName()
    {
        $this->doNaming();
        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasName()
    {
        return !!$this->name && !$this->autoNaming;
    }

    /**
     * Sets the foreign key name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->autoNaming = !$name; //if no name we activate autoNaming
        $this->name = $name;
    }

    protected function doNaming()
    {
        if (!$this->name || $this->autoNaming) {
            $newName = 'fk_';

            $hash = [];
            if ($this->getForeignEntity()) {
                $hash[] = $this->getForeignEntity()->getFQTableName();
            }

            if (!$this->localFields) {
                throw new EngineException('Could not generate a relation name for relation without references.');
            }

            if ($this->localFields) {
                $columnNames = array_map(function (Field $field) {
                    return $field->getColumnName();
                }, $this->getLocalFieldObjects());
                $hash[] = implode(',', $columnNames);

                $columnNames = array_map(function(Field $field) {
                    return $field->getColumnName();
                }, $this->getForeignFieldObjects());
                $hash[] = implode(',', $columnNames);
            }

            $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);
//            $newName .= implode(':', $hash);

            if ($this->getEntity()) {
                $newName = $this->getEntity()->getTableName() . '_' . $newName;
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    /**
     * Returns the refName for this foreign key (if any).
     *
     * @return string
     */
    public function getRefName()
    {
        return $this->refName;
    }

    /**
     * Sets a refName to use for this foreign key.
     *
     * @param string $name
     */
    public function setRefName($name)
    {
        $this->refName = $name;
    }

    /**
     * Returns the default join strategy for this foreign key (if any).
     *
     * @return string
     */
    public function getDefaultJoin()
    {
        return $this->defaultJoin;
    }

    /**
     * Sets the default join strategy for this foreign key (if any).
     *
     * @param string $join
     */
    public function setDefaultJoin($join)
    {
        $this->defaultJoin = $join;
    }

    /**
     * Returns the PlatformInterface instance.
     *
     * @return PlatformInterface
     */
    private function getPlatform()
    {
        return $this->parentEntity->getPlatform();
    }

    /**
     * Returns the Database object of this Field.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->parentEntity->getDatabase();
    }

    /**
     * Returns the foreign table name of the FK, aka 'target'.
     *
     * @return string
     */
    public function getForeignEntityName()
    {
        if (null === $this->foreignEntityName && null !== $this->foreignEntity) {
            $this->foreignEntity->getFullClassName();
        }

        return $this->foreignEntityName;

//        $platform = $this->getPlatform();
//        if ($this->foreignSchemaName && $platform->supportsSchemas()) {
//            return $this->foreignSchemaName
//            . $platform->getSchemaDelimiter()
//            . $this->foreignEntityName;
//        }
//
//        $database = $this->getDatabase();
//        if ($database && ($schema = $this->parentEntity->guessSchemaName()) && $platform->supportsSchemas()) {
//            return $schema
//            . $platform->getSchemaDelimiter()
//            . $this->foreignEntityName;
//        }
//
//        return $this->foreignEntityName;
    }

    /**
     * @param string $foreignEntityName
     */
    public function setForeignEntityName($foreignEntityName)
    {
        $this->foreignEntityName = $foreignEntityName;
    }

    /**
     * Returns the resolved foreign Entity model object.
     *
     * @return Entity|null
     */
    public function getForeignEntity()
    {
        if (null !== $this->foreignEntity) {
            return $this->foreignEntity;
        }

        if (($database = $this->parentEntity->getDatabase()) && $this->getForeignEntityName()) {
            return $database->getEntity($this->getForeignEntityName());
        }
    }

    /**
     * @param null|Entity $foreignEntity
     */
    public function setForeignEntity($foreignEntity)
    {
        $this->foreignEntity = $foreignEntity;
    }

    /**
     * Sets the parent Entity of the foreign key.
     *
     * @param Entity $table
     */
    public function setEntity(Entity $parent)
    {
        $this->parentEntity = $parent;
    }

    /**
     * Returns the parent Entity of the foreign key.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->parentEntity;
    }

    /**
     * Returns the name of the table the foreign key is in.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->parentEntity->getName();
    }

    /**
     * Returns the name of the schema the foreign key is in.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->parentEntity->getSchema();
    }

    /**
     * Adds a new reference entry to the foreign key.
     *
     * @param mixed $ref1 A Field object or an associative array or a string
     * @param mixed $ref2 A Field object or a single string name
     */
    public function addReference($ref1, $ref2 = null)
    {
        if (is_array($ref1)) {
            $this->localFields[] = $ref1['local'] ? $ref1['local'] : null;
            $this->foreignFields[] = $ref1['foreign'] ? $ref1['foreign'] : null;

            return;
        }

        if (is_string($ref1)) {
            $this->localFields[] = $ref1;
            $this->foreignFields[] = is_string($ref2) ? $ref2 : null;

            return;
        }

        $local = null;
        $foreign = null;
        if ($ref1 instanceof Field) {
            $local = $ref1->getName();
        }

        if ($ref2 instanceof Field) {
            $foreign = $ref2->getName();
        }

        $this->localFields[] = $local;
        $this->foreignFields[] = $foreign;
    }

    /**
     * Clears the references of this foreign key.
     *
     */
    public function clearReferences()
    {
        $this->localFields = [];
        $this->foreignFields = [];
    }

    /**
     * Returns an array of local field names.
     *
     * @return string[]
     */
    public function getLocalFields()
    {
        return $this->localFields;
    }

    /**
     * Returns an array of local field objects.
     *
     * @return Field[]
     */
    public function getLocalFieldObjects()
    {
        $fields = [];
        foreach ($this->getLocalFields() as $fieldName) {
            $field = $this->parentEntity->getField($fieldName);
            if (null === $field) {
                throw new BuildException(sprintf(
                        'Field `%s` in local reference of relation `%s` from `%s` to `%s` not found.',
                        $fieldName,
                        $this->getName(),
                        $this->getEntity()->getName(),
                        $this->getForeignEntity()->getName()
                    ));
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Returns a local field name identified by a position.
     *
     * @param  integer $index
     *
     * @return string
     */
    public function getLocalFieldName($index = 0)
    {
        return $this->localFields[$index];
    }

    /**
     * Returns a local Field object identified by a position.
     *
     * @param  integer $index
     *
     * @return Field
     */
    public function getLocalField($index = 0)
    {
        return $this->parentEntity->getField($this->getLocalFieldName($index));
    }

    /**
     * Returns an array of local field to foreign field
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getLocalForeignMapping()
    {
        $h = [];
        for ($i = 0, $size = count($this->localFields); $i < $size; $i++) {
            $h[$this->localFields[$i]] = $this->foreignFields[$i];
        }

        return $h;
    }

    /**
     * Returns an array of local field to foreign field
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getForeignLocalMapping()
    {
        $h = [];
        for ($i = 0, $size = count($this->localFields); $i < $size; $i++) {
            $h[$this->foreignFields[$i]] = $this->localFields[$i];
        }

        return $h;
    }

    /**
     * Returns an array of local and foreign field objects
     * mapped for this foreign key.
     *
     * @return Field[][]
     */
    public function getFieldObjectsMapping()
    {
        $mapping = [];
        $foreignFields = $this->getForeignFieldObjects();
        for ($i = 0, $size = count($this->localFields); $i < $size; $i++) {
            $mapping[] = [
                'local' => $this->parentEntity->getField($this->localFields[$i]),
                'foreign' => $foreignFields[$i],
            ];
        }

        return $mapping;
    }

    /**
     * Returns an array of local and foreign field objects
     * mapped for this foreign key.
     *
     * Easy to iterate using
     *
     * foreach ($relation->getFieldObjectsMapArray() as $map) {
     *      list($local, $foreign) = $map;
     * }
     *
     * @return Field[]
     */
    public function getFieldObjectsMapArray()
    {
        $mapping = [];
        $foreignFields = $this->getForeignFieldObjects();
        for ($i = 0, $size = count($this->localFields); $i < $size; $i++) {
            $mapping[] = [$this->parentEntity->getField($this->localFields[$i]), $foreignFields[$i]];
        }

        return $mapping;
    }

    /**
     * Returns the foreign field name mapped to a specified local field.
     *
     * @param  string $local
     *
     * @return string
     */
    public function getMappedForeignField($local)
    {
        $m = $this->getLocalForeignMapping();

        return isset($m[$local]) ? $m[$local] : null;
    }

    /**
     * Returns the local field name mapped to a specified foreign field.
     *
     * @param  string $foreign
     *
     * @return string
     */
    public function getMappedLocalField($foreign)
    {
        $mapping = $this->getForeignLocalMapping();

        return isset($mapping[$foreign]) ? $mapping[$foreign] : null;
    }

    /**
     * Returns an array of foreign field names.
     *
     * @return array
     */
    public function getForeignFields()
    {
        return $this->foreignFields;
    }

    /**
     * Returns an array of foreign field objects.
     *
     * @return Field[]
     */
    public function getForeignFieldObjects()
    {
        $fields = [];
        $foreignEntity = $this->getForeignEntity();
        foreach ($this->foreignFields as $fieldName) {
            $field = null;
            if (false !== strpos($fieldName, '.')) {
                list($relationName, $foreignFieldName) = explode('.', $fieldName);
                $foreignRelation = $this->getForeignEntity()->getRelation($relationName);
                if (!$foreignRelation) {
                    throw new BuildException(sprintf(
                            'Relation `%s` in Entity %s (%s) in foreign reference of relation `%s` from `%s` to `%s` not found.',
                            $relationName,
                            $this->getForeignEntity()->getName(),
                            $fieldName,
                            $this->getName(),
                            $this->getEntity()->getName(),
                            $this->getForeignEntity()->getName()
                        ));
                }
                foreach ($foreignRelation->getFieldObjectsMapping() as $mapping) {
                    /** @var Field $local */
                    $local = $mapping['local'];
                    /** @var Field $foreign */
                    $foreign = $mapping['foreign'];
                    if ($foreign->getName() === $foreignFieldName) {
                        $field = clone $local;
                        $field->foreignRelation = $foreignRelation;
                        $field->foreignRelationFieldName = $foreignFieldName;
                    }
                }
            } else {
                $field = $foreignEntity->getField($fieldName);
            }

            if (null === $field) {
                throw new BuildException(sprintf(
                    'Field `%s` in foreign reference of relation `%s` from `%s` to `%s` not found.',
                    $fieldName,
                    $this->getName(),
                    $this->getEntity()->getName(),
                    $this->getForeignEntity()->getName()
                ));
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Returns a foreign field name.
     *
     * @param  integer $index
     *
     * @return string
     */
    public function getForeignFieldName($index = 0)
    {
        return $this->foreignFields[$index];
    }

    /**
     * Returns a foreign field object.
     *
     * @return Field
     */
    public function getForeignField($index = 0)
    {
        return $this->getForeignEntity()->getField($this->getForeignFieldName($index));
    }

    /**
     * Returns whether this foreign key uses only required local fields.
     *
     * @return boolean
     */
    public function isLocalFieldsRequired()
    {
        foreach ($this->localFields as $fieldName) {
            if (!$this->parentEntity->getField($fieldName)->isNotNull()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether this foreign key uses at least one required local field.
     *
     * @return boolean
     */
    public function isAtLeastOneLocalFieldRequired()
    {
        foreach ($this->localFields as $fieldName) {
            if ($this->parentEntity->getField($fieldName)->isNotNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key uses at least one required(notNull && no defaultValue) local primary key.
     *
     * @return boolean
     */
    public function isAtLeastOneLocalPrimaryKeyIsRequired()
    {
        foreach ($this->getLocalPrimaryKeys() as $pk) {
            if ($pk->isNotNull() && !$pk->hasDefaultValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key is also the primary key of the foreign
     * table.
     *
     * @return boolean Returns true if all fields inside this foreign key are primary keys of the foreign table
     */
    public function isForeignPrimaryKey()
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignEntity = $this->getForeignEntity();

        $foreignPKCols = [];
        foreach ($foreignEntity->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[] = $fPKCol->getName();
        }

        $foreignCols = array();
        foreach ($this->localFields as $colName) {
            $foreignCols[] = $foreignEntity->getField($lfmap[$colName])->getName();
        }

        return ((count($foreignPKCols) === count($foreignCols))
            && !array_diff($foreignPKCols, $foreignCols));
    }

    /**
     * Returns whether or not this foreign key relies on more than one
     * field binding.
     *
     * @return boolean
     */
    public function isComposite()
    {
        return count($this->localFields) > 1;
    }

    /**
     * Returns whether or not this foreign key is also the primary key of
     * the local table.
     *
     * @return boolean True if all local fields are at the same time a primary key
     */
    public function isLocalPrimaryKey()
    {
        $localPKCols = [];
        foreach ($this->parentEntity->getPrimaryKey() as $lPKCol) {
            $localPKCols[] = $lPKCol->getName();
        }

        return count($localPKCols) === count($this->localFields) && !array_diff($localPKCols, $this->localFields);
    }

    /**
     * Sets whether or not this foreign key should have its creation SQL
     * generated.
     *
     * @param boolean $skip
     */
    public function setSkipSql($skip)
    {
        $this->skipSql = (Boolean)$skip;
    }

    /**
     * Returns whether or not the SQL generation must be skipped for this
     * foreign key.
     *
     * @return boolean
     */
    public function isSkipSql()
    {
        return $this->skipSql;
    }

    /**
     * Whether this foreign key is matched by an inverted foreign key (on foreign table).
     *
     * This is to prevent duplicate fields being generated for a 1:1 relationship that is represented
     * by foreign keys on both tables.  I don't know if that's good practice ... but hell, why not
     * support it.
     *
     * @return boolean
     * @link http://propel.phpdb.org/trac/ticket/549
     */
    public function isMatchedByInverseFK()
    {
        return (Boolean)$this->getInverseFK();
    }

    public function getInverseFK()
    {
        $foreignEntity = $this->getForeignEntity();
        $map = $this->getForeignLocalMapping();

        foreach ($foreignEntity->getRelations() as $refFK) {
            $fkMap = $refFK->getLocalForeignMapping();
            // compares keys and values, but doesn't care about order, included check to make sure it's the same table (fixes #679)
            if (($refFK->getEntityName() === $this->getEntityName()) && ($map === $fkMap)) {
                return $refFK;
            }
        }
    }

    /**
     * Returns the list of other foreign keys starting on the same table.
     * Used in many-to-many relationships.
     *
     * @return Relation[]
     */
    public function getOtherFks()
    {
        $fks = [];
        foreach ($this->parentEntity->getRelations() as $fk) {
            if ($fk !== $this) {
                $fks[] = $fk;
            }
        }

        return $fks;
    }

    /**
     * Whether at least one foreign field is also the primary key of the foreign table.
     *
     * @return boolean True if there is at least one field that is a primary key of the foreign table
     */
    public function isAtLeastOneForeignPrimaryKey()
    {
        $cols = $this->getForeignPrimaryKeys();

        return 0 !== count($cols);
    }

    /**
     * Returns all foreign fields which are also a primary key of the foreign table.
     *
     * @return array Field[]
     */
    public function getForeignPrimaryKeys()
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignEntity = $this->getForeignEntity();

        $foreignPKCols = [];
        foreach ($foreignEntity->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[$fPKCol->getName()] = true;
        }

        $foreignCols = [];
        foreach ($this->getLocalField() as $colName) {
            if ($foreignPKCols[$lfmap[$colName]]) {
                $foreignCols[] = $foreignEntity->getField($lfmap[$colName]);
            }
        }

        return $foreignCols;
    }

    /**
     * Returns all local fields which are also a primary key of the local table.
     *
     * @return Field[]
     */
    public function getLocalPrimaryKeys()
    {
        $cols = [];
        $localCols = $this->getLocalFieldObjects();

        foreach ($localCols as $localCol) {
            if ($localCol->isPrimaryKey()) {
                $cols[] = $localCol;
            }
        }

        return $cols;
    }

    /**
     * Whether at least one local field is also a primary key.
     *
     * @return boolean True if there is at least one field that is a primary key
     */
    public function isAtLeastOneLocalPrimaryKey()
    {
        $cols = $this->getLocalPrimaryKeys();

        return 0 !== count($cols);
    }
}
