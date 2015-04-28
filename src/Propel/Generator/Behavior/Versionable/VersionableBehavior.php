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
        'version_column'            => 'version',
        'version_table'             => '',
        'log_created_at'            => 'false',
        'log_created_by'            => 'false',
        'log_comment'               => 'false',
        'version_created_at_column' => 'version_created_at',
        'version_created_by_column' => 'version_created_by',
        'version_comment_column'    => 'version_comment',
        'indices'                   => 'false'
    );

    protected $versionTable;

    protected $objectBuilderModifier;

    protected $queryBuilderModifier;

    protected $tableModificationOrder = 80;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            if ($table->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            if (property_exists($table, 'isVersionTable')) {
                // don't add the behavior to version tables
                continue;
            }
            $b = clone $this;
            $table->addBehavior($b);
        }
    }

    public function modifyTable()
    {
        $this->addVersionColumn();
        $this->addLogColumns();
        $this->addVersionTable();
        $this->addForeignKeyVersionColumns();
    }

    protected function addVersionColumn()
    {
        $table = $this->getTable();
        // add the version column
        if (!$table->hasColumn($this->getParameter('version_column'))) {
            $table->addColumn(array(
                'name'    => $this->getParameter('version_column'),
                'type'    => 'INTEGER',
                'default' => 0
            ));
        }
    }

    protected function addLogColumns()
    {
        $table = $this->getTable();
        if ('true' === $this->getParameter('log_created_at') && !$table->hasColumn($this->getParameter('version_created_at_column'))) {
            $table->addColumn(array(
                'name' => $this->getParameter('version_created_at_column'),
                'type' => 'TIMESTAMP'
            ));
        }
        if ('true' === $this->getParameter('log_created_by') && !$table->hasColumn($this->getParameter('version_created_by_column'))) {
            $table->addColumn(array(
                'name' => $this->getParameter('version_created_by_column'),
                'type' => 'VARCHAR',
                'size' => 100
            ));
        }
        if ('true' === $this->getParameter('log_comment') && !$table->hasColumn($this->getParameter('version_comment_column'))) {
            $table->addColumn(array(
                'name' => $this->getParameter('version_comment_column'),
                'type' => 'VARCHAR',
                'size' => 255
            ));
        }
    }

    protected function addVersionTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $versionTableName = $this->getParameter('version_table') ? $this->getParameter('version_table') : ($table->getName() . '_version');
        if (!$database->hasTable($versionTableName)) {
            // create the version table
            $versionTable = $database->addTable(array(
                'name'      => $versionTableName,
                'phpName'   => $this->getVersionTablePhpName(),
                'package'   => $table->getPackage(),
                'schema'    => $table->getSchema(),
                'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
                'skipSql'   => $table->isSkipSql()
            ));
            $versionTable->isVersionTable = true;
            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
            // copy all the columns
            foreach ($table->getColumns() as $column) {
                $columnInVersionTable = clone $column;
                $columnInVersionTable->clearInheritanceList();
                if ($columnInVersionTable->hasReferrers()) {
                    $columnInVersionTable->clearReferrers();
                }
                if ($columnInVersionTable->isAutoincrement()) {
                    $columnInVersionTable->setAutoIncrement(false);
                }
                $versionTable->addColumn($columnInVersionTable);
            }
            // create the foreign key
            $fk = new ForeignKey();
            $fk->setForeignTableCommonName($table->getCommonName());
            $fk->setForeignSchemaName($table->getSchema());
            $fk->setOnDelete('CASCADE');
            $fk->setOnUpdate(null);
            $tablePKs = $table->getPrimaryKey();
            foreach ($versionTable->getPrimaryKey() as $key => $column) {
                $fk->addReference($column, $tablePKs[$key]);
            }
            $versionTable->addForeignKey($fk);

            if ('true' === $this->getParameter('indices')) {
                foreach ($table->getIndices() as $index) {
                    $index = clone $index;
                    $versionTable->addIndex($index);
                }
            }

            // add the version column to the primary key
            $versionColumn = $versionTable->getColumn($this->getParameter('version_column'));
            $versionColumn->setNotNull(true);
            $versionColumn->setPrimaryKey(true);
            $this->versionTable = $versionTable;
        } else {
            $this->versionTable = $database->getTable($versionTableName);
        }
    }

    public function addForeignKeyVersionColumns()
    {
        $versionTable = $this->versionTable;
        foreach ($this->getVersionableFks() as $fk) {
            $fkVersionColumnName = $fk->getLocalColumnName() . '_version';
            if (!$versionTable->hasColumn($fkVersionColumnName)) {
                $versionTable->addColumn(array(
                    'name'    => $fkVersionColumnName,
                    'type'    => 'INTEGER',
                    'default' => 0
                ));
            }
        }

        foreach ($this->getVersionableReferrers() as $fk) {
            $fkTableName = $fk->getTable()->getName();
            $fkIdsColumnName = $fkTableName . '_ids';
            if (!$versionTable->hasColumn($fkIdsColumnName)) {
                $versionTable->addColumn(array(
                    'name'    => $fkIdsColumnName,
                    'type'    => 'ARRAY'
                ));
            }

            $fkVersionsColumnName = $fkTableName . '_versions';
            if (!$versionTable->hasColumn($fkVersionsColumnName)) {
                $versionTable->addColumn(array(
                    'name'    => $fkVersionsColumnName,
                    'type'    => 'ARRAY'
                ));
            }
        }
    }

    public function getVersionTable()
    {
        return $this->versionTable;
    }

    public function getVersionTablePhpName()
    {
        return $this->getTable()->getPhpName() . 'Version';
    }

    public function getVersionableFks()
    {
        $versionableFKs = array();
        if ($fks = $this->getTable()->getForeignKeys()) {
            foreach ($fks as $fk) {
                if ($fk->getForeignTable()->hasBehavior($this->getName()) && ! $fk->isComposite()) {
                    $versionableFKs []= $fk;
                }
            }
        }

        return $versionableFKs;
    }

    public function getVersionableReferrers()
    {
        $versionableReferrers = array();
        if ($fks = $this->getTable()->getReferrers()) {
            foreach ($fks as $fk) {
                if ($fk->getTable()->hasBehavior($this->getName()) && ! $fk->isComposite()) {
                    $versionableReferrers []= $fk;
                }
            }
        }

        return $versionableReferrers;
    }

    public function getReferrerIdsColumn(ForeignKey $fk)
    {
        $fkTableName = $fk->getTable()->getName();
        $fkIdsColumnName = $fkTableName . '_ids';

        return $this->versionTable->getColumn($fkIdsColumnName);
    }

    public function getReferrerVersionsColumn(ForeignKey $fk)
    {
        $fkTableName = $fk->getTable()->getName();
        $fkIdsColumnName = $fkTableName . '_versions';

        return $this->versionTable->getColumn($fkIdsColumnName);
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
