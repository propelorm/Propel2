<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Platform;

use Propel\Common\Util\SetColumnConverter;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Runtime\Connection\ConnectionInterface;
use ReflectionClass;

/**
 * Default implementation for the PlatformInterface interface.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class DefaultPlatform implements PlatformInterface
{
    /**
     * Mapping from Propel types to Domain objects.
     *
     * @var \Propel\Generator\Model\Domain[]
     */
    protected $schemaDomainMap;

    /**
     * The database connection.
     *
     * @var \Propel\Runtime\Connection\ConnectionInterface Database connection.
     */
    protected $con;

    /**
     * @var bool
     */
    protected $identifierQuoting = true;

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con Optional database connection to use in this platform.
     */
    public function __construct(?ConnectionInterface $con = null)
    {
        if ($con !== null) {
            $this->setConnection($con);
        }

        $this->initialize();
    }

    /**
     * Returns the object builder class.
     *
     * @param string $type
     *
     * @return string
     */
    public function getObjectBuilderClass($type)
    {
        return '';
    }

    /**
     * Sets the database connection to use for this Platform class.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con Database connection to use in this platform.
     *
     * @return void
     */
    public function setConnection(?ConnectionInterface $con = null)
    {
        $this->con = $con;
    }

    /**
     * Returns the database connection to use for this Platform class.
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->con;
    }

    /**
     * @return bool
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param bool $enabled
     *
     * @return void
     */
    public function setIdentifierQuoting($enabled)
    {
        $this->identifierQuoting = $enabled;
    }

    /**
     * Sets the GeneratorConfigInterface to use in the parsing.
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
    }

    /**
     * Initialize the type -> Domain mapping.
     *
     * @return void
     */
    protected function initialize()
    {
        $this->schemaDomainMap = [];
        foreach (PropelTypes::getPropelTypes() as $type) {
            $this->schemaDomainMap[$type] = new Domain($type);
        }
        // BU_* no longer needed, so map these to the DATE/TIMESTAMP domains
        $this->schemaDomainMap[PropelTypes::BU_DATE] = new Domain(PropelTypes::DATE);
        $this->schemaDomainMap[PropelTypes::BU_TIMESTAMP] = new Domain(PropelTypes::TIMESTAMP);

        // Boolean is a bit special, since typically it must be mapped to INT type.
        $this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain(PropelTypes::BOOLEAN, 'INTEGER');
    }

    /**
     * Adds a mapping entry for specified Domain.
     *
     * @param \Propel\Generator\Model\Domain $domain
     *
     * @return void
     */
    protected function setSchemaDomainMapping(Domain $domain)
    {
        $this->schemaDomainMap[$domain->getType()] = $domain;
    }

    /**
     * Returns the short name of the database type that this platform represents.
     * For example MysqlPlatform->getDatabaseType() returns 'mysql'.
     *
     * @return string
     */
    public function getDatabaseType()
    {
        $reflClass = new ReflectionClass($this);
        $clazz = $reflClass->getShortName();
        $pos = strpos($clazz, 'Platform');

        return strtolower(substr($clazz, 0, $pos));
    }

    /**
     * Returns the max column length supported by the db.
     *
     * @return int The max column length
     */
    public function getMaxColumnNameLength()
    {
        return 64;
    }

    /**
     * @return string
     */
    public function getSchemaDelimiter()
    {
        return '.';
    }

    /**
     * Returns the native IdMethod (sequence|identity)
     *
     * @return string The native IdMethod (PlatformInterface:IDENTITY, PlatformInterface::SEQUENCE).
     */
    public function getNativeIdMethod()
    {
        return PlatformInterface::IDENTITY;
    }

    /**
     * @return bool
     */
    public function isNativeIdMethodAutoIncrement()
    {
        return $this->getNativeIdMethod() === PlatformInterface::IDENTITY;
    }

    /**
     * Returns the database specific domain for a mapping type.
     *
     * @param string $mappingType
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return \Propel\Generator\Model\Domain
     */
    public function getDomainForType($mappingType)
    {
        if (!isset($this->schemaDomainMap[$mappingType])) {
            throw new EngineException(sprintf('Cannot map unknown Propel type %s to native database type.', var_export($mappingType, true)));
        }

        return $this->schemaDomainMap[$mappingType];
    }

    /**
     * Returns the NOT NULL string for the configured RDBMS.
     *
     * @param bool $notNull
     *
     * @return string
     */
    public function getNullString($notNull)
    {
        return $notNull ? 'NOT NULL' : '';
    }

    /**
     * Returns the auto increment strategy for the configured RDBMS.
     *
     * @return string
     */
    public function getAutoIncrement()
    {
        return 'IDENTITY';
    }

    /**
     * Returns the name to use for creating a table sequence.
     *
     * This will create a new name or use one specified in an
     * id-method-parameter tag, if specified.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getSequenceName(Table $table)
    {
        static $longNamesMap = [];
        $result = null;
        if ($table->getIdMethod() === IdMethod::NATIVE) {
            $idMethodParams = $table->getIdMethodParameters();
            $maxIdentifierLength = $this->getMaxColumnNameLength();
            if (empty($idMethodParams)) {
                if (strlen($table->getName() . '_SEQ') > $maxIdentifierLength) {
                    if (!isset($longNamesMap[$table->getName()])) {
                        $longNamesMap[$table->getName()] = (string)(count($longNamesMap) + 1);
                    }
                    $result = substr($table->getName(), 0, $maxIdentifierLength - strlen('_SEQ_' . $longNamesMap[$table->getName()])) . '_SEQ_' . $longNamesMap[$table->getName()];
                } else {
                    $result = substr($table->getName(), 0, $maxIdentifierLength - 4) . '_SEQ';
                }
            } else {
                $result = substr($idMethodParams[0]->getValue(), 0, $maxIdentifierLength);
            }
        }

        return $result;
    }

    /**
     * Returns the DDL SQL to add the tables of a database
     * together with index and foreign keys
     *
     * @param \Propel\Generator\Model\Database $database
     *
     * @return string
     */
    public function getAddTablesDDL(Database $database)
    {
        $ret = $this->getBeginDDL();
        foreach ($database->getTablesForSql() as $table) {
            $this->normalizeTable($table);
        }
        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getCommentBlockDDL($table->getName());
            $ret .= $this->getDropTableDDL($table);
            $ret .= $this->getAddTableDDL($table);
            $ret .= $this->getAddIndicesDDL($table);
            $ret .= $this->getAddForeignKeysDDL($table);
        }
        $ret .= $this->getEndDDL();

        return $ret;
    }

    /**
     * Gets the requests to execute at the beginning of a DDL file
     *
     * @return string
     */
    public function getBeginDDL()
    {
        return '';
    }

    /**
     * Gets the requests to execute at the end of a DDL file
     *
     * @return string
     */
    public function getEndDDL()
    {
        return '';
    }

    /**
     * Builds the DDL SQL to drop a table
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getDropTableDDL(Table $table)
    {
        return "
DROP TABLE IF EXISTS " . $this->quoteIdentifier($table->getName()) . ";
";
    }

    /**
     * Builds the DDL SQL to add a table
     * without index and foreign keys
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddTableDDL(Table $table)
    {
        $tableDescription = $table->hasDescription() ? $this->getCommentLineDDL($table->getDescription()) : '';

        $lines = [];

        foreach ($table->getColumns() as $column) {
            $lines[] = $this->getColumnDDL($column);
        }

        if ($table->hasPrimaryKey()) {
            $lines[] = $this->getPrimaryKeyDDL($table);
        }

        foreach ($table->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        $sep = ",
    ";

        $pattern = "
%sCREATE TABLE %s
(
    %s
);
";

        return sprintf(
            $pattern,
            $tableDescription,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    /**
     * Builds the DDL SQL for a Column object.
     *
     * @param \Propel\Generator\Model\Column $col
     *
     * @return string
     */
    public function getColumnDDL(Column $col)
    {
        $domain = $col->getDomain();

        $ddl = [$this->quoteIdentifier($col->getName())];
        $sqlType = $domain->getSqlType();
        if ($this->hasSize($sqlType) && $col->isDefaultSqlType($this)) {
            $ddl[] = $sqlType . $col->getSizeDefinition();
        } else {
            $ddl[] = $sqlType;
        }
        if ($default = $this->getColumnDefaultValueDDL($col)) {
            $ddl[] = $default;
        }
        if ($notNull = $this->getNullString($col->isNotNull())) {
            $ddl[] = $notNull;
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }

        return implode(' ', $ddl);
    }

    /**
     * Returns the SQL for the default value of a Column object
     *
     * @param \Propel\Generator\Model\Column $col
     *
     * @return string
     */
    public function getColumnDefaultValueDDL(Column $col)
    {
        $default = '';
        $defaultValue = $col->getDefaultValue();
        if ($defaultValue !== null) {
            $default .= 'DEFAULT ';
            if ($defaultValue->isExpression()) {
                $default .= $defaultValue->getValue();
            } else {
                if ($col->isTextType()) {
                    $default .= $this->quote($defaultValue->getValue());
                } elseif (in_array($col->getType(), [PropelTypes::BOOLEAN, PropelTypes::BOOLEAN_EMU])) {
                    $default .= $this->getBooleanString($defaultValue->getValue());
                } elseif ($col->getType() == PropelTypes::ENUM) {
                    $default .= array_search($defaultValue->getValue(), $col->getValueSet());
                } elseif ($col->isSetType()) {
                    $val = trim($defaultValue->getValue());
                    $values = [];
                    foreach (explode(',', $val) as $v) {
                        $values[] = trim($v);
                    }
                    $default .= SetColumnConverter::convertToInt($values, $col->getValueSet());
                } elseif ($col->isPhpArrayType()) {
                    $value = $this->getPhpArrayString($defaultValue->getValue());
                    if ($value === null) {
                        $default = '';
                    } else {
                        $default .= $value;
                    }
                } else {
                    $default .= $defaultValue->getValue();
                }
            }
        }

        return $default;
    }

    /**
     * Creates a delimiter-delimited string list of column names, quoted using quoteIdentifier().
     *
     * @example
     * <code>
     * echo $platform->getColumnListDDL(array('foo', 'bar');
     * // '"foo","bar"'
     * </code>
     *
     * @param \Propel\Generator\Model\Column[] $columns
     * @param string $delimiter The delimiter to use in separating the column names.
     *
     * @return string
     */
    public function getColumnListDDL($columns, $delimiter = ',')
    {
        $list = [];
        foreach ($columns as $column) {
            $columnName = $column->getName();
            $list[] = $this->quoteIdentifier($columnName);
        }

        return implode($delimiter, $list);
    }

    /**
     * Returns the name of a table primary key.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getPrimaryKeyName(Table $table)
    {
        $tableName = $table->getCommonName();

        return $tableName . '_pk';
    }

    /**
     * Returns the SQL for the primary key of a Table object.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getPrimaryKeyDDL(Table $table)
    {
        if ($table->hasPrimaryKey()) {
            return 'PRIMARY KEY (' . $this->getColumnListDDL($table->getPrimaryKey()) . ')';
        }

        return '';
    }

    /**
     * Returns the DDL SQL to drop the primary key of a table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getDropPrimaryKeyDDL(Table $table)
    {
        if (!$table->hasPrimaryKey()) {
            return '';
        }

        $pattern = "
ALTER TABLE %s DROP CONSTRAINT %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName()),
            $this->quoteIdentifier($this->getPrimaryKeyName($table))
        );
    }

    /**
     * Returns the DDL SQL to add the primary key of a table.
     *
     * @param \Propel\Generator\Model\Table $table From Table
     *
     * @return string
     */
    public function getAddPrimaryKeyDDL(Table $table)
    {
        if (!$table->hasPrimaryKey()) {
            return '';
        }

        $pattern = "
ALTER TABLE %s ADD %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName()),
            $this->getPrimaryKeyDDL($table)
        );
    }

    /**
     * Returns the DDL SQL to add the indices of a table.
     *
     * @param \Propel\Generator\Model\Table $table To Table
     *
     * @return string
     */
    public function getAddIndicesDDL(Table $table)
    {
        $ret = '';
        foreach ($table->getIndices() as $fk) {
            $ret .= $this->getAddIndexDDL($fk);
        }

        return $ret;
    }

    /**
     * Returns the DDL SQL to add an Index.
     *
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    public function getAddIndexDDL(Index $index)
    {
        $pattern = "
CREATE %sINDEX %s ON %s (%s);
";

        return sprintf(
            $pattern,
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTable()->getName()),
            $this->getColumnListDDL($index->getColumnObjects())
        );
    }

    /**
     * Builds the DDL SQL to drop an Index.
     *
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    public function getDropIndexDDL(Index $index)
    {
        $pattern = "
DROP INDEX %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($index->getFQName())
        );
    }

    /**
     * Builds the DDL SQL for an Index object.
     *
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    public function getIndexDDL(Index $index)
    {
        return sprintf(
            '%sINDEX %s (%s)',
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($index->getName()),
            $this->getColumnListDDL($index->getColumnObjects())
        );
    }

    /**
     * Builds the DDL SQL for a Unique constraint object.
     *
     * @param \Propel\Generator\Model\Unique $unique
     *
     * @return string
     */
    public function getUniqueDDL(Unique $unique)
    {
        return sprintf('UNIQUE (%s)', $this->getColumnListDDL($unique->getColumnObjects()));
    }

    /**
     * Builds the DDL SQL to add the foreign keys of a table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddForeignKeysDDL(Table $table)
    {
        $ret = '';
        foreach ($table->getForeignKeys() as $fk) {
            $ret .= $this->getAddForeignKeyDDL($fk);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to add a foreign key.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string
     */
    public function getAddForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return '';
        }
        $pattern = "
ALTER TABLE %s ADD %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fk->getTable()->getName()),
            $this->getForeignKeyDDL($fk)
        );
    }

    /**
     * Builds the DDL SQL to drop a foreign key.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string|null
     */
    public function getDropForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return null;
        }
        $pattern = "
ALTER TABLE %s DROP CONSTRAINT %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fk->getTable()->getName()),
            $this->quoteIdentifier($fk->getName())
        );
    }

    /**
     * Builds the DDL SQL for a ForeignKey object.
     *
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string
     */
    public function getForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return '';
        }

        $pattern = "CONSTRAINT %s
    FOREIGN KEY (%s)
    REFERENCES %s (%s)";
        $script = sprintf(
            $pattern,
            $this->quoteIdentifier($fk->getName()),
            $this->getColumnListDDL($fk->getLocalColumnObjects()),
            $this->quoteIdentifier($fk->getForeignTableName()),
            $this->getColumnListDDL($fk->getForeignColumnObjects())
        );
        if ($fk->hasOnUpdate()) {
            $script .= "
    ON UPDATE " . $fk->getOnUpdate();
        }
        if ($fk->hasOnDelete()) {
            $script .= "
    ON DELETE " . $fk->getOnDelete();
        }

        return $script;
    }

    /**
     * @param string $comment
     *
     * @return string
     */
    public function getCommentLineDDL($comment)
    {
        $pattern = "-- %s
";

        return sprintf($pattern, $comment);
    }

    /**
     * @param string $comment
     *
     * @return string
     */
    public function getCommentBlockDDL($comment)
    {
        $pattern = "
-----------------------------------------------------------------------
-- %s
-----------------------------------------------------------------------
";

        return sprintf($pattern, $comment);
    }

    /**
     * Builds the DDL SQL to modify a database
     * based on a DatabaseDiff instance
     *
     * @param \Propel\Generator\Model\Diff\DatabaseDiff $databaseDiff
     *
     * @return string
     */
    public function getModifyDatabaseDDL(DatabaseDiff $databaseDiff)
    {
        $ret = '';
        foreach ($databaseDiff->getRemovedTables() as $table) {
            $ret .= $this->getDropTableDDL($table);
        }

        foreach ($databaseDiff->getRenamedTables() as $fromTableName => $toTableName) {
            $ret .= $this->getRenameTableDDL($fromTableName, $toTableName);
        }

        foreach ($databaseDiff->getAddedTables() as $table) {
            $ret .= $this->getAddTableDDL($table);
            $ret .= $this->getAddIndicesDDL($table);
        }

        foreach ($databaseDiff->getModifiedTables() as $tableDiff) {
            $ret .= $this->getModifyTableDDL($tableDiff);
        }

        foreach ($databaseDiff->getAddedTables() as $table) {
            $ret .= $this->getAddForeignKeysDDL($table);
        }

        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to rename a table
     *
     * @param string $fromTableName
     * @param string $toTableName
     *
     * @return string
     */
    public function getRenameTableDDL($fromTableName, $toTableName)
    {
        $pattern = "
ALTER TABLE %s RENAME TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fromTableName),
            $this->quoteIdentifier($toTableName)
        );
    }

    /**
     * Builds the DDL SQL to alter a table
     * based on a TableDiff instance
     *
     * @param \Propel\Generator\Model\Diff\TableDiff $tableDiff
     *
     * @return string
     */
    public function getModifyTableDDL(TableDiff $tableDiff)
    {
        $ret = '';

        $toTable = $tableDiff->getToTable();

        // drop indices, foreign keys
        foreach ($tableDiff->getRemovedFks() as $fk) {
            $ret .= $this->getDropForeignKeyDDL($fk);
        }
        foreach ($tableDiff->getModifiedFks() as $fkModification) {
            [$fromFk] = $fkModification;
            $ret .= $this->getDropForeignKeyDDL($fromFk);
        }
        foreach ($tableDiff->getRemovedIndices() as $index) {
            $ret .= $this->getDropIndexDDL($index);
        }
        foreach ($tableDiff->getModifiedIndices() as $indexModification) {
            [$fromIndex] = $indexModification;
            $ret .= $this->getDropIndexDDL($fromIndex);
        }

        $columnChangeString = '';

        // alter table structure
        if ($tableDiff->hasModifiedPk()) {
            $columnChangeString .= $this->getDropPrimaryKeyDDL($tableDiff->getFromTable());
        }
        foreach ($tableDiff->getRenamedColumns() as $columnRenaming) {
            $columnChangeString .= $this->getRenameColumnDDL($columnRenaming[0], $columnRenaming[1]);
        }
        if ($modifiedColumns = $tableDiff->getModifiedColumns()) {
            $columnChangeString .= $this->getModifyColumnsDDL($modifiedColumns);
        }
        if ($addedColumns = $tableDiff->getAddedColumns()) {
            $columnChangeString .= $this->getAddColumnsDDL($addedColumns);
        }
        foreach ($tableDiff->getRemovedColumns() as $column) {
            $columnChangeString .= $this->getRemoveColumnDDL($column);
        }

        // add new indices and foreign keys
        if ($tableDiff->hasModifiedPk()) {
            $columnChangeString .= $this->getAddPrimaryKeyDDL($tableDiff->getToTable());
        }

        if ($columnChangeString) {
            //merge column changes into one command. This is more compatible especially with PK constraints.

            $changes = explode(';', $columnChangeString);
            $columnChanges = [];

            foreach ($changes as $change) {
                if (!trim($change)) {
                    continue;
                }
                $isCompatibleCall = preg_match(
                    sprintf('/ALTER TABLE %s (?!RENAME)/', $this->quoteIdentifier($toTable->getName())),
                    $change
                );
                if ($isCompatibleCall) {
                    $columnChanges[] = preg_replace(
                        sprintf('/ALTER TABLE %s /', $this->quoteIdentifier($toTable->getName())),
                        "\n\n  ",
                        trim($change)
                    );
                } else {
                    $ret .= $change . ";\n";
                }
            }

            if (0 < count($columnChanges)) {
                $ret .= sprintf(
                    "
ALTER TABLE %s%s;
",
                    $this->quoteIdentifier($toTable->getName()),
                    implode(',', $columnChanges)
                );
            }
        }

        // create indices, foreign keys
        foreach ($tableDiff->getModifiedIndices() as $indexModification) {
            [$oldIndex, $toIndex] = $indexModification;
            $ret .= $this->getAddIndexDDL($toIndex);
        }
        foreach ($tableDiff->getAddedIndices() as $index) {
            $ret .= $this->getAddIndexDDL($index);
        }
        foreach ($tableDiff->getModifiedFks() as $fkModification) {
            [, $toFk] = $fkModification;
            $ret .= $this->getAddForeignKeyDDL($toFk);
        }
        foreach ($tableDiff->getAddedFks() as $fk) {
            $ret .= $this->getAddForeignKeyDDL($fk);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a table
     * based on a TableDiff instance
     *
     * @param \Propel\Generator\Model\Diff\TableDiff $tableDiff
     *
     * @return string
     */
    public function getModifyTableColumnsDDL(TableDiff $tableDiff)
    {
        $ret = '';

        foreach ($tableDiff->getRemovedColumns() as $column) {
            $ret .= $this->getRemoveColumnDDL($column);
        }

        foreach ($tableDiff->getRenamedColumns() as $columnRenaming) {
            $ret .= $this->getRenameColumnDDL($columnRenaming[0], $columnRenaming[1]);
        }

        if ($modifiedColumns = $tableDiff->getModifiedColumns()) {
            $ret .= $this->getModifyColumnsDDL($modifiedColumns);
        }

        if ($addedColumns = $tableDiff->getAddedColumns()) {
            $ret .= $this->getAddColumnsDDL($addedColumns);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a table's primary key
     * based on a TableDiff instance
     *
     * @param \Propel\Generator\Model\Diff\TableDiff $tableDiff
     *
     * @return string
     */
    public function getModifyTablePrimaryKeyDDL(TableDiff $tableDiff)
    {
        $ret = '';

        if ($tableDiff->hasModifiedPk()) {
            $ret .= $this->getDropPrimaryKeyDDL($tableDiff->getFromTable());
            $ret .= $this->getAddPrimaryKeyDDL($tableDiff->getToTable());
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a table's indices
     * based on a TableDiff instance
     *
     * @param \Propel\Generator\Model\Diff\TableDiff $tableDiff
     *
     * @return string
     */
    public function getModifyTableIndicesDDL(TableDiff $tableDiff)
    {
        $ret = '';

        foreach ($tableDiff->getRemovedIndices() as $index) {
            $ret .= $this->getDropIndexDDL($index);
        }

        foreach ($tableDiff->getAddedIndices() as $index) {
            $ret .= $this->getAddIndexDDL($index);
        }

        foreach ($tableDiff->getModifiedIndices() as $indexModification) {
            [$fromIndex, $toIndex] = $indexModification;
            $ret .= $this->getDropIndexDDL($fromIndex);
            $ret .= $this->getAddIndexDDL($toIndex);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to alter a table's foreign keys
     * based on a TableDiff instance
     *
     * @param \Propel\Generator\Model\Diff\TableDiff $tableDiff
     *
     * @return string
     */
    public function getModifyTableForeignKeysDDL(TableDiff $tableDiff)
    {
        $ret = '';

        foreach ($tableDiff->getRemovedFks() as $fk) {
            $ret .= $this->getDropForeignKeyDDL($fk);
        }

        foreach ($tableDiff->getAddedFks() as $fk) {
            $ret .= $this->getAddForeignKeyDDL($fk);
        }

        foreach ($tableDiff->getModifiedFks() as $fkModification) {
            [$fromFk, $toFk] = $fkModification;
            $ret .= $this->getDropForeignKeyDDL($fromFk);
            $ret .= $this->getAddForeignKeyDDL($toFk);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to remove a column
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    public function getRemoveColumnDDL(Column $column)
    {
        $pattern = "
ALTER TABLE %s DROP COLUMN %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($column->getTable()->getName()),
            $this->quoteIdentifier($column->getName())
        );
    }

    /**
     * Builds the DDL SQL to rename a column
     *
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return string
     */
    public function getRenameColumnDDL(Column $fromColumn, Column $toColumn)
    {
        $pattern = "
ALTER TABLE %s RENAME COLUMN %s TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fromColumn->getTable()->getName()),
            $this->quoteIdentifier($fromColumn->getName()),
            $this->quoteIdentifier($toColumn->getName())
        );
    }

    /**
     * Builds the DDL SQL to modify a column
     *
     * @param \Propel\Generator\Model\Diff\ColumnDiff $columnDiff
     *
     * @return string
     */
    public function getModifyColumnDDL(ColumnDiff $columnDiff)
    {
        $toColumn = $columnDiff->getToColumn();
        $pattern = "
ALTER TABLE %s MODIFY %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($toColumn->getTable()->getName()),
            $this->getColumnDDL($toColumn)
        );
    }

    /**
     * Builds the DDL SQL to modify a list of columns
     *
     * @param \Propel\Generator\Model\Diff\ColumnDiff[] $columnDiffs
     *
     * @return string
     */
    public function getModifyColumnsDDL($columnDiffs)
    {
        $lines = [];
        $table = null;
        foreach ($columnDiffs as $columnDiff) {
            $toColumn = $columnDiff->getToColumn();
            if ($table === null) {
                $table = $toColumn->getTable();
            }
            $lines[] = $this->getColumnDDL($toColumn);
        }

        $sep = ",
    ";

        $pattern = "
ALTER TABLE %s MODIFY
(
    %s
);
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    /**
     * Builds the DDL SQL to remove a column
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    public function getAddColumnDDL(Column $column)
    {
        $pattern = "
ALTER TABLE %s ADD %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($column->getTable()->getName()),
            $this->getColumnDDL($column)
        );
    }

    /**
     * Builds the DDL SQL to remove a list of columns
     *
     * @param \Propel\Generator\Model\Column[] $columns
     *
     * @return string
     */
    public function getAddColumnsDDL($columns)
    {
        $lines = [];
        $table = null;
        foreach ($columns as $column) {
            if ($table === null) {
                $table = $column->getTable();
            }
            $lines[] = $this->getColumnDDL($column);
        }

        $sep = ",
    ";

        $pattern = "
ALTER TABLE %s ADD
(
    %s
);
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    /**
     * Returns if the RDBMS-specific SQL type has a size attribute.
     *
     * @param string $sqlType the SQL type
     *
     * @return bool True if the type has a size attribute
     */
    public function hasSize($sqlType)
    {
        return true;
    }

    /**
     * Returns if the RDBMS-specific SQL type has a scale attribute.
     *
     * @param string $sqlType the SQL type
     *
     * @return bool True if the type has a scale attribute
     */
    public function hasScale($sqlType)
    {
        return true;
    }

    /**
     * Quote and escape needed characters in the string for underlying RDBMS.
     *
     * @param string $text
     *
     * @return string
     */
    public function quote($text)
    {
        if ($con = $this->getConnection()) {
            return $con->quote($text);
        } else {
            return "'" . $this->disconnectedEscapeText($text) . "'";
        }
    }

    /**
     * Method to escape text when no connection has been set.
     *
     * The subclasses can implement this using string replacement functions
     * or native DB methods.
     *
     * @param string $text Text that needs to be escaped.
     *
     * @return string
     */
    protected function disconnectedEscapeText($text)
    {
        return str_replace("'", "''", $text);
    }

    /**
     * Quotes identifiers used in database SQL if isIdentifierQuotingEnabled is true.
     * Calls doQuoting() when identifierQuoting is enabled.
     *
     * @param string $text
     *
     * @return string Quoted identifier.
     */
    protected function quoteIdentifier($text)
    {
        return $this->isIdentifierQuotingEnabled() ? $this->doQuoting($text) : $text;
    }

    /**
     * @inheritDoc
     */
    public function doQuoting($text)
    {
        return '"' . strtr($text, ['.' => '"."']) . '"';
    }

    /**
     * Whether RDBMS supports native ON DELETE triggers (e.g. ON DELETE CASCADE).
     *
     * @return bool
     */
    public function supportsNativeDeleteTrigger()
    {
        return false;
    }

    /**
     * Whether RDBMS supports INSERT null values in autoincremented primary keys
     *
     * @return bool
     */
    public function supportsInsertNullPk()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportsIndexSize()
    {
        return false;
    }

    /**
     * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
     *
     * @return bool
     */
    public function hasStreamBlobImpl()
    {
        return false;
    }

    /**
     * @see Platform::supportsSchemas()
     *
     * @return bool
     */
    public function supportsSchemas()
    {
        return false;
    }

    /**
     * @see Platform::supportsMigrations()
     *
     * @return bool
     */
    public function supportsMigrations()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportsVarcharWithoutSize()
    {
        return false;
    }

    /**
     * Returns the Boolean value for the RDBMS.
     *
     * This value should match the Boolean value that is set
     * when using Propel's PreparedStatement::setBoolean().
     *
     * This function is used to set default column values when building
     * SQL.
     *
     * @param bool|int|string $b A Boolean or string representation of Boolean ('y', 'true').
     *
     * @return string
     */
    public function getBooleanString($b)
    {
        if (is_bool($b) && $b === true) {
            return '1';
        }

        if (is_int($b) && $b === 1) {
            return '1';
        }

        if (
            is_string($b)
            && in_array(strtolower($b), ['1', 'true', 'y', 'yes'])
        ) {
            return '1';
        }

        return '0';
    }

    /**
     * @param string $stringValue
     *
     * @return string|null
     */
    public function getPhpArrayString($stringValue)
    {
        $stringValue = trim($stringValue);
        if (empty($stringValue)) {
            return null;
        }

        $values = [];
        foreach (explode(',', $stringValue) as $v) {
            $values[] = trim($v);
        }

        $value = implode(' | ', $values);
        if (empty($value) || $value === ' | ') {
            return null;
        }

        return $this->quote(sprintf('||%s||', $value));
    }

    /**
     * Gets the preferred timestamp formatter for setting date/time values.
     *
     * @return string
     */
    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s.u';
    }

    /**
     * Gets the preferred time formatter for setting date/time values.
     *
     * @return string
     */
    public function getTimeFormatter()
    {
        return 'H:i:s.u';
    }

    /**
     * Gets the preferred date formatter for setting date/time values.
     *
     * @return string
     */
    public function getDateFormatter()
    {
        return 'Y-m-d';
    }

    /**
     * Get the PHP snippet for binding a value to a column.
     * Warning: duplicates logic from AdapterInterface::bindValue().
     * Any code modification here must be ported there.
     *
     * @param \Propel\Generator\Model\Column $column
     * @param string $identifier
     * @param string $columnValueAccessor
     * @param string $tab
     *
     * @return string
     */
    public function getColumnBindingPHP(Column $column, $identifier, $columnValueAccessor, $tab = '            ')
    {
        $script = '';
        if ($column->isTemporalType()) {
            $columnValueAccessor = $columnValueAccessor . ' ? ' . $columnValueAccessor . '->format("' . $this->getTimeStampFormatter() . '") : null';
        } elseif ($column->isLobType()) {
            // we always need to make sure that the stream is rewound, otherwise nothing will
            // get written to database.
            $script .= "
if (is_resource($columnValueAccessor)) {
    rewind($columnValueAccessor);
}";
        }

        $script .= sprintf(
            "
\$stmt->bindValue(%s, %s, %s);",
            $identifier,
            $columnValueAccessor,
            PropelTypes::getPdoTypeString($column->getType())
        );

        return preg_replace('/^(.+)/m', $tab . '$1', $script);
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from AdapterInterface::getId().
     * Any code modification here must be ported there.
     *
     * Typical output:
     * <code>
     * $this->id = $con->lastInsertId();
     * </code>
     *
     * @param string $columnValueMutator
     * @param string $connectionVariableName
     * @param string $sequenceName
     * @param string $tab
     * @param string|null $phpType
     *
     * @return string|string[]|null
     */
    public function getIdentifierPhp($columnValueMutator, $connectionVariableName = '$con', $sequenceName = '', $tab = '            ', $phpType = null)
    {
        return sprintf(
            "
%s%s = %s%s->lastInsertId(%s);",
            $tab,
            $columnValueMutator,
            $connectionVariableName,
            $phpType ? '(' . $phpType . ') ' : '',
            $sequenceName ? ("'" . $sequenceName . "'") : ''
        );
    }

    /**
     * Returns a integer indexed array of default type sizes.
     *
     * @return int[] type indexed array of integers
     */
    public function getDefaultTypeSizes()
    {
        return [];
    }

    /**
     * Returns the default size of a specific type.
     *
     * @param string $type
     *
     * @return int
     */
    public function getDefaultTypeSize($type)
    {
        $sizes = $this->getDefaultTypeSizes();

        return isset($sizes[strtolower($type)]) ? $sizes[strtolower($type)] : null;
    }

    /**
     * Normalizes a table for the current platform. Very important for the TableComparator to not
     * generate useless diffs.
     * Useful for checking needed definitions/structures. E.g. Unique Indexes for ForeignKey columns,
     * which the most Platforms requires but which is not always explicitly defined in the table model.
     *
     * @param \Propel\Generator\Model\Table $table The table object which gets modified.
     *
     * @return void
     */
    public function normalizeTable(Table $table)
    {
        if ($table->hasForeignKeys()) {
            foreach ($table->getForeignKeys() as $fk) {
                if ($fk->getForeignTable() && !$fk->getForeignTable()->isUnique($fk->getForeignColumnObjects())) {
                    $unique = new Unique();
                    $unique->setColumns($fk->getForeignColumnObjects());
                    $fk->getForeignTable()->addUnique($unique);
                }
            }
        }

        if (!$this->supportsIndexSize() && $table->getIndices()) {
            // when the plafform does not support index sizes we reset it
            foreach ($table->getIndices() as $index) {
                $index->resetColumnsSize();
            }
        }

        foreach ($table->getColumns() as $column) {
            if ($column->getSize() && $defaultSize = $this->getDefaultTypeSize($column->getType())) {
                if ($column->getScale() === null && (int)$column->getSize() === $defaultSize) {
                    $column->setSize(null);
                }
            }
        }
    }
}
