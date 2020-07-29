<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Platform;

use PDO;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;

/**
 * MySql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class MysqlPlatform extends DefaultPlatform
{
    /**
     * @var string
     */
    protected $tableEngineKeyword = 'ENGINE';

    /**
     * @var string
     */
    protected $defaultTableEngine = 'InnoDB';

    /**
     * Initializes db specific domain mapping.
     *
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'TINYINT', 1));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'DECIMAL'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'LONGTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'TINYINT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SET, 'INT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'DOUBLE'));
    }

    /**
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        parent::setGeneratorConfig($generatorConfig);
        if ($defaultTableEngine = $generatorConfig->get()['database']['adapters']['mysql']['tableType']) {
            $this->defaultTableEngine = $defaultTableEngine;
        }
        if ($tableEngineKeyword = $generatorConfig->get()['database']['adapters']['mysql']['tableEngineKeyword']) {
            $this->tableEngineKeyword = $tableEngineKeyword;
        }
    }

    /**
     * Setter for the tableEngineKeyword property
     *
     * @param string $tableEngineKeyword
     *
     * @return void
     */
    public function setTableEngineKeyword($tableEngineKeyword)
    {
        $this->tableEngineKeyword = $tableEngineKeyword;
    }

    /**
     * Getter for the tableEngineKeyword property
     *
     * @return string
     */
    public function getTableEngineKeyword()
    {
        return $this->tableEngineKeyword;
    }

    /**
     * Setter for the defaultTableEngine property
     *
     * @param string $defaultTableEngine
     *
     * @return void
     */
    public function setDefaultTableEngine($defaultTableEngine)
    {
        $this->defaultTableEngine = $defaultTableEngine;
    }

    /**
     * Getter for the defaultTableEngine property
     *
     * @return string
     */
    public function getDefaultTableEngine()
    {
        return $this->defaultTableEngine;
    }

    /**
     * @return string
     */
    public function getAutoIncrement()
    {
        return 'AUTO_INCREMENT';
    }

    /**
     * @return int
     */
    public function getMaxColumnNameLength()
    {
        return 64;
    }

    /**
     * @return bool
     */
    public function supportsNativeDeleteTrigger()
    {
        return strtolower($this->getDefaultTableEngine()) === 'innodb';
    }

    /**
     * @return bool
     */
    public function supportsIndexSize()
    {
        return true;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return bool
     */
    public function supportsForeignKeys(Table $table)
    {
        $vendorSpecific = $table->getVendorInfoForType('mysql');
        if ($vendorSpecific->hasParameter('Type')) {
            $mysqlTableType = $vendorSpecific->getParameter('Type');
        } elseif ($vendorSpecific->hasParameter('Engine')) {
            $mysqlTableType = $vendorSpecific->getParameter('Engine');
        } else {
            $mysqlTableType = $this->getDefaultTableEngine();
        }

        return strtolower($mysqlTableType) === 'innodb';
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return string
     */
    public function getAddTablesDDL(Database $database)
    {
        $ret = '';
        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getCommentBlockDDL($table->getName());
            $ret .= $this->getDropTableDDL($table);
            $ret .= $this->getAddTableDDL($table);
        }
        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getBeginDDL()
    {
        return "
# This is a fix for InnoDB in MySQL >= 4.1.x
# It \"suspends judgement\" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;
";
    }

    /**
     * @return string
     */
    public function getEndDDL()
    {
        return "
# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
";
    }

    /**
     * Returns the SQL for the primary key of a Table object
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getPrimaryKeyDDL(Table $table)
    {
        if ($table->hasPrimaryKey()) {
            $keys = $table->getPrimaryKey();

            //MySQL throws an 'Incorrect table definition; there can be only one auto column and it must be defined as a key'
            //if the primary key consists of multiple columns and if the first is not the autoIncrement one. So
            //this push the autoIncrement column to the first position if its not already.
            $autoIncrement = $table->getAutoIncrementPrimaryKey();
            if ($autoIncrement && $keys[0] != $autoIncrement) {
                $idx = array_search($autoIncrement, $keys);
                if ($idx !== false) {
                    unset($keys[$idx]);
                    array_unshift($keys, $autoIncrement);
                }
            }

            return 'PRIMARY KEY (' . $this->getColumnListDDL($keys) . ')';
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddTableDDL(Table $table)
    {
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

        foreach ($table->getIndices() as $index) {
            $lines[] = $this->getIndexDDL($index);
        }

        if ($this->supportsForeignKeys($table)) {
            foreach ($table->getForeignKeys() as $foreignKey) {
                if ($foreignKey->isSkipSql() || $foreignKey->isPolymorphic()) {
                    continue;
                }
                $lines[] = str_replace("
    ", "
        ", $this->getForeignKeyDDL($foreignKey));
            }
        }

        $vendorSpecific = $table->getVendorInfoForType('mysql');
        if ($vendorSpecific->hasParameter('Type')) {
            $mysqlTableType = $vendorSpecific->getParameter('Type');
        } elseif ($vendorSpecific->hasParameter('Engine')) {
            $mysqlTableType = $vendorSpecific->getParameter('Engine');
        } else {
            $mysqlTableType = $this->getDefaultTableEngine();
        }

        $tableOptions = $this->getTableOptions($table);

        if ($table->getDescription()) {
            $tableOptions[] = 'COMMENT=' . $this->quote($table->getDescription());
        }

        $tableOptions = $tableOptions ? ' ' . implode(' ', $tableOptions) : '';
        $sep = ",
    ";

        $pattern = "
CREATE TABLE %s
(
    %s
) %s=%s%s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines),
            $this->getTableEngineKeyword(),
            $mysqlTableType,
            $tableOptions
        );
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string[]
     */
    protected function getTableOptions(Table $table)
    {
        $dbVI = $table->getDatabase()->getVendorInfoForType('mysql');
        $tableVI = $table->getVendorInfoForType('mysql');
        $vi = $dbVI->getMergedVendorInfo($tableVI);
        $tableOptions = [];
        // List of supported table options
        // see http://dev.mysql.com/doc/refman/5.5/en/create-table.html
        $supportedOptions = [
            'AutoIncrement' => 'AUTO_INCREMENT',
            'AvgRowLength' => 'AVG_ROW_LENGTH',
            'Charset' => 'CHARACTER SET',
            'Checksum' => 'CHECKSUM',
            'Collate' => 'COLLATE',
            'Connection' => 'CONNECTION',
            'DataDirectory' => 'DATA DIRECTORY',
            'Delay_key_write' => 'DELAY_KEY_WRITE',
            'DelayKeyWrite' => 'DELAY_KEY_WRITE',
            'IndexDirectory' => 'INDEX DIRECTORY',
            'InsertMethod' => 'INSERT_METHOD',
            'KeyBlockSize' => 'KEY_BLOCK_SIZE',
            'MaxRows' => 'MAX_ROWS',
            'MinRows' => 'MIN_ROWS',
            'Pack_Keys' => 'PACK_KEYS',
            'PackKeys' => 'PACK_KEYS',
            'RowFormat' => 'ROW_FORMAT',
            'Union' => 'UNION',
        ];

        $noQuotedValue = array_flip([
            'InsertMethod',
            'Pack_Keys',
            'PackKeys',
            'RowFormat',
        ]);

        foreach ($supportedOptions as $name => $sqlName) {
            $parameterValue = null;

            if ($vi->hasParameter($name)) {
                $parameterValue = $vi->getParameter($name);
            } elseif ($vi->hasParameter($sqlName)) {
                $parameterValue = $vi->getParameter($sqlName);
            }

            // if we have a param value, then parse it out
            if ($parameterValue !== null) {
                // if the value is numeric or is parameter is in $noQuotedValue, then there is no need for quotes
                if (!is_numeric($parameterValue) && !isset($noQuotedValue[$name])) {
                    $parameterValue = $this->quote($parameterValue);
                }

                $tableOptions[] = sprintf('%s=%s', $sqlName, $parameterValue);
            }
        }

        return $tableOptions;
    }

    /**
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
     * @param \Propel\Generator\Model\Column $col
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return string
     */
    public function getColumnDDL(Column $col)
    {
        $domain = $col->getDomain();
        $sqlType = $domain->getSqlType();
        $notNullString = $this->getNullString($col->isNotNull());
        $defaultSetting = $this->getColumnDefaultValueDDL($col);

        // Special handling of TIMESTAMP/DATETIME types ...
        // See: http://propel.phpdb.org/trac/ticket/538
        if ($sqlType === 'DATETIME') {
            $def = $domain->getDefaultValue();
            if ($def && $def->isExpression()) {
                // DATETIME values can only have constant expressions
                $sqlType = 'TIMESTAMP';
            }
        } elseif ($sqlType === 'DATE') {
            $def = $domain->getDefaultValue();
            if ($def && $def->isExpression()) {
                throw new EngineException('DATE columns cannot have default *expressions* in MySQL.');
            }
        } elseif ($sqlType === 'TEXT' || $sqlType === 'BLOB') {
            if ($domain->getDefaultValue()) {
                throw new EngineException('BLOB and TEXT columns cannot have DEFAULT values. in MySQL.');
            }
        }

        $ddl = [$this->quoteIdentifier($col->getName())];
        if ($this->hasSize($sqlType) && $col->isDefaultSqlType($this)) {
            $ddl[] = $sqlType . $col->getSizeDefinition();
        } else {
            $ddl[] = $sqlType;
        }
        $colinfo = $col->getVendorInfoForType($this->getDatabaseType());
        if ($colinfo->hasParameter('Unsigned')) {
            $unsigned = $colinfo->getParameter('Unsigned');
            switch (strtoupper($unsigned)) {
                case 'FALSE':
                    break;
                case 'TRUE':
                    $ddl[] = 'UNSIGNED';

                    break;
                default:
                    throw new EngineException('Unexpected value "' . $unsigned . '" for MySQL vendor column parameter "Unsigned", expecting "true" or "false".');
            }
        }
        if ($colinfo->hasParameter('Charset')) {
            $ddl[] = 'CHARACTER SET ' . $this->quote($colinfo->getParameter('Charset'));
        }
        if ($colinfo->hasParameter('Collation')) {
            $ddl[] = 'COLLATE ' . $this->quote($colinfo->getParameter('Collation'));
        } elseif ($colinfo->hasParameter('Collate')) {
            $ddl[] = 'COLLATE ' . $this->quote($colinfo->getParameter('Collate'));
        }
        if ($sqlType === 'TIMESTAMP') {
            if ($notNullString == '') {
                $notNullString = 'NULL';
            }
            if ($defaultSetting == '' && $notNullString === 'NOT NULL') {
                $defaultSetting = 'DEFAULT CURRENT_TIMESTAMP';
            }
            if ($notNullString) {
                $ddl[] = $notNullString;
            }
            if ($defaultSetting) {
                $ddl[] = $defaultSetting;
            }
        } else {
            if ($defaultSetting) {
                $ddl[] = $defaultSetting;
            }
            if ($notNullString) {
                $ddl[] = $notNullString;
            }
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }
        if ($col->getDescription()) {
            $ddl[] = 'COMMENT ' . $this->quote($col->getDescription());
        }

        return implode(' ', $ddl);
    }

    /**
     * Creates a comma-separated list of column names for the index.
     * For MySQL unique indexes there is the option of specifying size, so we cannot simply use
     * the getColumnsList() method.
     *
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    protected function getIndexColumnListDDL(Index $index)
    {
        $list = [];
        foreach ($index->getColumns() as $col) {
            $list[] = $this->quoteIdentifier($col) . ($index->hasColumnSize($col) ? '(' . $index->getColumnSize($col) . ')' : '');
        }

        return implode(', ', $list);
    }

    /**
     * Builds the DDL SQL to drop the primary key of a table.
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
ALTER TABLE %s DROP PRIMARY KEY;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName())
        );
    }

    /**
     * Builds the DDL SQL to add an Index.
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
            $this->getIndexType($index),
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTable()->getName()),
            $this->getIndexColumnListDDL($index)
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
DROP INDEX %s ON %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTable()->getName())
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
            $this->getIndexType($index),
            $this->quoteIdentifier($index->getName()),
            $this->getIndexColumnListDDL($index)
        );
    }

    /**
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    protected function getIndexType(Index $index)
    {
        $type = '';
        $vendorInfo = $index->getVendorInfoForType($this->getDatabaseType());
        if ($vendorInfo && $vendorInfo->getParameter('Index_type')) {
            $type = $vendorInfo->getParameter('Index_type') . ' ';
        } elseif ($index->isUnique()) {
            $type = 'UNIQUE ';
        }

        return $type;
    }

    /**
     * @param \Propel\Generator\Model\Unique $unique
     *
     * @return string
     */
    public function getUniqueDDL(Unique $unique)
    {
        return sprintf(
            'UNIQUE INDEX %s (%s)',
            $this->quoteIdentifier($unique->getName()),
            $this->getIndexColumnListDDL($unique)
        );
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string
     */
    public function getAddForeignKeyDDL(ForeignKey $fk)
    {
        if ($this->supportsForeignKeys($fk->getTable())) {
            return parent::getAddForeignKeyDDL($fk);
        }

        return '';
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
        if ($this->supportsForeignKeys($fk->getTable())) {
            return parent::getForeignKeyDDL($fk);
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string|null
     */
    public function getDropForeignKeyDDL(ForeignKey $fk)
    {
        if (!$this->supportsForeignKeys($fk->getTable())) {
            return '';
        }
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return null;
        }
        $pattern = "
ALTER TABLE %s DROP FOREIGN KEY %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fk->getTable()->getName()),
            $this->quoteIdentifier($fk->getName())
        );
    }

    /**
     * @param string $comment
     *
     * @return string
     */
    public function getCommentBlockDDL($comment)
    {
        $pattern = "
-- ---------------------------------------------------------------------
-- %s
-- ---------------------------------------------------------------------
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

        foreach ($databaseDiff->getModifiedTables() as $tableDiff) {
            $ret .= $this->getModifyTableDDL($tableDiff);
        }

        foreach ($databaseDiff->getAddedTables() as $table) {
            $ret .= $this->getAddTableDDL($table);
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
RENAME TABLE %s TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fromTableName),
            $this->quoteIdentifier($toTableName)
        );
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
ALTER TABLE %s DROP %s;
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
        return $this->getChangeColumnDDL($fromColumn, $toColumn);
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
        return $this->getChangeColumnDDL($columnDiff->getFromColumn(), $columnDiff->getToColumn());
    }

    /**
     * Builds the DDL SQL to change a column
     *
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return string
     */
    public function getChangeColumnDDL(Column $fromColumn, Column $toColumn)
    {
        $pattern = "
ALTER TABLE %s CHANGE %s %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fromColumn->getTable()->getName()),
            $this->quoteIdentifier($fromColumn->getName()),
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
        $ret = '';
        foreach ($columnDiffs as $columnDiff) {
            $ret .= $this->getModifyColumnDDL($columnDiff);
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to add a column
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    public function getAddColumnDDL(Column $column)
    {
        $pattern = "
ALTER TABLE %s ADD %s %s;
";
        $tableColumns = $column->getTable()->getColumns();

        // Default to add first if no column is found before the current one
        $insertPositionDDL = 'FIRST';
        foreach ($tableColumns as $i => $tableColumn) {
            // We found the column, use the one before it if it's not the first
            if ($tableColumn->getName() == $column->getName()) {
                // We have a column that is not the first one
                if ($i > 0) {
                    $insertPositionDDL = 'AFTER ' . $this->quoteIdentifier($tableColumns[$i - 1]->getName());
                }

                break;
            }
        }

        return sprintf(
            $pattern,
            $this->quoteIdentifier($column->getTable()->getName()),
            $this->getColumnDDL($column),
            $insertPositionDDL
        );
    }

    /**
     * Builds the DDL SQL to add a list of columns
     *
     * @param \Propel\Generator\Model\Column[] $columns
     *
     * @return string
     */
    public function getAddColumnsDDL($columns)
    {
        $lines = '';
        foreach ($columns as $column) {
            $lines .= $this->getAddColumnDDL($column);
        }

        return $lines;
    }

    /**
     * @see Platform::supportsSchemas()
     *
     * @return bool
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
        return !in_array($sqlType, [
            'MEDIUMTEXT',
            'LONGTEXT',
            'BLOB',
            'MEDIUMBLOB',
            'LONGBLOB',
        ]);
    }

    /**
     * @return int[]
     */
    public function getDefaultTypeSizes()
    {
        return [
            'char' => 1,
            'tinyint' => 4,
            'smallint' => 6,
            'int' => 11,
            'bigint' => 20,
            'decimal' => 10,
        ];
    }

    /**
     * Escape the string for RDBMS.
     *
     * @param string $text
     *
     * @return string
     */
    public function disconnectedEscapeText($text)
    {
        return addslashes($text);
    }

    /**
     * {@inheritDoc}
     *
     * MySQL documentation says that identifiers cannot contain '.'. Thus it
     * should be safe to split the string by '.' and quote each part individually
     * to allow for a <schema>.<table> or <table>.<column> syntax.
     *
     * @param string $text the identifier
     *
     * @return string the quoted identifier
     */
    public function doQuoting($text)
    {
        return '`' . strtr($text, ['.' => '`.`']) . '`';
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     * @param string $identifier
     * @param string $columnValueAccessor
     * @param string $tab
     *
     * @return string
     */
    public function getColumnBindingPHP(Column $column, $identifier, $columnValueAccessor, $tab = '            ')
    {
        // FIXME - This is a temporary hack to get around apparent bugs w/ PDO+MYSQL
        // See http://pecl.php.net/bugs/bug.php?id=9919
        if ($column->getPDOType() === PDO::PARAM_BOOL) {
            return sprintf(
                "
%s\$stmt->bindValue(%s, (int) %s, PDO::PARAM_INT);",
                $tab,
                $identifier,
                $columnValueAccessor
            );
        }

        return parent::getColumnBindingPHP($column, $identifier, $columnValueAccessor, $tab);
    }
}
