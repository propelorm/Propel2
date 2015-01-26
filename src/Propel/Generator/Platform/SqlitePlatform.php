<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;

/**
 * SQLite PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqlitePlatform extends DefaultPlatform
{
    /**
     * If we should generate FOREIGN KEY statements.
     * This is since SQLite version 3.6.19 possible.
     *
     * @var bool
     */
    protected $foreignKeySupport = null;

    /**
     * If we should alter the table through creating a temporarily created table,
     * moving all items to the new one and finally rename the temp table.
     *
     * @var bool
     */
    protected $tableAlteringWorkaround = true;

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();

        $version = \SQLite3::version();
        $version = $version['versionString'];

        $this->foreignKeySupport = version_compare($version, '3.6.19') >= 0;

        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'DECIMAL'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'LONGTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'TINYINT'));
    }

    public function getSchemaDelimiter()
    {
        return '§';
    }

    public function getDefaultTypeSizes()
    {
        return array(
            'char'      => 1,
            'character' => 1,
            'integer'   => 32,
            'bigint'    => 64,
            'smallint'  => 16,
            'double precision' => 54
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        parent::setGeneratorConfig($generatorConfig);

        if (null !== ($foreignKeySupport = $generatorConfig->getConfigProperty('database.adapter.sqlite.foreignKey'))) {
            $this->foreignKeySupport = filter_var($foreignKeySupport, FILTER_VALIDATE_BOOLEAN);;
        }
        if (null !== ($tableAlteringWorkaround = $generatorConfig->getConfigProperty('database.adapter.sqlite.tableAlteringWorkaround'))) {
            $this->tableAlteringWorkaround = filter_var($tableAlteringWorkaround, FILTER_VALIDATE_BOOLEAN);;;
        }
    }

    /**
     * Builds the DDL SQL to remove a list of columns
     *
     * @param  Column[] $columns
     * @return string
     */
    public function getAddColumnsDDL($columns)
    {
        $ret = '';
        $pattern = "
ALTER TABLE %s ADD %s;
";
        foreach ($columns as $column) {
            $tableName = $column->getTable()->getName();
            $ret .= sprintf($pattern,
                $this->quoteIdentifier($tableName),
                $this->getColumnDDL($column)
            );
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getModifyTableDDL(TableDiff $tableDiff)
    {
        $changedNotEditableThroughDirectDDL = $this->tableAlteringWorkaround && (false
            || $tableDiff->hasModifiedFks()
            || $tableDiff->hasModifiedIndices()
            || $tableDiff->hasModifiedColumns()
            || $tableDiff->hasRenamedColumns()

            || $tableDiff->hasRemovedFks()
            || $tableDiff->hasRemovedIndices()
            || $tableDiff->hasRemovedColumns()

            || $tableDiff->hasAddedIndices()
            || $tableDiff->hasAddedFks()
            || $tableDiff->hasAddedPkColumns()
        );

        if ($this->tableAlteringWorkaround && !$changedNotEditableThroughDirectDDL && $tableDiff->hasAddedColumns()) {

            $addedCols = $tableDiff->getAddedColumns();
            foreach ($addedCols as $column) {

                $sqlChangeNotSupported = false

                    //The column may not have a PRIMARY KEY or UNIQUE constraint.
                    || $column->isPrimaryKey()
                    || $column->isUnique()

                    //The column may not have a default value of CURRENT_TIME, CURRENT_DATE, CURRENT_TIMESTAMP,
                    //or an expression in parentheses.
                    || false !== array_search(
                        $column->getDefaultValue(), array('CURRENT_TIME', 'CURRENT_DATE', 'CURRENT_TIMESTAMP'))
                    || substr(trim($column->getDefaultValue()), 0, 1) == '('

                    //If a NOT NULL constraint is specified, then the column must have a default value other than NULL.
                    || ($column->isNotNull() && $column->getDefaultValue() == 'NULL')
                ;

                if ($sqlChangeNotSupported) {
                    $changedNotEditableThroughDirectDDL = true;
                    break;
                }

            }
        }

        if ($changedNotEditableThroughDirectDDL) {
            return $this->getMigrationTableDDL($tableDiff);
        }

        return parent::getModifyTableDDL($tableDiff);
    }

    /**
     * Creates a temporarily created table with the new schema,
     * moves all items into it and drops the origin as well as renames the temp table to the origin then.
     *
     * @param  TableDiff $tableDiff
     * @return string
     */
    public function getMigrationTableDDL(TableDiff $tableDiff)
    {
        $pattern = "
CREATE TEMPORARY TABLE %s AS SELECT %s FROM %s;
DROP TABLE %s;
%s
INSERT INTO %s (%s) SELECT %s FROM %s;
DROP TABLE %s;
";

        $originTable     = clone $tableDiff->getFromTable();
        $newTable        = clone $tableDiff->getToTable();

        $originTableName = $originTable->getName();
        $tempTableName   = $newTable->getCommonName().'__temp__'.uniqid();

        $originTableFields = $this->getColumnListDDL($originTable->getColumns());

        $fieldMap = []; /** struct: [<oldCol> => <newCol>] */
        //start with modified columns
        foreach ($tableDiff->getModifiedColumns() as $diff) {
            $fieldMap[$diff->getFromColumn()->getName()] = $diff->getToColumn()->getName();
        }

        foreach ($tableDiff->getRenamedColumns() as $col) {
            list ($from, $to) = $col;
            $fieldMap[$from->getName()] = $to->getName();
        }

        foreach ($newTable->getColumns() as $col) {
            if ($originTable->hasColumn($col)) {
                if (!isset($fieldMap[$col->getName()])) {
                    $fieldMap[$col->getName()] = $col->getName();
                }
            }

        }

        $createTable = $this->getAddTableDDL($newTable);
        $createTable .= $this->getAddIndicesDDL($newTable);

        $sql = sprintf($pattern,
            $this->quoteIdentifier($tempTableName), //CREATE TEMPORARY TABLE %s
            $originTableFields, //select %s
            $this->quoteIdentifier($originTableName), //from %s
            $this->quoteIdentifier($originTableName), //drop table %s
            $createTable, //[create table] %s
            $this->quoteIdentifier($originTableName), //insert into %s
            implode(', ', $fieldMap), //(%s)
            implode(', ', array_keys($fieldMap)), //select %s
            $this->quoteIdentifier($tempTableName), //from %s
            $this->quoteIdentifier($tempTableName) //drop table %s
        );

        return $sql;
    }

    public function getBeginDDL()
    {
        return '
PRAGMA foreign_keys = OFF;
';
    }

    public function getEndDDL()
    {
        return '
PRAGMA foreign_keys = ON;
';
    }

    /**
     * {@inheritdoc}
     */
    public function getAddTablesDDL(Database $database)
    {
        $ret = '';
        foreach ($database->getTablesForSql() as $table) {
            $this->normalizeTable($table);
        }
        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getCommentBlockDDL($table->getName());
            $ret .= $this->getDropTableDDL($table);
            $ret .= $this->getAddTableDDL($table);
            $ret .= $this->getAddIndicesDDL($table);
        }

        return $ret;
    }

    /**
     * Unfortunately, SQLite does not support composite pks where one is AUTOINCREMENT,
     * so we have to flag both as NOT NULL and create in either way a UNIQUE constraint over pks since
     * those UNIQUE is otherwise automatically created by the sqlite engine.
     *
     * @param Table $table
     */
    public function normalizeTable(Table $table)
    {
        if ($table->getPrimaryKey()) {
            //search if there is already a UNIQUE constraint over the primary keys
            $pkUniqueExist = false;
            foreach ($table->getUnices() as $unique) {
                $coversAllPrimaryKeys = true;
                foreach ($unique->getColumns() as $columnName) {
                    if (!$table->getColumn($columnName)->isPrimaryKey()) {
                        $coversAllPrimaryKeys = false;
                        break;
                    }
                }
                if ($coversAllPrimaryKeys) {
                    //there's already a unique constraint with the composite pk
                    $pkUniqueExist = true;
                    break;
                }
            }

            //there is none, let's create it
            if (!$pkUniqueExist) {
                $unique = new Unique();
                foreach ($table->getPrimaryKey() as $pk) {
                    $unique->addColumn($pk);
                }
                $table->addUnique($unique);
            }

            if ($table->hasAutoIncrementPrimaryKey()) {
                foreach ($table->getPrimaryKey() as $pk) {
                    //no pk can be NULL, as usual
                    $pk->setNotNull(true);
                    //in SQLite the column with the AUTOINCREMENT MUST be a primary key, too.
                    if (!$pk->isAutoIncrement()) {
                        //for all other sub keys we remove it, since we create a UNIQUE constraint over all primary keys.
                        $pk->setPrimaryKey(false);
                    }
                }
            }
        }

        parent::normalizeTable($table);
    }

    /**
     * Returns the SQL for the primary key of a Table object
     * @return string
     */
    public function getPrimaryKeyDDL(Table $table)
    {
        if ($table->hasPrimaryKey() && !$table->hasAutoIncrementPrimaryKey()) {
            return 'PRIMARY KEY (' . $this->getColumnListDDL($table->getPrimaryKey()) . ')';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoveColumnDDL(Column $column)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getRenameColumnDDL(Column $fromColumn, Column $toColumn)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getModifyColumnDDL(ColumnDiff $columnDiff)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getModifyColumnsDDL($columnDiffs)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDropPrimaryKeyDDL(Table $table)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAddPrimaryKeyDDL(Table $table)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAddForeignKeyDDL(ForeignKey $fk)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDropForeignKeyDDL(ForeignKey $fk)
    {
        //not supported
        return '';
    }

    /**
     * @link       http://www.sqlite.org/autoinc.html
     */
    public function getAutoIncrement()
    {
        return 'PRIMARY KEY AUTOINCREMENT';
    }

    public function getMaxColumnNameLength()
    {
        return 1024;
    }

    public function getColumnDDL(Column $col)
    {
        if ($col->isAutoIncrement()) {
            $col->setType('INTEGER');
            $col->setDomainForType('INTEGER');
        }

        if ($col->getDefaultValue()
            && $col->getDefaultValue()->isExpression()
            && 'CURRENT_TIMESTAMP' === $col->getDefaultValue()->getValue()) {
            //sqlite use CURRENT_TIMESTAMP different than mysql/pgsql etc
            //we set it to the more common behavior
            $col->setDefaultValue(
                new ColumnDefaultValue("(datetime(CURRENT_TIMESTAMP, 'localtime'))", ColumnDefaultValue::TYPE_EXPR)
            );
        }

        return parent::getColumnDDL($col);
    }

    public function getAddTableDDL(Table $table)
    {
        $table = clone $table;
        $tableDescription = $table->hasDescription() ? $this->getCommentLineDDL($table->getDescription()) : '';

        $lines = array();

        foreach ($table->getColumns() as $column) {
            $lines[] = $this->getColumnDDL($column);
        }

        if ($table->hasPrimaryKey() && ($pk = $this->getPrimaryKeyDDL($table))) {
            $lines[] = $pk;
        }

        foreach ($table->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        if ($this->foreignKeySupport) {
            foreach ($table->getForeignKeys() as $foreignKey) {
                if ($foreignKey->isSkipSql() || $foreignKey->isPolymorphic()) {
                    continue;
                }
                $lines[] = str_replace("
    ", "
        ", $this->getForeignKeyDDL($foreignKey));
            }
        }

        $sep = ",
    ";

        $pattern = "
%sCREATE TABLE %s
(
    %s
);
";

        return sprintf($pattern,
            $tableDescription,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    public function getForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || !$this->foreignKeySupport || $fk->isPolymorphic()) {
            return;
        }

        $pattern = "FOREIGN KEY (%s) REFERENCES %s (%s)";

        $script = sprintf($pattern,
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

    public function hasSize($sqlType)
    {
        return !in_array($sqlType, array(
            'MEDIUMTEXT',
            'LONGTEXT',
            'BLOB',
            'MEDIUMBLOB',
            'LONGBLOB',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function doQuoting($text)
    {
        return '[' . strtr($text, array('.' => '].[')) . ']';
    }

    public function supportsSchemas()
    {
        return true;
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

}
