<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Reverse;

use PDO;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;

/**
 * Oracle database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Guillermo Gutierrez <ggutierrez@dailycosas.net> (Adaptation)
 */
class OracleSchemaParser extends AbstractSchemaParser
{
    /**
     * Map Oracle native types to Propel types.
     *
     * There really aren't any Oracle native types, so we're just
     * using the MySQL ones here.
     *
     * Left as unsupported:
     *   BFILE,
     *   RAW,
     *   ROWID
     *
     * Supported but non existent as a specific type in Oracle:
     *   DECIMAL (NUMBER with scale),
     *   DOUBLE (FLOAT with precision = 126)
     *
     * @var string[]
     */
    private static $oracleTypeMap = [
        'BLOB' => PropelTypes::BLOB,
        'CHAR' => PropelTypes::CHAR,
        'CLOB' => PropelTypes::CLOB,
        'DATE' => PropelTypes::TIMESTAMP,
        'BIGINT' => PropelTypes::BIGINT,
        'DECIMAL' => PropelTypes::DECIMAL,
        'DOUBLE' => PropelTypes::DOUBLE,
        'FLOAT' => PropelTypes::FLOAT,
        'LONG' => PropelTypes::LONGVARCHAR,
        'NCHAR' => PropelTypes::CHAR,
        'NCLOB' => PropelTypes::CLOB,
        'NUMBER' => PropelTypes::INTEGER,
        'NVARCHAR2' => PropelTypes::VARCHAR,
        'TIMESTAMP' => PropelTypes::TIMESTAMP,
        'VARCHAR2' => PropelTypes::VARCHAR,
    ];

    /**
     * Gets a type mapping from native types to Propel types
     *
     * @return string[]
     */
    protected function getTypeMapping()
    {
        return self::$oracleTypeMap;
    }

    /**
     * Searches for tables in the database. Maybe we want to search also the views.
     *
     * @param \Propel\Generator\Model\Database $database The Database model class to add tables to.
     * @param \Propel\Generator\Model\Table[] $additionalTables
     *
     * @return int
     */
    public function parse(Database $database, array $additionalTables = [])
    {
        $tables = [];
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query("SELECT OBJECT_NAME FROM USER_OBJECTS WHERE OBJECT_TYPE = 'TABLE'");

        $seqPattern = $this->getGeneratorConfig()->get()['database']['adapters']['oracle']['autoincrementSequencePattern'];

        // First load the tables (important that this happen before filling out details of tables)
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strpos($row['OBJECT_NAME'], '$') !== false) {
                // this is an Oracle internal table or materialized view - prune
                continue;
            }
            if (strtoupper($row['OBJECT_NAME']) === strtoupper($this->getMigrationTable())) {
                continue;
            }
            $table = new Table($row['OBJECT_NAME']);
            $table->setIdMethod($database->getDefaultIdMethod());
            $database->addTable($table);
            // Add columns, primary keys and indexes.
            $this->addColumns($table);
            $this->addPrimaryKey($table);
            $this->addIndexes($table);

            $pkColumns = $table->getPrimaryKey();
            if (count($pkColumns) === 1 && $seqPattern) {
                $seqName = str_replace('${table}', $table->getName(), $seqPattern);
                $seqName = strtoupper($seqName);

                /** @var \PDOStatement $stmt2 */
                $stmt2 = $this->dbh->query("SELECT * FROM USER_SEQUENCES WHERE SEQUENCE_NAME = '" . $seqName . "'");
                $hasSeq = $stmt2->fetch(PDO::FETCH_ASSOC);

                if ($hasSeq) {
                    $pkColumns[0]->setAutoIncrement(true);
                    $idMethodParameter = new IdMethodParameter();
                    $idMethodParameter->setValue($seqName);
                    $table->addIdMethodParameter($idMethodParameter);
                }
            }

            $tables[] = $table;
        }

        foreach ($tables as $table) {
            $this->addForeignKeys($table);
        }

        return count($tables);
    }

    /**
     * Adds Columns to the specified table.
     *
     * @param \Propel\Generator\Model\Table $table The Table model class to add columns to.
     *
     * @return void
     */
    protected function addColumns(Table $table)
    {
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query("SELECT COLUMN_NAME, DATA_TYPE, NULLABLE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, DATA_DEFAULT FROM USER_TAB_COLS WHERE TABLE_NAME = '" . $table->getName() . "'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strpos($row['COLUMN_NAME'], '$') !== false) {
                // this is an Oracle internal column - prune
                continue;
            }
            $size = $row['DATA_PRECISION'] ? $row['DATA_PRECISION'] : $row['DATA_LENGTH'];
            $scale = $row['DATA_SCALE'];
            $default = $row['DATA_DEFAULT'];
            $type = $row['DATA_TYPE'];
            $isNullable = ($row['NULLABLE'] === 'Y');
            if ($type === 'NUMBER' && $row['DATA_SCALE'] > 0) {
                $type = 'DECIMAL';
            }
            if ($type === 'NUMBER' && $size > 9) {
                $type = 'BIGINT';
            }
            if ($type === 'FLOAT' && $row['DATA_PRECISION'] == 126) {
                $type = 'DOUBLE';
            }
            if (strpos($type, 'TIMESTAMP(') !== false) {
                $type = substr($type, 0, strpos($type, '('));
                $default = '0000-00-00 00:00:00';
                $size = null;
                $scale = null;
            }
            if ($type === 'DATE') {
                $default = '0000-00-00';
                $size = null;
                $scale = null;
            }

            $propelType = $this->getMappedPropelType($type);
            if (!$propelType) {
                $propelType = Column::DEFAULT_TYPE;
                $this->warn('Column [' . $table->getName() . '.' . $row['COLUMN_NAME'] . '] has a column type (' . $row['DATA_TYPE'] . ') that Propel does not support.');
            }

            $column = new Column($row['COLUMN_NAME']);
            $column->setPhpName(); // Prevent problems with strange col names
            $column->setTable($table);
            $column->setDomainForType($propelType);
            $column->getDomain()->replaceSize($size);
            $column->getDomain()->replaceScale($scale);
            if ($default !== null) {
                $column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, ColumnDefaultValue::TYPE_VALUE));
            }
            $column->setAutoIncrement(false); // This flag sets in self::parse()
            $column->setNotNull(!$isNullable);
            $table->addColumn($column);
        }
    }

    /**
     * Adds Indexes to the specified table.
     *
     * @param \Propel\Generator\Model\Table $table The Table model class to add columns to.
     *
     * @return void
     */
    protected function addIndexes(Table $table)
    {
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query("SELECT INDEX_NAME, COLUMN_NAME FROM USER_IND_COLUMNS WHERE TABLE_NAME = '" . $table->getName() . "' ORDER BY COLUMN_NAME");

        $indices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $indices[$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
        }

        foreach ($indices as $indexName => $columnNames) {
            $index = new Index($indexName);
            foreach ($columnNames as $columnName) {
                // Oracle deals with complex indices using an internal reference, so...
                // let's ignore this kind of index
                if ($table->hasColumn($columnName)) {
                    $index->addColumn($table->getColumn($columnName));
                }
            }
            // since some of the columns are pruned above, we must only add an index if it has columns
            if ($index->hasColumns()) {
                $table->addIndex($index);
            }
        }
    }

    /**
     * Load foreign keys for this table.
     *
     * @param \Propel\Generator\Model\Table $table The Table model class to add FKs to
     *
     * @return void
     */
    protected function addForeignKeys(Table $table)
    {
        // local store to avoid duplicates
        $foreignKeys = [];

        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query("SELECT CONSTRAINT_NAME, DELETE_RULE, R_CONSTRAINT_NAME FROM USER_CONSTRAINTS WHERE CONSTRAINT_TYPE = 'R' AND TABLE_NAME = '" . $table->getName() . "'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Local reference
            /** @var \PDOStatement $stmt2 */
            $stmt2 = $this->dbh->query("SELECT COLUMN_NAME FROM USER_CONS_COLUMNS WHERE CONSTRAINT_NAME = '" . $row['CONSTRAINT_NAME'] . "' AND TABLE_NAME = '" . $table->getName() . "'");
            $localReferenceInfo = $stmt2->fetch(PDO::FETCH_ASSOC);

            // Foreign reference
            /** @var \PDOStatement $stmt3 */
            $stmt3 = $this->dbh->query("SELECT TABLE_NAME, COLUMN_NAME FROM USER_CONS_COLUMNS WHERE CONSTRAINT_NAME = '" . $row['R_CONSTRAINT_NAME'] . "'");
            $foreignReferenceInfo = $stmt3->fetch(PDO::FETCH_ASSOC);

            if (!isset($foreignKeys[$row['CONSTRAINT_NAME']])) {
                $fk = new ForeignKey($row['CONSTRAINT_NAME']);
                $fk->setForeignTableCommonName($foreignReferenceInfo['TABLE_NAME']);
                $onDelete = ($row['DELETE_RULE'] === 'NO ACTION') ? 'NONE' : $row['DELETE_RULE'];
                $fk->setOnDelete($onDelete);
                $fk->setOnUpdate($onDelete);
                $fk->addReference(['local' => $localReferenceInfo['COLUMN_NAME'], 'foreign' => $foreignReferenceInfo['COLUMN_NAME']]);
                $table->addForeignKey($fk);
                $foreignKeys[$row['CONSTRAINT_NAME']] = $fk;
            }
        }
    }

    /**
     * Loads the primary key for this table.
     *
     * @param \Propel\Generator\Model\Table $table The Table model class to add PK to.
     *
     * @return void
     */
    protected function addPrimaryKey(Table $table)
    {
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query("SELECT COLS.COLUMN_NAME FROM USER_CONSTRAINTS CONS, USER_CONS_COLUMNS COLS WHERE CONS.CONSTRAINT_NAME = COLS.CONSTRAINT_NAME AND CONS.TABLE_NAME = '" . $table->getName() . "' AND CONS.CONSTRAINT_TYPE = 'P'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // This fixes a strange behavior by PDO. Sometimes the
            // row values are inside an index 0 of an array
            if (isset($row[0])) {
                $row = $row[0];
            }
            $table->getColumn($row['COLUMN_NAME'])->setPrimaryKey(true);
        }
    }
}
