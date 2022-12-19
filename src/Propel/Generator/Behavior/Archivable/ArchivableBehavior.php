<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Archivable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Platform\SqlitePlatform;

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
     * @var array<string, mixed>
     */
    protected $parameters = [
        'archive_table' => '',
        'archive_phpname' => null,
        'archive_class' => '',
        'sync' => 'false',
        'inherit_foreign_key_relations' => 'false',
        'inherit_foreign_key_constraints' => 'false',
        'foreign_keys' => null,
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
    public function modifyDatabase(): void
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            if ($table->hasBehavior($this->getId())) {
                // don't add the same behavior twice
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
    public function modifyTable(): void
    {
        if ($this->getParameter('archive_class') && $this->getParameter('archive_table')) {
            throw new InvalidArgumentException('Please set only one of the two parameters "archive_class" and "archive_table".');
        }
        if (!$this->getParameter('archive_class')) {
            $this->addArchiveTable();
        }
    }

    /**
     * @return string
     */
    protected function getArchiveTableName(): string
    {
        return $this->getParameter('archive_table') ?: ($this->getTable()->getOriginCommonName() . '_archive');
    }

    /**
     * @return void
     */
    protected function addArchiveTable(): void
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $archiveTableName = $this->getArchiveTableName();

        $archiveTableExistsInSchema = $database->hasTable($archiveTableName);

        $this->archiveTable = $archiveTableExistsInSchema ?
            $database->getTable($archiveTableName) :
            $this->createArchiveTable();

        if ($archiveTableExistsInSchema && !$this->parameterHasValue('sync', 'true')) {
            return;
        }

        $this->syncTables();
    }

    /**
     * @return \Propel\Generator\Model\Table
     */
    protected function createArchiveTable(): Table
    {
        $sourceTable = $this->getTable();
        $database = $sourceTable->getDatabase();

        // create the version table
        return $database->addTable([
            'name' => $this->getArchiveTableName(),
            'phpName' => $this->getParameter('archive_phpname'),
            'package' => $sourceTable->getPackage(),
            'schema' => $sourceTable->getSchema(),
            'namespace' => $sourceTable->getNamespace() ? '\\' . $sourceTable->getNamespace() : null,
            'identifierQuoting' => $sourceTable->isIdentifierQuotingEnabled(),
        ]);
    }

    /**
     * @return \Propel\Generator\Model\Table
     */
    protected function syncTables(): Table
    {
        $archiveTable = $this->getArchiveTable();
        $sourceTable = $this->getTable();
        $database = $sourceTable->getDatabase();
        $platform = $database->getPlatform();

        // copy all the columns
        foreach ($sourceTable->getColumns() as $sourceColumn) {
            if ($archiveTable->hasColumn($sourceColumn)) {
                continue;
            }
            $archiveColumn = clone $sourceColumn;
            $archiveColumn->clearReferrers();
            $archiveColumn->setAutoIncrement(false);
            $archiveTable->addColumn($archiveColumn);
        }

        // add archived_at column
        if (
            $this->parameterHasValue('log_archived_at', 'true') &&
            !$archiveTable->hasColumn($this->getParameter('archived_at_column'))
        ) {
            $archiveTable->addColumn([
                'name' => $this->getParameter('archived_at_column'),
                'type' => 'TIMESTAMP',
            ]);
        }

        // add fks declared on behavior
        $foreignKeys = $this->getParameter('foreign_keys');
        if ($foreignKeys) {
            foreach ($foreignKeys as $fkData) {
                $this->createForeignKeyFromParameters($archiveTable, $fkData);
            }
        }

        // copy foreign keys if enabled in parameters
        if (
            $this->parameterHasValue('inherit_foreign_key_relations', 'true') ||
            $this->parameterHasValue('inherit_foreign_key_constraints', 'true')
        ) {
            foreach ($sourceTable->getForeignKeys() as $foreignKey) {
                if ($archiveTable->containsForeignKeyWithSameName($foreignKey)) {
                    continue;
                }
                $copiedForeignKey = clone $foreignKey;
                $archiveTable->addForeignKey($copiedForeignKey);

                $createConstraint = $this->parameterHasValue('inherit_foreign_key_constraints', 'true');
                $copiedForeignKey->setSkipSql(!$createConstraint);
            }
        }

        // copy the indices
        foreach ($sourceTable->getIndices() as $index) {
            $copiedIndex = clone $index;
            if ($this->isDistinctiveIndexNameRequired($platform)) {
                // by unsetting the name, Propel will generate a unique name based on table and columns
                $copiedIndex->setName(null);
            }
            if ($archiveTable->hasIndex($index->getName())) {
                continue;
            }
            $archiveTable->addIndex($copiedIndex);
        }
        // create unique indexes as regular indexes (unique columns in source table can be archived multiple times)
        foreach ($sourceTable->getUnices() as $unique) {
            $index = new Index();
            $index->setTable($archiveTable);
            foreach ($unique->getColumns() as $columnName) {
                if ($size = $unique->getColumnSize($columnName)) {
                    $index->addColumn(['name' => $columnName, 'size' => $size]);
                } else {
                    $index->addColumn(['name' => $columnName]);
                }
            }

            if ($archiveTable->hasIndex($index->getName())) {
                continue;
            }
            $archiveTable->addIndex($index);
        }
        // every behavior adding a table should re-execute database behaviors
        foreach ($database->getBehaviors() as $behavior) {
            if ($behavior instanceof ArchivableBehavior) {
                continue;
            }
            $behavior->modifyDatabase();
        }

        return $archiveTable;
    }

    /**
     * @psalm-param array{local_column: string, foreign_table: string, foreign_column: string, relation_only?: string} $fkParameterData
     *
     * @param \Propel\Generator\Model\Table $table
     * @param array $fkParameterData
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    protected function createForeignKeyFromParameters(Table $table, array $fkParameterData): void
    {
        if (
            empty($fkParameterData['localColumn']) ||
            empty($fkParameterData['foreignColumn'])
        ) {
            $tableName = $this->table->getName();

            throw new SchemaException("Table `$tableName`: Archivable behavior misses foreign key parameters. Please supply `localColumn`, `foreignTable` and `foreignColumn` for every entry");
        }
        $fk = new ForeignKey($fkParameterData['name'] ?? null);
        $fk->addReference($fkParameterData['localColumn'], $fkParameterData['foreignColumn']);
        $table->addForeignKey($fk);
        $fk->loadMapping($fkParameterData);
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform
     *
     * @return bool
     */
    protected function isDistinctiveIndexNameRequired(?PlatformInterface $platform): bool
    {
        return $platform instanceof PgsqlPlatform || $platform instanceof SqlitePlatform;
    }

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    public function getArchiveTable(): ?Table
    {
        return $this->archiveTable;
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

        $archiveTable = $this->getArchiveTable();
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

        return $builder->getClassNameFromBuilder($builder->getNewStubQueryBuilder($this->getArchiveTable()));
    }

    /**
     * @return bool
     */
    public function hasArchiveClass(): bool
    {
        return $this->getParameter('archive_class') ? true : false;
    }

    /**
     * @return \Propel\Generator\Model\Column|null
     */
    public function getArchivedAtColumn(): ?Column
    {
        if ($this->getArchiveTable() && $this->getParameter('log_archived_at') === 'true') {
            return $this->getArchiveTable()->getColumn($this->getParameter('archived_at_column'));
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
