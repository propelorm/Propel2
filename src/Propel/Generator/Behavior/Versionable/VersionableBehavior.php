<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Versionable;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\ForeignKey;

/**
 * Keeps tracks of all the modifications in an ActiveRecord object
 *
 * @author Francois Zaninotto
 */
class VersionableBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'version_field'            => 'version',
        'version_table'             => '',
        'log_created_at'            => 'false',
        'log_created_by'            => 'false',
        'log_comment'               => 'false',
        'version_created_at_field' => 'version_created_at',
        'version_created_by_field' => 'version_created_by',
        'version_comment_field'    => 'version_comment'
    );

    protected $versionEntity;

    protected $objectBuilderModifier;

    protected $queryBuilderModifier;

    protected $tableModificationOrder = 80;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getEntities() as $table) {
            if ($table->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            if (property_exists($table, 'isVersionEntity')) {
                // don't add the behavior to version entities
                continue;
            }
            $b = clone $this;
            $table->addBehavior($b);
        }
    }

    public function modifyEntity()
    {
        $this->addVersionField();
        $this->addLogFields();
        $this->addVersionEntity();
        $this->addForeignKeyVersionFields();
    }

    protected function addVersionField()
    {
        $table = $this->getEntity();
        // add the version field
        if (!$table->hasField($this->getParameter('version_field'))) {
            $table->addField(array(
                'name'    => $this->getParameter('version_field'),
                'type'    => 'INTEGER',
                'default' => 0
            ));
        }
    }

    protected function addLogFields()
    {
        $table = $this->getEntity();
        if ('true' === $this->getParameter('log_created_at') && !$table->hasField($this->getParameter('version_created_at_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('version_created_at_field'),
                'type' => 'TIMESTAMP'
            ));
        }
        if ('true' === $this->getParameter('log_created_by') && !$table->hasField($this->getParameter('version_created_by_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('version_created_by_field'),
                'type' => 'VARCHAR',
                'size' => 100
            ));
        }
        if ('true' === $this->getParameter('log_comment') && !$table->hasField($this->getParameter('version_comment_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('version_comment_field'),
                'type' => 'VARCHAR',
                'size' => 255
            ));
        }
    }

    protected function addVersionEntity()
    {
        $table = $this->getEntity();
        $database = $table->getDatabase();
        $versionEntityName = $this->getParameter('version_table') ? $this->getParameter('version_table') : ($table->getName() . '_version');
        if (!$database->hasEntity($versionEntityName)) {
            // create the version table
            $versionEntity = $database->addEntity(array(
                'name'      => $versionEntityName,
                'phpName'   => $this->getVersionEntityPhpName(),
                'package'   => $table->getPackage(),
                'schema'    => $table->getSchema(),
                'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
                'skipSql'   => $table->isSkipSql()
            ));
            $versionEntity->isVersionEntity = true;
            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
            // copy all the fields
            foreach ($table->getFields() as $field) {
                $fieldInVersionEntity = clone $field;
                $fieldInVersionEntity->clearInheritanceList();
                if ($fieldInVersionEntity->hasReferrers()) {
                    $fieldInVersionEntity->clearReferrers();
                }
                if ($fieldInVersionEntity->isAutoincrement()) {
                    $fieldInVersionEntity->setAutoIncrement(false);
                }
                $versionEntity->addField($fieldInVersionEntity);
            }
            // create the foreign key
            $fk = new ForeignKey();
            $fk->setForeignEntityCommonName($table->getCommonName());
            $fk->setForeignSchemaName($table->getSchema());
            $fk->setOnDelete('CASCADE');
            $fk->setOnUpdate(null);
            $tablePKs = $table->getPrimaryKey();
            foreach ($versionEntity->getPrimaryKey() as $key => $field) {
                $fk->addReference($field, $tablePKs[$key]);
            }
            $versionEntity->addForeignKey($fk);

            // add the version field to the primary key
            $versionField = $versionEntity->getField($this->getParameter('version_field'));
            $versionField->setNotNull(true);
            $versionField->setPrimaryKey(true);
            $this->versionEntity = $versionEntity;
        } else {
            $this->versionEntity = $database->getEntity($versionEntityName);
        }
    }

    public function addForeignKeyVersionFields()
    {
        $versionEntity = $this->versionEntity;
        foreach ($this->getVersionableFks() as $fk) {
            $fkVersionFieldName = $fk->getLocalFieldName() . '_version';
            if (!$versionEntity->hasField($fkVersionFieldName)) {
                $versionEntity->addField(array(
                    'name'    => $fkVersionFieldName,
                    'type'    => 'INTEGER',
                    'default' => 0
                ));
            }
        }

        foreach ($this->getVersionableReferrers() as $fk) {
            $fkEntityName = $fk->getEntity()->getName();
            $fkIdsFieldName = $fkEntityName . '_ids';
            if (!$versionEntity->hasField($fkIdsFieldName)) {
                $versionEntity->addField(array(
                    'name'    => $fkIdsFieldName,
                    'type'    => 'ARRAY'
                ));
            }

            $fkVersionsFieldName = $fkEntityName . '_versions';
            if (!$versionEntity->hasField($fkVersionsFieldName)) {
                $versionEntity->addField(array(
                    'name'    => $fkVersionsFieldName,
                    'type'    => 'ARRAY'
                ));
            }
        }
    }

    public function getVersionEntity()
    {
        return $this->versionEntity;
    }

    public function getVersionEntityPhpName()
    {
        return $this->getEntity()->getName() . 'Version';
    }

    public function getVersionableFks()
    {
        $versionableFKs = array();
        if ($fks = $this->getEntity()->getForeignKeys()) {
            foreach ($fks as $fk) {
                if ($fk->getForeignEntity()->hasBehavior($this->getName()) && ! $fk->isComposite()) {
                    $versionableFKs []= $fk;
                }
            }
        }

        return $versionableFKs;
    }

    public function getVersionableReferrers()
    {
        $versionableReferrers = array();
        if ($fks = $this->getEntity()->getReferrers()) {
            foreach ($fks as $fk) {
                if ($fk->getEntity()->hasBehavior($this->getName()) && ! $fk->isComposite()) {
                    $versionableReferrers []= $fk;
                }
            }
        }

        return $versionableReferrers;
    }

    public function getReferrerIdsField(ForeignKey $fk)
    {
        $fkEntityName = $fk->getEntity()->getName();
        $fkIdsFieldName = $fkEntityName . '_ids';

        return $this->versionEntity->getField($fkIdsFieldName);
    }

    public function getReferrerVersionsField(ForeignKey $fk)
    {
        $fkEntityName = $fk->getEntity()->getName();
        $fkIdsFieldName = $fkEntityName . '_versions';

        return $this->versionEntity->getField($fkIdsFieldName);
    }

    public function getObjectBuilderModifier()
    {
        if (null === $this->objectBuilderModifier) {
            $this->objectBuilderModifier = new VersionableBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    public function getQueryBuilderModifier()
    {
        if (null === $this->queryBuilderModifier) {
            $this->queryBuilderModifier = new VersionableBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }
}
