<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Unique;
use Propel\Generator\Reverse\AbstractSchemaParser;

/**
 * SQLite database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqliteSchemaParser extends AbstractSchemaParser
{

    /**
     * Map Sqlite native types to Propel types.
     *
     * There really aren't any SQLite native types, so we're just
     * using the MySQL ones here.
     *
     * @var array
     */
    private static $sqliteTypeMap = array(
        'tinyint'    => PropelTypes::TINYINT,
        'smallint'   => PropelTypes::SMALLINT,
        'mediumint'  => PropelTypes::SMALLINT,
        'int'        => PropelTypes::INTEGER,
        'integer'    => PropelTypes::INTEGER,
        'bigint'     => PropelTypes::BIGINT,
        'int24'      => PropelTypes::BIGINT,
        'real'       => PropelTypes::REAL,
        'float'      => PropelTypes::FLOAT,
        'decimal'    => PropelTypes::DECIMAL,
        'numeric'    => PropelTypes::NUMERIC,
        'double'     => PropelTypes::DOUBLE,
        'char'       => PropelTypes::CHAR,
        'varchar'    => PropelTypes::VARCHAR,
        'date'       => PropelTypes::DATE,
        'time'       => PropelTypes::TIME,
        'year'       => PropelTypes::INTEGER,
        'datetime'   => PropelTypes::TIMESTAMP,
        'timestamp'  => PropelTypes::TIMESTAMP,
        'tinyblob'   => PropelTypes::BINARY,
        'blob'       => PropelTypes::BLOB,
        'mediumblob' => PropelTypes::BLOB,
        'longblob'   => PropelTypes::BLOB,
        'longtext'   => PropelTypes::CLOB,
        'tinytext'   => PropelTypes::VARCHAR,
        'mediumtext' => PropelTypes::LONGVARCHAR,
        'text'       => PropelTypes::LONGVARCHAR,
        'enum'       => PropelTypes::CHAR,
        'set'        => PropelTypes::CHAR,
    );

    /**
     * Gets a type mapping from native types to Propel types
     *
     * @return array
     */
    protected function getTypeMapping()
    {
        return self::$sqliteTypeMap;
    }

    /**
     *
     */
    public function parse(Database $database)
    {
        $dataFetcher = $this->dbh->query("
        SELECT name
        FROM sqlite_master
        WHERE type='table'
        UNION ALL
        SELECT name
        FROM sqlite_temp_master
        WHERE type='table'
        ORDER BY name;");

        // First load the tables (important that this happen before filling out details of tables)
        $tables = array();
        foreach ($dataFetcher as $row) {
            $name = $row[0];
            $commonName = '';

            if ('sqlite_' == substr($name, 0, 7)) {
                continue;
            }

            if ($database->getSchema()) {
                if (false !== ($pos = strpos($name, '§'))) {
                    if ($database->getSchema()) {
                        if ($database->getSchema() !== substr($name, 0, $pos)) {
                            continue;
                        } else {
                            $commonName = substr($name, $pos+2); //2 because the delimiter § uses in UTF8 one byte more.
                        }
                    }
                } else {
                    continue;
                }
            }

            if ($name === $this->getMigrationTable()) {
                continue;
            }

            $table = new Table($commonName ?: $name);
            $table->setIdMethod($database->getDefaultIdMethod());
            $database->addTable($table);
            $tables[] = $table;
        }

        // Now populate only columns.
        foreach ($tables as $table) {
            $this->addColumns($table);
        }

        // Now add indexes and constraints.
        foreach ($tables as $table) {
            $this->addIndexes($table);
            $this->addForeignKeys($table);
        }

        return count($tables);
    }

    /**
     * Adds Columns to the specified table.
     *
     * @param Table $table The Table model class to add columns to.
     */
    protected function addColumns(Table $table)
    {
        $stmt = $this->dbh->query("PRAGMA table_info('" . $table->getName() . "')");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $name = $row['name'];

            $fulltype = $row['type'];
            $size = null;
            $scale = null;

            if (preg_match('/^([^\(]+)\(\s*(\d+)\s*,\s*(\d+)\s*\)$/', $fulltype, $matches)) {
                $type = $matches[1];
                $scale = $matches[3];
            } elseif (preg_match('/^([^\(]+)\(\s*(\d+)\s*\)$/', $fulltype, $matches)) {
                $type = $matches[1];
                $size = $matches[2];
            } else {
                $type = $fulltype;
            }
            $notNull = $row['notnull'];
            $default = $row['dflt_value'];

            $propelType = $this->getMappedPropelType(strtolower($type));

            if (!$propelType) {
                $propelType = Column::DEFAULT_TYPE;
                $this->warn('Column [' . $table->getName() . '.' . $name. '] has a column type ('.$type.') that Propel does not support.');
            }

            $column = new Column($name);
            $column->setTable($table);
            $column->setDomainForType($propelType);
            $column->getDomain()->setOriginSqlType(strtolower($type));
            // We may want to provide an option to include this:
            // $column->getDomain()->replaceSqlType($type);
            $column->getDomain()->replaceSize($size);
            $column->getDomain()->replaceScale($scale);

            if (null !== $default) {
                $column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, ColumnDefaultValue::TYPE_VALUE));
            }

            $column->setNotNull($notNull);

            if (1 == $row['pk']) {
                $column->setPrimaryKey(true);
            }

            if ($column->isPrimaryKey()) {
                // check if autoIncrement
                $autoIncrementStmt = $this->dbh->prepare('
                SELECT tbl_name
                FROM sqlite_master
                WHERE
                  tbl_name = ?
                AND
                  sql LIKE "%AUTOINCREMENT%"
                ');
                $autoIncrementStmt->execute([$table->getName()]);
                $autoincrementRow = $autoIncrementStmt->fetch(\PDO::FETCH_ASSOC);
                if ($autoincrementRow && $autoincrementRow['tbl_name'] == $table->getName()) {
                    $column->setAutoIncrement(true);
                }
            }

            $table->addColumn($column);
        }
    }

    protected function addForeignKeys(Table $table)
    {
        $stmt = $this->dbh->query('PRAGMA foreign_key_list("' . $table->getName() . '")');

        $lastId = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($lastId !== $row['id']) {
                $fk = new ForeignKey();

                $tableName   = $row['table'];
                $tableSchema = '';

                if (false !== ($pos = strpos($tableName, '§'))) {
                    $tableName = substr($tableName, $pos + 2);
                    $tableSchema = substr($tableName, 0, $pos);
                }

                $fk->setForeignTableCommonName($tableName);
                if ($table->getDatabase()->getSchema() != $tableSchema) {
                    $fk->setForeignSchemaName($tableSchema);
                }

                $fk->setOnDelete($row['on_delete']);
                $fk->setOnUpdate($row['on_update']);
                $table->addForeignKey($fk);
                $lastId = $row['id'];
            }

            $fk->addReference($row['from'], $row['to']);
        }
    }

    /**
     * Load indexes for this table
     */
    protected function addIndexes(Table $table)
    {
        $stmt = $this->dbh->query('PRAGMA index_list("' . $table->getName() . '")');

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $name = $row['name'];

            $index = $row['unique'] ? new Unique() : new Index();

            $stmt2 = $this->dbh->query("PRAGMA index_info('".$name."')");
            while ($row2 = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $colname = $row2['name'];
                $index->addColumn($table->getColumn($colname));
            }

            if (1 === count($table->getPrimaryKey()) && 1 === count($index->getColumns())) {
                // exclude the primary unique index, since it's autogenerated by sqlite
                if ($table->getPrimaryKey()[0]->getName() === $index->getColumns()[0]) {
                    continue;
                }
            }

            $table->addIndex($index);
        }
    }
}
