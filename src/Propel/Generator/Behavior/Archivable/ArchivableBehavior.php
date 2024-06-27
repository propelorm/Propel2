<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Archivable;

use Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior;
use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author Francois Zaninotto
 */
class ArchivableBehavior extends SyncedTableBehavior
{
    /**
     * @see \Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior::DEFAULT_SYNCED_TABLE_SUFFIX
     *
     * @var string DEFAULT_SYNCED_TABLE_SUFFIX
     */
    protected const DEFAULT_SYNCED_TABLE_SUFFIX = '_archive';

    /**
     * @see \Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior::PARAMETER_KEY_SYNCED_TABLE
     *
     * @var string
     */
    public const PARAMETER_KEY_SYNCED_TABLE = 'archive_table';

    /**
     * @see \Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior::PARAMETER_KEY_SYNCED_PHPNAME
     *
     * @var string
     */
    public const PARAMETER_KEY_SYNCED_PHPNAME = 'archive_phpname';

    /**
     * @var \Propel\Generator\Behavior\Archivable\ArchivableBehaviorObjectBuilderModifier|null
     */
    protected $objectBuilderModifier;

    /**
     * @var \Propel\Generator\Behavior\Archivable\ArchivableBehaviorQueryBuilderModifier|null
     */
    protected $queryBuilderModifier;

    /**
     * @see \Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior::getDefaultParameters()
     *
     * @return array
     */
    protected function getDefaultParameters(): array
    {
        return [
            static::PARAMETER_KEY_SYNCED_TABLE => '',
            static::PARAMETER_KEY_SYNCED_PHPNAME => null,
            'archive_class' => '',
            static::PARAMETER_KEY_SYNC => 'false',
            static::PARAMETER_KEY_INHERIT_FOREIGN_KEY_RELATIONS => 'false',
            static::PARAMETER_KEY_INHERIT_FOREIGN_KEY_CONSTRAINTS => 'false',
            static::PARAMETER_KEY_FOREIGN_KEYS => null,
            static::PARAMETER_KEY_SYNC_INDEXES => 'true',
            static::PARAMETER_KEY_SYNC_UNIQUE_AS => null,
            static::PARAMETER_KEY_EMPTY_ACCESSOR_COLUMNS => 'true',
            'log_archived_at' => 'true',
            'archived_at_column' => 'archived_at',
            'archive_on_insert' => 'false',
            'archive_on_update' => 'false',
            'archive_on_delete' => 'true',
        ];
    }

    /**
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return void
     */
    public function modifyTable(): void
    {
        if ($this->getParameter('archive_class') && $this->getParameter(static::PARAMETER_KEY_SYNCED_TABLE)) {
            throw new InvalidArgumentException('Please set only one of the two parameters "archive_class" and "archive_table".');
        }
        if (!$this->getParameter('archive_class')) {
            parent::modifyTable();
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $syncedTable
     * @param bool $tableExistsInSchema
     *
     * @return void
     */
    public function addTableElements(Table $syncedTable, $tableExistsInSchema): void
    {
        parent::addTableElements($syncedTable, $tableExistsInSchema);
        $this->addCustomColumnsToSyncedTable($syncedTable);
    }

    /**
     * @see \Propel\Generator\Behavior\SyncedTable\SyncedTableBehavior::addCustomColumnsToSyncedTable()
     *
     * @param \Propel\Generator\Model\Table $syncedTable
     *
     * @return void
     */
    protected function addCustomColumnsToSyncedTable(Table $syncedTable)
    {
        if ($this->parameterHasValue('log_archived_at', 'true')) {
            $this->addColumnFromParameterIfNotExists($syncedTable, 'archived_at_column', ['type' => 'TIMESTAMP']);
        }
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function getArchiveTablePhpName(AbstractOMBuilder $builder): string
    {
        if ($this->hasArchiveClass()) {
            return $this->getParameter('archive_class');
        }

        $archiveTable = $this->getSyncedTable();
        $tableStub = $builder->getNewStubObjectBuilder($archiveTable);

        return $builder->getClassNameFromBuilder($tableStub);
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function getArchiveTableQueryName(AbstractOMBuilder $builder): string
    {
        if ($this->hasArchiveClass()) {
            return $this->getParameter('archive_class') . 'Query';
        }

        return $builder->getClassNameFromBuilder($builder->getNewStubQueryBuilder($this->getSyncedTable()));
    }

    /**
     * @return bool
     */
    public function hasArchiveClass(): bool
    {
        return (bool)$this->getParameter('archive_class');
    }

    /**
     * @return \Propel\Generator\Model\Column|null
     */
    public function getArchivedAtColumn(): ?Column
    {
        if ($this->getSyncedTable() && $this->getParameter('log_archived_at') === 'true') {
            return $this->getSyncedTable()->getColumn($this->getParameter('archived_at_column'));
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isArchiveOnInsert(): bool
    {
        return $this->getParameter('archive_on_insert') === 'true';
    }

    /**
     * @return bool
     */
    public function isArchiveOnUpdate(): bool
    {
        return $this->getParameter('archive_on_update') === 'true';
    }

    /**
     * @return bool
     */
    public function isArchiveOnDelete(): bool
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
