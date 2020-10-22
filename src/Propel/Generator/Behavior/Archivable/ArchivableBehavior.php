<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Archivable;

use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Index;

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author Francois Zaninotto
 */
class ArchivableBehavior extends Behavior
{
    /**
     * Default parameters value
     *
     * @var array
     */
    protected $parameters = [
        'archive_table' => '',
        'archive_phpname' => null,
        'archive_class' => '',
        'log_archived_at' => 'true',
        'archived_at_column' => 'archived_at',
        'archive_on_insert' => 'false',
        'archive_on_update' => 'false',
        'archive_on_delete' => 'true',
    ];

    /**
     * @var \Propel\Generator\Model\Table|null
     */
    protected $archiveTable;

    /**
     * @var \Propel\Generator\Behavior\Archivable\ArchivableBehaviorObjectBuilderModifier|null
     */
    protected $objectBuilderModifier;

    /**
     * @var \Propel\Generator\Behavior\Archivable\ArchivableBehaviorQueryBuilderModifier|null
     */
    protected $queryBuilderModifier;

    /**
     * @return void
     */
    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            if ($table->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }
            if (property_exists($table, 'isArchiveTable')) {
                // don't add the behavior to archive tables
                continue;
            }
            $b = clone $this;
            $table->addBehavior($b);
        }
    }

    /**
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function modifyTable()
    {
        if ($this->getParameter('archive_class') && $this->getParameter('archive_table')) {
            throw new InvalidArgumentException('Please set only one of the two parameters "archive_class" and "archive_table".');
        }
        if (!$this->getParameter('archive_class')) {
            $this->addArchiveTable();
        }
    }

    /**
     * @return void
     */
    protected function addArchiveTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $archiveTableName = $this->getParameter('archive_table') ? $this->getParameter('archive_table') : ($this->getTable()->getOriginCommonName() . '_archive');
        if (!$database->hasTable($archiveTableName)) {
            // create the version table
            $archiveTable = $database->addTable([
                'name' => $archiveTableName,
                'phpName' => $this->getParameter('archive_phpname'),
                'package' => $table->getPackage(),
                'schema' => $table->getSchema(),
                'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
                'identifierQuoting' => $table->getIdentifierQuoting(),
            ]);
            $archiveTable->isArchiveTable = true;
            // copy all the columns
            foreach ($table->getColumns() as $column) {
                $columnInArchiveTable = clone $column;
                if ($columnInArchiveTable->hasReferrers()) {
                    $columnInArchiveTable->clearReferrers();
                }
                if ($columnInArchiveTable->isAutoincrement()) {
                    $columnInArchiveTable->setAutoIncrement(false);
                }
                $archiveTable->addColumn($columnInArchiveTable);
            }
            // add archived_at column
            if ($this->getParameter('log_archived_at') == 'true') {
                $archiveTable->addColumn([
                    'name' => $this->getParameter('archived_at_column'),
                    'type' => 'TIMESTAMP',
                ]);
            }
            // do not copy foreign keys
            // copy the indices
            foreach ($table->getIndices() as $index) {
                $copiedIndex = clone $index;
                $archiveTable->addIndex($copiedIndex);
            }
            // copy unique indices to indices
            // see https://github.com/propelorm/Propel/issues/175 for details
            foreach ($table->getUnices() as $unique) {
                $index = new Index();
                $index->setTable($table);
                foreach ($unique->getColumns() as $columnName) {
                    if ($size = $unique->getColumnSize($columnName)) {
                        $index->addColumn(['name' => $columnName, 'size' => $size]);
                    } else {
                        $index->addColumn(['name' => $columnName]);
                    }
                }
                $archiveTable->addIndex($index);
            }
            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
            $this->archiveTable = $archiveTable;
        } else {
            $this->archiveTable = $database->getTable($archiveTableName);
        }
    }

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    public function getArchiveTable()
    {
        return $this->archiveTable;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function getArchiveTablePhpName($builder)
    {
        if ($this->hasArchiveClass()) {
            return $this->getParameter('archive_class');
        }

        return $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($this->getArchiveTable()));
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function getArchiveTableQueryName($builder)
    {
        if ($this->hasArchiveClass()) {
            return $this->getParameter('archive_class') . 'Query';
        }

        return $builder->getClassNameFromBuilder($builder->getNewStubQueryBuilder($this->getArchiveTable()));
    }

    /**
     * @return bool
     */
    public function hasArchiveClass()
    {
        return $this->getParameter('archive_class') ? true : false;
    }

    /**
     * @return \Propel\Generator\Model\Column|null
     */
    public function getArchivedAtColumn()
    {
        if ($this->getArchiveTable() && $this->getParameter('log_archived_at') === 'true') {
            return $this->getArchiveTable()->getColumn($this->getParameter('archived_at_column'));
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isArchiveOnInsert()
    {
        return $this->getParameter('archive_on_insert') === 'true';
    }

    /**
     * @return bool
     */
    public function isArchiveOnUpdate()
    {
        return $this->getParameter('archive_on_update') === 'true';
    }

    /**
     * @return bool
     */
    public function isArchiveOnDelete()
    {
        return $this->getParameter('archive_on_delete') === 'true';
    }

    /**
     * @return $this|\Propel\Generator\Behavior\Archivable\ArchivableBehaviorObjectBuilderModifier
     */
    public function getObjectBuilderModifier()
    {
        if ($this->objectBuilderModifier === null) {
            $this->objectBuilderModifier = new ArchivableBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    /**
     * @return $this|\Propel\Generator\Behavior\Archivable\ArchivableBehaviorQueryBuilderModifier
     */
    public function getQueryBuilderModifier()
    {
        if ($this->queryBuilderModifier === null) {
            $this->queryBuilderModifier = new ArchivableBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }
}
