<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Column;

/**
 * MS SQL PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Dominic Winkler <d.winkler@flexarts.at> (Flexarts)
 */
class MssqlPlatform extends DefaultPlatform
{
    protected static $dropCount = 0;

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();

        $this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, "INT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "INT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "FLOAT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "VARCHAR(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "VARCHAR(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, "DATE"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BU_DATE, "DATE"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, "TIME"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, "DATETIME2"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BU_TIMESTAMP, "DATETIME2"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BINARY(7132)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, "VARCHAR(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, "TINYINT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SET, "INT"));
    }

    public function getMaxColumnNameLength()
    {
        return 128;
    }

    public function getNullString($notNull)
    {
        return $notNull ? 'NOT NULL' : 'NULL';
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

    public function supportsInsertNullPk()
    {
        return false;
    }

    /**
     * Returns the DDL SQL to add the tables of a database
     * together with index and foreign keys.
     * Since MSSQL always checks it the tables in foreign key definitions exist,
     * the foreign key DDLs are moved after all tables are created
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
        }
        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getAddForeignKeysDDL($table);
        }
        $ret .= $this->getEndDDL();

        return $ret;
    }

    /**
     * Builds the DDL SQL for a Column object AFTER a mutation
     * This is required since MSSQL doesnt support the same column definition
     * when mutating a column vs creating a column
     *
     * @return string
     */
    public function getChangeColumnDDL(Column $col)
    {
        $domain = $col->getDomain();

        $ddl = [$this->quoteIdentifier($col->getName())];
        $sqlType = $domain->getSqlType();
        if ($this->hasSize($sqlType) && $col->isDefaultSqlType($this)) {
            $ddl[] = $sqlType . $col->getSizeDefinition();
        } else {
            $ddl[] = $sqlType;
        }
        if ($notNull = $this->getNullString($col->isNotNull())) {
            $ddl[] = $notNull;
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }

        return implode(' ', $ddl);
    }

    public function getDropTableDDL(Table $table)
    {
        $ret = '';
        foreach ($table->getForeignKeys() as $fk) {
            $ret .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='" . $fk->getName() . "')
    ALTER TABLE " . $this->quoteIdentifier($table->getName()) . " DROP CONSTRAINT " . $this->quoteIdentifier($fk->getName()) . ";
";
        }

        self::$dropCount++;

        $ret .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = '" . $table->getName() . "')
BEGIN
    DECLARE @reftable_" . self::$dropCount . " nvarchar(60), @constraintname_" . self::$dropCount . " nvarchar(60)
    DECLARE refcursor CURSOR FOR
    select reftables.name tablename, cons.name constraintname
        from sysobjects tables,
            sysobjects reftables,
            sysobjects cons,
            sysreferences ref
        where tables.id = ref.rkeyid
            and cons.id = ref.constid
            and reftables.id = ref.fkeyid
            and tables.name = '" . $table->getName() . "'
    OPEN refcursor
    FETCH NEXT from refcursor into @reftable_" . self::$dropCount . ", @constraintname_" . self::$dropCount . "
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_" . self::$dropCount . "+' drop constraint '+@constraintname_" . self::$dropCount . ")
        FETCH NEXT from refcursor into @reftable_" . self::$dropCount . ", @constraintname_" . self::$dropCount . "
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE " . $this->quoteIdentifier($table->getName()) . "
END
";

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string|null
     */
    public function getPrimaryKeyDDL(Table $table)
    {
        if ($table->hasPrimaryKey()) {
            $pattern = 'CONSTRAINT %s PRIMARY KEY (%s)';

            return sprintf($pattern,
                $this->quoteIdentifier($this->getPrimaryKeyName($table)),
                $this->getColumnListDDL($table->getPrimaryKey())
            );
        }
    }

    /**
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
BEGIN
ALTER TABLE %s ADD %s
END
;
";

        return sprintf($pattern,
            $this->quoteIdentifier($fk->getTable()->getName()),
            $this->getForeignKeyDDL($fk)
        );
    }

    /**
     * Builds the DDL SQL for a Unique constraint object. MS SQL Server CONTRAINT specific
     *
     * @param  Unique $unique
     * @return string
     */
    public function getUniqueDDL(Unique $unique)
    {
        $pattern = 'CONSTRAINT %s UNIQUE NONCLUSTERED (%s) ON [PRIMARY]';
        return sprintf($pattern,
            $this->quoteIdentifier($unique->getName()),
            $this->getColumnListDDL($unique->getColumnObjects())
        );
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string
     */
    public function getForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return '';
        }

        $pattern = 'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)';
        $script = sprintf($pattern,
            $this->quoteIdentifier($fk->getName()),
            $this->getColumnListDDL($fk->getLocalColumnObjects()),
            $this->quoteIdentifier($fk->getForeignTableName()),
            $this->getColumnListDDL($fk->getForeignColumnObjects())
        );
        if ($fk->hasOnUpdate() && $fk->getOnUpdate()) {
            $script .= ' ON UPDATE ' . $fk->getOnUpdate();
        }
        if ($fk->hasOnDelete() && $fk->getOnDelete()) {
            $script .= ' ON DELETE '.  $fk->getOnDelete();
        }

        return $script;
    }

    /**
     * Builds the DDL SQL to drop a foreign key.
     *
     * @param  ForeignKey $fk
     * @return string
     */
    public function getDropForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return;
        }

        $sql = "
IF EXISTS (SELECT 1 FROM sysobjects WHERE name='" . $fk->getName() . "')
    ALTER TABLE " . $this->quoteIdentifier($fk->getTable()->getName()) . " DROP CONSTRAINT " . $this->quoteIdentifier($fk->getName()) . ";
";

        return $sql;
    }

    /**
     * Returns the DDL SQL to add an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getAddIndexDDL(Index $index)
    {
        // Unique indexes must be treated as constraints
        if ($index->isUnique()) {
            return "\nALTER TABLE " . $this->quoteIdentifier($index->getTable()->getName()) . " ADD " . $this->getUniqueDDL($index).";\n";

        } else {
            $pattern = "
CREATE INDEX %s ON %s (%s);
";

            return sprintf($pattern,
                $this->quoteIdentifier($index->getName()),
                $this->quoteIdentifier($index->getTable()->getName()),
                $this->getColumnListDDL($index->getColumnObjects())
            );
        }
    }

    /**
     * Builds the DDL SQL to drop an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getDropIndexDDL(Index $index)
    {
        // Unique indexes must be treated as constraints
        if ($index->isUnique()) {
            $sql = "
IF EXISTS (SELECT 1 FROM sysobjects WHERE name='" . $index->getFQName() . "')
    ALTER TABLE " . $this->quoteIdentifier($index->getTable()->getName()) . " DROP CONSTRAINT " . $this->quoteIdentifier($index->getFQName()) . ";
";

            return $sql;
        }

        $pattern = "\nDROP INDEX %s ON %s;\n";

        return sprintf($pattern,
            $this->quoteIdentifier($index->getFQName()),
            $this->quoteIdentifier($index->getTable()->getName())
        );

    }

    /**
     * Builds the DDL SQL to rename a column
     *
     * @return string
     */
    public function getRenameColumnDDL(Column $fromColumn, Column $toColumn)
    {
        $fromColumnName = $this->quoteIdentifier($fromColumn->getTable()->getName().".".$fromColumn->getName());
        $toColumnName = $this->quoteIdentifier($toColumn->getName());

        $script = "
EXEC sp_rename $fromColumnName, $toColumnName, 'COLUMN';
        ";

        return $script;
    }

    /**
     * Builds the DDL SQL to add a list of columns
     *
     * @param  Column[] $columns
     * @return string
     */
    public function getAddColumnsDDL($columns)
    {
        $lines = [];
        $table = null;
        foreach ($columns as $column) {
            if (null === $table) {
                $table = $column->getTable();
            }
            $lines[] = $this->getColumnDDL($column);
        }

        $sep = ",\n";

        $pattern = "\nALTER TABLE %s ADD %s;";

        return sprintf($pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    /**
     * Builds the DDL SQL to modify a column
     *
     * @return string
     */
    public function getModifyColumnDDL(ColumnDiff $columnDiff)
    {
        $script = '';

        // default value changes requires default value constraint dropping
        if ($this->alterColumnRequiresDropDefaultConstraint($columnDiff)) {
            $dropDefaultContraintSql = $this->getDropDefaultConstraintDDL($columnDiff->getFromColumn());
            $script .= $dropDefaultContraintSql;
        }

        $toColumn = $columnDiff->getToColumn();
        $changed = $columnDiff->getChangedProperties();

        // default value changed
        if (isset($changed['defaultValueValue']) || isset($changed['defaultValueType'])) {
            $defaultValueForColumnDDL = $this->getColumnDefaultValueDDL($toColumn);
            if (strlen(trim($defaultValueForColumnDDL)) > 0) {
                $pattern = "ALTER TABLE %s ADD $defaultValueForColumnDDL FOR %s;\n";
                $script .= sprintf(
                    $pattern,
                    $this->quoteIdentifier($toColumn->getTable()->getName()),
                    $this->quoteIdentifier($toColumn->getName())
                );
            }
        }

        $pattern = "ALTER TABLE %s ALTER COLUMN %s;\n";
        $script .= sprintf($pattern,
            $this->quoteIdentifier($toColumn->getTable()->getName()),
            $this->getChangeColumnDDL($toColumn)
        );

        return $script;
    }

    /**
     * Builds the DDL SQL to modify a list of columns
     *
     * @param  ColumnDiff[] $columnDiffs
     * @return string
     */
    public function getModifyColumnsDDL($columnDiffs)
    {
        $lines = [];
        $table = null;
        foreach ($columnDiffs as $columnDiff) {
            $toColumn = $columnDiff->getToColumn();
            if (null === $table) {
                $table = $toColumn->getTable();
            }
            $lines[] = $this->getModifyColumnDDL($columnDiff);
        }

        $sep = "";

        return "\n" . implode($sep, $lines);
    }

    /**
     * @see Platform::supportsSchemas()
     */
    public function supportsSchemas()
    {
        return true;
    }

    /**
     * @param string $sqlType
     *
     * @return bool
     */
    public function hasSize($sqlType)
    {
        $nosize = ['INT', 'TEXT', 'GEOMETRY', 'VARCHAR(MAX)', 'VARBINARY(MAX)', 'SMALLINT', 'DATETIME', 'TINYINT', 'REAL', 'BIGINT'];

        return !(in_array($sqlType, $nosize));
    }

    /**
     * {@inheritdoc}
     */
    public function doQuoting($text)
    {
        return "[$text]";
    }

    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @param Column $column
     * @return string
     */
    public function getDropDefaultConstraintDDL(Column $column)
    {
        $tableName = $column->getTableName();
        $colName = $column->getName();

        $script = "IF EXISTS (SELECT d.name FROM sys.tables t JOIN sys.default_constraints d on d.parent_object_id = t.object_id JOIN sys.columns c on c.object_id = t.object_id AND c.column_id = d.parent_column_id WHERE t.name = '".$tableName."' AND c.name = '".$colName."')
BEGIN
DECLARE @constraintName nvarchar(100), @cmd nvarchar(1000)
SET @constraintName = (SELECT d.name FROM sys.tables t JOIN sys.default_constraints d on d.parent_object_id = t.object_id JOIN sys.columns c on c.object_id = t.object_id AND c.column_id = d.parent_column_id WHERE t.name = '".$tableName."' AND c.name = '".$colName."')
SET @CMD = 'ALTER TABLE {$this->quoteIdentifier($tableName)} DROP CONSTRAINT ' + @constraintName
EXEC (@CMD)
END;\n";

        return $script;
    }

    /**
     * Checks whether a column alteration requires dropping its default constraint first.
     *
     * Different to other database vendors SQL Server implements column default values
     * as constraints and therefore changes in a column's default value as well as changes
     * in a column's type require dropping the default constraint first before being to
     * alter the particular column to the new definition.
     *
     * @param ColumnDiff $columnDiff The column diff to evaluate.
     *
     * @return boolean True if the column alteration requires dropping its default constraint first, false otherwise.
     */
    private function alterColumnRequiresDropDefaultConstraint(ColumnDiff $columnDiff)
    {
        // We can only decide whether to drop an existing default constraint
        // if we know the original default value.
        if (!$columnDiff->getFromColumn() instanceof Column) {
            return false;
        }
        // We only need to drop an existing default constraint if we know the
        // column was defined with a default value before.
        if (!$columnDiff->getFromColumn()->hasDefaultValue()) {
            return false;
        }
        // We need to drop an existing default constraint if the column was
        // defined with a default value before and it has changed.
        if (!$columnDiff->getFromColumn()->hasDefaultValue() && $columnDiff->getToColumn()->hasDefaultValue()) {
            return false;
        }

        return true;
    }
}
