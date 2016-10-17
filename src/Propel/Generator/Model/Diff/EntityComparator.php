<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Entity;

/**
 * Service class for comparing Entity objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class EntityComparator
{
    /**
     * The entity difference.
     *
     * @var EntityDiff
     */
    protected $entityDiff;

    /**
     * Constructor.
     *
     * @param EntityDiff $entityDiff
     */
    public function __construct(EntityDiff $entityDiff = null)
    {
        $this->entityDiff = (null === $entityDiff) ? new EntityDiff() : $entityDiff;
    }

    /**
     * Returns the entity difference.
     *
     * @return EntityDiff
     */
    public function getEntityDiff()
    {
        return $this->entityDiff;
    }

    /**
     * Sets the entity the comparator starts from.
     *
     * @param Entity $fromEntity
     */
    public function setFromEntity(Entity $fromEntity)
    {
        $this->entityDiff->setFromEntity($fromEntity);
    }

    /**
     * Returns the entity the comparator starts from.
     *
     * @return Entity
     */
    public function getFromEntity()
    {
        return $this->entityDiff->getFromEntity();
    }

    /**
     * Sets the entity the comparator goes to.
     *
     * @param Entity $toEntity
     */
    public function setToEntity(Entity $toEntity)
    {
        $this->entityDiff->setToEntity($toEntity);
    }

    /**
     * Returns the entity the comparator goes to.
     *
     * @return Entity
     */
    public function getToEntity()
    {
        return $this->entityDiff->getToEntity();
    }

    /**
     * Returns the computed difference between two entity objects.
     *
     * @param  Entity             $fromEntity
     * @param  Entity             $toEntity
     * @param  boolean           $caseInsensitive
     * @return EntityDiff|Boolean
     */
    public static function computeDiff(Entity $fromEntity, Entity $toEntity, $caseInsensitive = false)
    {
        $tc = new self();

        $tc->setFromEntity($fromEntity);
        $tc->setToEntity($toEntity);

        $differences = 0;
        $differences += $tc->compareFields($caseInsensitive);
        $differences += $tc->comparePrimaryKeys($caseInsensitive);
        $differences += $tc->compareIndices($caseInsensitive);
        $differences += $tc->compareRelations($caseInsensitive);

        return ($differences > 0) ? $tc->getEntityDiff() : false;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the columns of the fromEntity and the toEntity,
     * and modifies the inner entityDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareFields($caseInsensitive = false)
    {
        $fromEntityFields = $this->getFromEntity()->getFields();
        $toEntityFields = $this->getToEntity()->getFields();
        $columnDifferences = 0;

        // check for new columns in $toEntity
        foreach ($toEntityFields as $column) {
            if (!$this->getFromEntity()->hasField($column->getName(), $caseInsensitive)) {
                $this->entityDiff->addAddedField($column->getName(), $column);
                $columnDifferences++;
            }
        }

        // check for removed columns in $toEntity
        foreach ($fromEntityFields as $column) {
            if (!$this->getToEntity()->hasField($column->getName(), $caseInsensitive)) {
                $this->entityDiff->addRemovedField($column->getName(), $column);
                $columnDifferences++;
            }
        }

        // check for column differences
        foreach ($fromEntityFields as $fromField) {
            if ($this->getToEntity()->hasField($fromField->getName(), $caseInsensitive)) {
                $toField = $this->getToEntity()->getField($fromField->getName(), $caseInsensitive);
                $columnDiff = FieldComparator::computeDiff($fromField, $toField, $caseInsensitive);
                if ($columnDiff) {
                    $this->entityDiff->addModifiedField($fromField->getName(), $columnDiff);
                    $columnDifferences++;
                }
            }
        }

        // check for column renamings
        foreach ($this->entityDiff->getAddedFields() as $addedFieldName => $addedField) {
            foreach ($this->entityDiff->getRemovedFields() as $removedFieldName => $removedField) {
                if (!FieldComparator::computeDiff($addedField, $removedField, $caseInsensitive)) {
                    // no difference except the name, that's probably a renaming
                    $this->entityDiff->addRenamedField($removedField, $addedField);
                    $this->entityDiff->removeAddedField($addedFieldName);
                    $this->entityDiff->removeRemovedField($removedFieldName);
                    $columnDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $columnDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the primary keys of the fromEntity and the toEntity,
     * and modifies the inner entityDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function comparePrimaryKeys($caseInsensitive = false)
    {
        $pkDifferences = 0;
        $fromEntityPk = $this->getFromEntity()->getPrimaryKey();
        $toEntityPk = $this->getToEntity()->getPrimaryKey();

        // check for new pk columns in $toEntity
        foreach ($toEntityPk as $column) {
            if (!$this->getFromEntity()->hasField($column->getName(), $caseInsensitive) ||
                !$this->getFromEntity()->getField($column->getName(), $caseInsensitive)->isPrimaryKey()) {
                    $this->entityDiff->addAddedPkField($column->getName(), $column);
                    $pkDifferences++;
            }
        }

        // check for removed pk columns in $toEntity
        foreach ($fromEntityPk as $column) {
            if (!$this->getToEntity()->hasField($column->getName(), $caseInsensitive) ||
                !$this->getToEntity()->getField($column->getName(), $caseInsensitive)->isPrimaryKey()) {
                    $this->entityDiff->addRemovedPkField($column->getName(), $column);
                    $pkDifferences++;
            }
        }

        // check for column renamings
        foreach ($this->entityDiff->getAddedPkFields() as $addedFieldName => $addedField) {
            foreach ($this->entityDiff->getRemovedPkFields() as $removedFieldName => $removedField) {
                if (!FieldComparator::computeDiff($addedField, $removedField, $caseInsensitive)) {
                    // no difference except the name, that's probably a renaming
                    $this->entityDiff->addRenamedPkField($removedField, $addedField);
                    $this->entityDiff->removeAddedPkField($addedFieldName);
                    $this->entityDiff->removeRemovedPkField($removedFieldName);
                    $pkDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $pkDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the indices and unique indices of the fromEntity and the toEntity,
     * and modifies the inner entityDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareIndices($caseInsensitive = false)
    {
        $indexDifferences = 0;
        $fromEntityIndices = array_merge($this->getFromEntity()->getIndices(), $this->getFromEntity()->getUnices());
        $toEntityIndices = array_merge($this->getToEntity()->getIndices(), $this->getToEntity()->getUnices());

        foreach ($fromEntityIndices as $fromEntityIndexPos => $fromEntityIndex) {
            foreach ($toEntityIndices as $toEntityIndexPos => $toEntityIndex) {
                $sameName = $caseInsensitive ?
                    strtolower($fromEntityIndex->getName()) == strtolower($toEntityIndex->getName()) :
                    $fromEntityIndex->getName() == $toEntityIndex->getName();
                if ($sameName) {
                    if (false === IndexComparator::computeDiff($fromEntityIndex, $toEntityIndex, $caseInsensitive)) {
                        //no changes
                        unset($fromEntityIndices[$fromEntityIndexPos]);
                        unset($toEntityIndices[$toEntityIndexPos]);
                    } else {
                        // same name, but different columns
                        $this->entityDiff->addModifiedIndex($fromEntityIndex->getName(), $fromEntityIndex, $toEntityIndex);
                        unset($fromEntityIndices[$fromEntityIndexPos]);
                        unset($toEntityIndices[$toEntityIndexPos]);
                        $indexDifferences++;
                    }
                }
            }
        }

        foreach ($fromEntityIndices as $fromEntityIndex) {
            $this->entityDiff->addRemovedIndex($fromEntityIndex->getName(), $fromEntityIndex);
            $indexDifferences++;
        }

        foreach ($toEntityIndices as $toEntityIndex) {
            $this->entityDiff->addAddedIndex($toEntityIndex->getName(), $toEntityIndex);
            $indexDifferences++;
        }

        return $indexDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the foreign keys of the fromEntity and the toEntity,
     * and modifies the inner entityDiff if necessary.
     *
     * @param  boolean $caseInsensitive
     * @return integer
     */
    public function compareRelations($caseInsensitive = false)
    {
        $fkDifferences = 0;
        $fromEntityFks = $this->getFromEntity()->getRelations();
        $toEntityFks = $this->getToEntity()->getRelations();

        foreach ($fromEntityFks as $fromEntityFkPos => $fromEntityFk) {
            foreach ($toEntityFks as $toEntityFkPos => $toEntityFk) {
                $sameName = $caseInsensitive ?
                    strtolower($fromEntityFk->getName()) == strtolower($toEntityFk->getName()) :
                    $fromEntityFk->getName() == $toEntityFk->getName();
                if ($sameName) {
                    if (false === RelationComparator::computeDiff($fromEntityFk, $toEntityFk, $caseInsensitive)) {
                        unset($fromEntityFks[$fromEntityFkPos]);
                        unset($toEntityFks[$toEntityFkPos]);
                    } else {
                        // same name, but different columns
                        $this->entityDiff->addModifiedFk($fromEntityFk->getName(), $fromEntityFk, $toEntityFk);
                        unset($fromEntityFks[$fromEntityFkPos]);
                        unset($toEntityFks[$toEntityFkPos]);
                        $fkDifferences++;
                    }
                }
            }
        }

        foreach ($fromEntityFks as $fromEntityFk) {
            if (!$fromEntityFk->isSkipSql() && !in_array($fromEntityFk, $toEntityFks)) {
                $this->entityDiff->addRemovedFk($fromEntityFk->getName(), $fromEntityFk);
                $fkDifferences++;
            }
        }

        foreach ($toEntityFks as $toEntityFk) {
            if (!$toEntityFk->isSkipSql() && !in_array($toEntityFk, $fromEntityFks)) {
                $this->entityDiff->addAddedFk($toEntityFk->getName(), $toEntityFk);
                $fkDifferences++;
            }
        }

        return $fkDifferences;
    }
}
