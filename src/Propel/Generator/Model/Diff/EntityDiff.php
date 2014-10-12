<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Exception\DiffException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;

/**
 * Value object for storing Entity object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class EntityDiff
{
    /**
     * The first Entity object.
     *
     * @var Entity
     */
    protected $fromEntity;

    /**
     * The second Entity object.
     *
     * @var Entity
     */
    protected $toEntity;

    /**
     * The list of added columns.
     *
     * @var array
     */
    protected $addedFields;

    /**
     * The list of removed columns.
     *
     * @var array
     */
    protected $removedFields;

    /**
     * The list of modified columns.
     *
     * @var array
     */
    protected $modifiedFields;

    /**
     * The list of renamed columns.
     *
     * @var array
     */
    protected $renamedFields;

    /**
     * The list of added primary key columns.
     *
     * @var array
     */
    protected $addedPkFields;

    /**
     * The list of removed primary key columns.
     *
     * @var array
     */
    protected $removedPkFields;

    /**
     * The list of renamed primary key columns.
     *
     * @var array
     */
    protected $renamedPkFields;

    /**
     * The list of added indices.
     *
     * @var array
     */
    protected $addedIndices;

    /**
     * The list of removed indices.
     *
     * @var array
     */
    protected $removedIndices;

    /**
     * The list of modified indices.
     *
     * @var array
     */
    protected $modifiedIndices;

    /**
     * The list of added foreign keys.
     *
     * @var array
     */
    protected $addedFks;

    /**
     * The list of removed foreign keys.
     *
     * @var array
     */
    protected $removedFks;

    /**
     * The list of modified columns.
     *
     * @var array
     */
    protected $modifiedFks;

    /**
     * Constructor.
     *
     * @param Entity $fromEntity The first table
     * @param Entity $toEntity   The second table
     */
    public function __construct(Entity $fromEntity = null, Entity $toEntity = null)
    {
        if (null !== $fromEntity) {
            $this->setFromEntity($fromEntity);
        }

        if (null !== $toEntity) {
            $this->setToEntity($toEntity);
        }

        $this->addedFields     = [];
        $this->removedFields   = [];
        $this->modifiedFields  = [];
        $this->renamedFields   = [];
        $this->addedPkFields   = [];
        $this->removedPkFields = [];
        $this->renamedPkFields = [];
        $this->addedIndices     = [];
        $this->modifiedIndices  = [];
        $this->removedIndices   = [];
        $this->addedFks         = [];
        $this->modifiedFks      = [];
        $this->removedFks       = [];
    }

    /**
     * Sets the fromEntity property.
     *
     * @param Entity $fromEntity
     */
    public function setFromEntity(Entity $fromEntity)
    {
        $this->fromEntity = $fromEntity;
    }

    /**
     * Returns the fromEntity property.
     *
     * @return Entity
     */
    public function getFromEntity()
    {
        return $this->fromEntity;
    }

    /**
     * Sets the toEntity property.
     *
     * @param Entity $toEntity
     */
    public function setToEntity(Entity $toEntity)
    {
        $this->toEntity = $toEntity;
    }

    /**
     * Returns the toEntity property.
     *
     * @return Entity
     */
    public function getToEntity()
    {
        return $this->toEntity;
    }

    /**
     * Sets the added columns.
     *
     * @param Field[] $columns
     */
    public function setAddedFields(array $columns)
    {
        $this->addedFields = [];
        foreach ($columns as $column) {
            $this->addAddedField($column->getName(), $column);
        }
    }

    /**
     * Adds an added column.
     *
     * @param string $name
     * @param Field $column
     */
    public function addAddedField($name, Field $column)
    {
        $this->addedFields[$name] = $column;
    }

    /**
     * Removes an added column.
     *
     * @param string $columnName
     */
    public function removeAddedField($columnName)
    {
        if (isset($this->addedFields[$columnName])) {
            unset($this->addedFields[$columnName]);
        }
    }

    /**
     * Returns the list of added columns
     *
     * @return Field[]
     */
    public function getAddedFields()
    {
        return $this->addedFields;
    }

    /**
     * Returns an added column by its name.
     *
     * @param  string      $columnName
     * @return Field|null
     */
    public function getAddedField($columnName)
    {
        if (isset($this->addedFields[$columnName])) {
            return $this->addedFields[$columnName];
        }
    }

    /**
     * Setter for the removedFields property
     *
     * @param Field[] $removedFields
     */
    public function setRemovedFields(array $removedFields)
    {
        $this->removedFields = [];
        foreach ($removedFields as $removedField) {
            $this->addRemovedField($removedField->getName(), $removedField);
        }
    }

    /**
     * Adds a removed column.
     *
     * @param string $columnName
     * @param Field $removedField
     */
    public function addRemovedField($columnName, Field $removedField)
    {
        $this->removedFields[$columnName] = $removedField;
    }

    /**
     * Removes a removed column.
     *
     * @param string $columnName
     */
    public function removeRemovedField($columnName)
    {
        unset($this->removedFields[$columnName]);
    }

    /**
     * Getter for the removedFields property.
     *
     * @return Field[]
     */
    public function getRemovedFields()
    {
        return $this->removedFields;
    }

    /**
     * Get a removed column
     *
     * @param string $columnName
     *
     * @param Field
     */
    public function getRemovedField($columnName)
    {
        if (isset($this->removedFields[$columnName])) {
            return $this->removedFields[$columnName];
        }
    }

    /**
     * Sets the list of modified columns.
     *
     * @param FieldDiff[] $modifiedFields An associative array of FieldDiff objects
     */
    public function setModifiedFields(array $modifiedFields)
    {
        $this->modifiedFields = [];
        foreach ($modifiedFields as $columnName => $modifiedField) {
            $this->addModifiedField($columnName, $modifiedField);
        }
    }

    /**
     * Add a column difference
     *
     * @param string     $columnName
     * @param FieldDiff $modifiedField
     */
    public function addModifiedField($columnName, FieldDiff $modifiedField)
    {
        $this->modifiedFields[$columnName] = $modifiedField;
    }

    /**
     * Getter for the modifiedFields property
     *
     * @return FieldDiff[]
     */
    public function getModifiedFields()
    {
        return $this->modifiedFields;
    }

    /**
     * Sets the list of renamed columns.
     *
     * @param array $renamedFields
     */
    public function setRenamedFields(array $renamedFields)
    {
        $this->renamedFields = [];
        foreach ($renamedFields as $columns) {
            list($fromField, $toField) = $columns;
            $this->addRenamedField($fromField, $toField);
        }
    }

    /**
     * Add a renamed column
     *
     * @param Field $fromField
     * @param Field $toField
     */
    public function addRenamedField(Field $fromField, Field $toField)
    {
        $this->renamedFields[] = [ $fromField, $toField ];
    }

    /**
     * Getter for the renamedFields property
     *
     * @return array
     */
    public function getRenamedFields()
    {
        return $this->renamedFields;
    }

    /**
     * Sets the list of added primary key columns.
     *
     * @param Field[] $addedPkFields
     */
    public function setAddedPkFields(array $addedPkFields)
    {
        $this->addedPkFields = [];
        foreach ($addedPkFields as $addedPkField) {
            $this->addAddedPkField($addedPkField->getName(), $addedPkField);
        }
    }

    /**
     * Add an added Pk column
     *
     * @param string $columnName
     * @param Field $addedPkField
     */
    public function addAddedPkField($columnName, Field $addedPkField)
    {
        if (!$addedPkField->isPrimaryKey()) {
            throw new DiffException(sprintf('Field %s is not a valid primary key column.', $columnName));
        }

        $this->addedPkFields[$columnName] = $addedPkField;
    }

    /**
     * Removes an added primary key column.
     *
     * @param string $columnName
     */
    public function removeAddedPkField($columnName)
    {
        if (isset($this->addedPkFields[$columnName])) {
            unset($this->addedPkFields[$columnName]);
        }
    }

    /**
     * Getter for the addedPkFields property
     *
     * @return array
     */
    public function getAddedPkFields()
    {
        return $this->addedPkFields;
    }

    /**
     * Sets the list of removed primary key columns.
     *
     * @param Field[] $removedPkFields
     */
    public function setRemovedPkFields(array $removedPkFields)
    {
        $this->removedPkFields = [];
        foreach ($removedPkFields as $removedPkField) {
            $this->addRemovedPkField($removedPkField->getName(), $removedPkField);
        }
    }

    /**
     * Add a removed Pk column
     *
     * @param string $columnName
     * @param Field $removedField
     */
    public function addRemovedPkField($columnName, Field $removedPkField)
    {
        $this->removedPkFields[$columnName] = $removedPkField;
    }

    /**
     * Removes a removed primary key column.
     *
     * @param string $columnName
     */
    public function removeRemovedPkField($columnName)
    {
        if (isset($this->removedPkFields[$columnName])) {
            unset($this->removedPkFields[$columnName]);
        }
    }

    /**
     * Getter for the removedPkFields property
     *
     * @return array
     */
    public function getRemovedPkFields()
    {
        return $this->removedPkFields;
    }

    /**
     * Sets the list of all renamed primary key columns.
     *
     * @param Field[] $renamedPkFields
     */
    public function setRenamedPkFields(array $renamedPkFields)
    {
        $this->renamedPkFields = [];
        foreach ($renamedPkFields as $columns) {
            list($fromField, $toField) = $columns;
            $this->addRenamedPkField($fromField, $toField);
        }
    }

    /**
     * Adds a renamed primary key column.
     *
     * @param Field $fromField The original column
     * @param Field $toField   The renamed column
     */
    public function addRenamedPkField(Field $fromField, Field $toField)
    {
        $this->renamedPkFields[] = [ $fromField, $toField ];
    }

    /**
     * Getter for the renamedPkFields property
     *
     * @return array
     */
    public function getRenamedPkFields()
    {
        return $this->renamedPkFields;
    }

    /**
     * Whether the primary key was modified
     *
     * @return boolean
     */
    public function hasModifiedPk()
    {
        return $this->renamedPkFields || $this->removedPkFields || $this->addedPkFields;
    }

    /**
     * Sets the list of new added indices.
     *
     * @param Index[] $addedIndices
     */
    public function setAddedIndices(array $addedIndices)
    {
        $this->addedIndices = [];
        foreach ($addedIndices as $addedIndex) {
            $this->addAddedIndex($addedIndex->getName(), $addedIndex);
        }
    }

    /**
     * Add an added index.
     *
     * @param string $indexName
     * @param Index  $addedIndex
     */
    public function addAddedIndex($indexName, Index $addedIndex)
    {
        $this->addedIndices[$indexName] = $addedIndex;
    }

    /**
     * Getter for the addedIndices property
     *
     * @return Index[]
     */
    public function getAddedIndices()
    {
        return $this->addedIndices;
    }

    /**
     * Sets the list of removed indices.
     *
     * @param Index[] $removedIndices
     */
    public function setRemovedIndices(array $removedIndices)
    {
        $this->removedIndices = [];
        foreach ($removedIndices as $removedIndex) {
            $this->addRemovedIndex($removedIndex->getName(), $removedIndex);
        }
    }

    /**
     * Adds a removed index.
     *
     * @param string $indexName
     * @param Index  $removedIndex
     */
    public function addRemovedIndex($indexName, Index $removedIndex)
    {
        $this->removedIndices[$indexName] = $removedIndex;
    }

    /**
     * Getter for the removedIndices property
     *
     * @return Index[]
     */
    public function getRemovedIndices()
    {
        return $this->removedIndices;
    }

    /**
     * Sets the list of modified indices.
     *
     * Array must be [ [ Index $fromIndex, Index $toIndex ], [ ... ] ]
     *
     * @param Index[] $modifiedIndices An aray of modified indices
     */
    public function setModifiedIndices(array $modifiedIndices)
    {
        $this->modifiedIndices = [];
        foreach ($modifiedIndices as $indices) {
            list($fromIndex, $toIndex) = $indices;
            $this->addModifiedIndex($fromIndex->getName(), $fromIndex, $toIndex);
        }
    }

    /**
     * Add a modified index.
     *
     * @param string $indexName
     * @param Index  $fromIndex
     * @param Index  $toIndex
     */
    public function addModifiedIndex($indexName, Index $fromIndex, Index $toIndex)
    {
        $this->modifiedIndices[$indexName] = [ $fromIndex, $toIndex ];
    }

    /**
     * Getter for the modifiedIndices property
     *
     * @return array
     */
    public function getModifiedIndices()
    {
        return $this->modifiedIndices;
    }

    /**
     * Sets the list of added foreign keys.
     *
     * @param Relation[] $addedFks
     */
    public function setAddedFks(array $addedFks)
    {
        $this->addedFks = [];
        foreach ($addedFks as $addedFk) {
            $this->addAddedFk($addedFk->getName(), $addedFk);
        }
    }

    /**
     * Adds an added foreign key.
     *
     * @param string     $fkName
     * @param Relation $addedFk
     */
    public function addAddedFk($fkName, Relation $addedFk)
    {
        $this->addedFks[$fkName] = $addedFk;
    }

    /**
     * Remove an added Fk column
     *
     * @param string $fkName
     */
    public function removeAddedFk($fkName)
    {
        if (isset($this->addedFks[$fkName])) {
            unset($this->addedFks[$fkName]);
        }
    }

    /**
     * Getter for the addedFks property
     *
     * @return Relation[]
     */
    public function getAddedFks()
    {
        return $this->addedFks;
    }

    /**
     * Sets the list of removed foreign keys.
     *
     * @param Relation[] $removedFks
     */
    public function setRemovedFks(array $removedFks)
    {
        $this->removedFks = [];
        foreach ($removedFks as $removedFk) {
            $this->addRemovedFk($removedFk->getName(), $removedFk);
        }
    }

    /**
     * Adds a removed foreign key column.
     *
     * @param string     $fkName
     * @param Relation $removedField
     */
    public function addRemovedFk($fkName, Relation $removedFk)
    {
        $this->removedFks[$fkName] = $removedFk;
    }

    /**
     * Removes a removed foreign key.
     *
     * @param string $fkName
     */
    public function removeRemovedFk($fkName)
    {
        unset($this->removedFks[$fkName]);
    }

    /**
     * Returns the list of removed foreign keys.
     *
     * @return Relation[]
     */
    public function getRemovedFks()
    {
        return $this->removedFks;
    }

    /**
     * Sets the list of modified foreign keys.
     *
     * Array must be [ [ Relation $fromFk, Relation $toFk ], [ ... ] ]
     *
     * @param Relation[] $modifiedFks
     */
    public function setModifiedFks(array $modifiedFks)
    {
        $this->modifiedFks = [];
        foreach ($modifiedFks as $relations) {
            list($fromRelation, $toRelation) = $relations;
            $this->addModifiedFk($fromRelation->getName(), $fromRelation, $toRelation);
        }
    }

    /**
     * Adds a modified foreign key.
     *
     * @param string     $fkName
     * @param Relation $fromFk
     * @param Relation $toFk
     */
    public function addModifiedFk($fkName, Relation $fromFk, Relation $toFk)
    {
        $this->modifiedFks[$fkName] = [ $fromFk, $toFk ];
    }

    /**
     * Returns the list of modified foreign keys.
     *
     * @return array
     */
    public function getModifiedFks()
    {
        return $this->modifiedFks;
    }

    /**
     * Returns whether or not there are
     * some modified foreign keys.
     *
     * @return boolean
     */
    public function hasModifiedFks()
    {
        return !empty($this->modifiedFks);
    }

    /**
     * Returns whether or not there are
     * some modified indices.
     *
     * @return boolean
     */
    public function hasModifiedIndices()
    {
        return !empty($this->modifiedIndices);
    }

    /**
     * Returns whether or not there are
     * some modified columns.
     *
     * @return boolean
     */
    public function hasModifiedFields()
    {
        return !empty($this->modifiedFields);
    }

    /**
     * Returns whether or not there are
     * some removed foreign keys.
     *
     * @return boolean
     */
    public function hasRemovedFks()
    {
        return !empty($this->removedFks);
    }

    /**
     * Returns whether or not there are
     * some removed indices.
     *
     * @return boolean
     */
    public function hasRemovedIndices()
    {
        return !empty($this->removedIndices);
    }

    /**
     * Returns whether or not there are
     * some renamed columns.
     *
     * @return boolean
     */
    public function hasRenamedFields()
    {
        return !empty($this->renamedFields);
    }

    /**
     * Returns whether or not there are
     * some removed columns.
     *
     * @return boolean
     */
    public function hasRemovedFields()
    {
        return !empty($this->removedFields);
    }

    /**
     * Returns whether or not there are
     * some added columns.
     *
     * @return boolean
     */
    public function hasAddedFields()
    {
        return !empty($this->addedFields);
    }

    /**
     * Returns whether or not there are
     * some added indices.
     *
     * @return boolean
     */
    public function hasAddedIndices()
    {
        return !empty($this->addedIndices);
    }

    /**
     * Returns whether or not there are
     * some added foreign keys.
     *
     * @return boolean
     */
    public function hasAddedFks()
    {
        return !empty($this->addedFks);
    }

    /**
     * Returns whether or not there are
     * some added primary key columns.
     *
     * @return boolean
     */
    public function hasAddedPkFields()
    {
        return !empty($this->addedPkFields);
    }

    /**
     * Returns whether or not there are
     * some removed primary key columns.
     *
     * @return boolean
     */
    public function hasRemovedPkFields()
    {
        return !empty($this->removedPkFields);
    }

    /**
     * Returns whether or not there are
     * some renamed primary key columns.
     *
     * @return boolean
     */
    public function hasRenamedPkFields()
    {
        return !empty($this->renamedPkFields);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return EntityDiff
     */
    public function getReverseDiff()
    {
        $diff = new self();

        // tables
        $diff->setFromEntity($this->toEntity);
        $diff->setToEntity($this->fromEntity);

        // columns
        if ($this->hasAddedFields()) {
            $diff->setRemovedFields($this->addedFields);
        }

        if ($this->hasRemovedFields()) {
            $diff->setAddedFields($this->removedFields);
        }

        if ($this->hasRenamedFields()) {
            $renamedFields = [];
            foreach ($this->renamedFields as $columnRenaming) {
                $renamedFields[] = array_reverse($columnRenaming);
            }
            $diff->setRenamedFields($renamedFields);
        }

        if ($this->hasModifiedFields()) {
            $columnDiffs = [];
            foreach ($this->modifiedFields as $name => $columnDiff) {
                $columnDiffs[$name] = $columnDiff->getReverseDiff();
            }
            $diff->setModifiedFields($columnDiffs);
        }

        // pks
        if ($this->hasRemovedPkFields()) {
            $diff->setAddedPkFields($this->removedPkFields);
        }

        if ($this->hasAddedPkFields()) {
            $diff->setRemovedPkFields($this->addedPkFields);
        }

        if ($this->hasRenamedPkFields()) {
            $renamedPkFields = [];
            foreach ($this->renamedPkFields as $columnRenaming) {
                $renamedPkFields[] = array_reverse($columnRenaming);
            }
            $diff->setRenamedPkFields($renamedPkFields);
        }

        // indices
        if ($this->hasRemovedIndices()) {
            $diff->setAddedIndices($this->removedIndices);
        }

        if ($this->hasAddedIndices()) {
            $diff->setRemovedIndices($this->addedIndices);
        }

        if ($this->hasModifiedIndices()) {
            $indexDiffs = [];
            foreach ($this->modifiedIndices as $name => $indexDiff) {
                $indexDiffs[$name] = array_reverse($indexDiff);
            }
            $diff->setModifiedIndices($indexDiffs);
        }

        // fks
        if ($this->hasAddedFks()) {
            $diff->setRemovedFks($this->addedFks);
        }

        if ($this->hasRemovedFks()) {
            $diff->setAddedFks($this->removedFks);
        }

        if ($this->hasModifiedFks()) {
            $fkDiffs = [];
            foreach ($this->modifiedFks as $name => $fkDiff) {
                $fkDiffs[$name] = array_reverse($fkDiff);
            }
            $diff->setModifiedFks($fkDiffs);
        }

        return $diff;
    }

    /**
     * Clones the current diff object.
     *
     */
    public function __clone()
    {
        if ($this->fromEntity) {
            $this->fromEntity = clone $this->fromEntity;
        }
        if ($this->toEntity) {
            $this->toEntity = clone $this->toEntity;
        }
    }

    /**
     * Returns the string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        $ret = '';
        $ret .= sprintf("  %s:\n", $this->fromEntity->getName());
        if ($addedFields = $this->getAddedFields()) {
            $ret .= "    addedFields:\n";
            foreach ($addedFields as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($removedFields = $this->getRemovedFields()) {
            $ret .= "    removedFields:\n";
            foreach ($removedFields as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($modifiedFields = $this->getModifiedFields()) {
            $ret .= "    modifiedFields:\n";
            foreach ($modifiedFields as $colDiff) {
                $ret .= (string) $colDiff;
            }
        }
        if ($renamedFields = $this->getRenamedFields()) {
            $ret .= "    renamedFields:\n";
            foreach ($renamedFields as $columnRenaming) {
                list($fromField, $toField) = $columnRenaming;
                $ret .= sprintf("      %s: %s\n", $fromField->getName(), $toField->getName());
            }
        }
        if ($addedIndices = $this->getAddedIndices()) {
            $ret .= "    addedIndices:\n";
            foreach ($addedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($removedIndices = $this->getRemovedIndices()) {
            $ret .= "    removedIndices:\n";
            foreach ($removedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($modifiedIndices = $this->getModifiedIndices()) {
            $ret .= "    modifiedIndices:\n";
            foreach ($modifiedIndices as $indexName => $indexDiff) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($addedFks = $this->getAddedFks()) {
            $ret .= "    addedFks:\n";
            foreach ($addedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($removedFks = $this->getRemovedFks()) {
            $ret .= "    removedFks:\n";
            foreach ($removedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($modifiedFks = $this->getModifiedFks()) {
            $ret .= "    modifiedFks:\n";
            foreach ($modifiedFks as $fkName => $fkFromTo) {
                $ret .= sprintf("      %s:\n", $fkName);
                list($fromFk, $toFk) = $fkFromTo;
                $fromLocalFields = json_encode($fromFk->getLocalFields());
                $toLocalFields = json_encode($toFk->getLocalFields());
                if ($fromLocalFields != $toLocalFields) {
                    $ret .= sprintf("          localFields: from %s to %s\n", $fromLocalFields, $toLocalFields);
                }
                $fromForeignFields = json_encode($fromFk->getForeignFields());
                $toForeignFields = json_encode($toFk->getForeignFields());
                if ($fromForeignFields != $toForeignFields) {
                    $ret .= sprintf("          foreignFields: from %s to %s\n", $fromForeignFields, $toForeignFields);
                }
                if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) != $toFk->normalizeFKey($toFk->getOnUpdate())) {
                    $ret .= sprintf("          onUpdate: from %s to %s\n", $fromFk->getOnUpdate(), $toFk->getOnUpdate());
                }
                if ($fromFk->normalizeFKey($fromFk->getOnDelete()) != $toFk->normalizeFKey($toFk->getOnDelete())) {
                    $ret .= sprintf("          onDelete: from %s to %s\n", $fromFk->getOnDelete(), $toFk->getOnDelete());
                }
            }
        }

        return $ret;
    }
}
