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

    public function getAddForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return;
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

    public function getForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return;
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

        $sep = ",
    ";

        $pattern = "
ALTER TABLE %s ADD

    %s
;
";

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
        $toColumn = $columnDiff->getToColumn();
        $pattern = "
ALTER TABLE %s ALTER COLUMN %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($toColumn->getTable()->getName()),
            $this->getColumnDDL($toColumn)
        );
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
            $lines[] = $this->getColumnDDL($toColumn);
        }

        $sep = ",
    ";

        $pattern = "
ALTER TABLE %s ALTER COLUMN

    %s
;
";

        return sprintf($pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    /**
     * @see Platform::supportsSchemas()
     */
    public function supportsSchemas()
    {
        return true;
    }

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
}
