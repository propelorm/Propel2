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
        'datetime'   => PropelTypes::DATE,
        'timestamp'  => PropelTypes::TIMESTAMP,
        'tinyblob'   => PropelTypes::BINARY,
        'blob'       => PropelTypes::BLOB,
        'mediumblob' => PropelTypes::VARBINARY,
        'longblob'   => PropelTypes::LONGVARBINARY,
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

    public function parse(Database $database, array $additionalTables = array())
    {
        if ($this->getGeneratorConfig()) {
            $this->addVendorInfo = $this->getGeneratorConfig()->get()['migrations']['addVendorInfo'];
        }

        $this->parseTables($database);

        foreach ($additionalTables as $table) {
            $this->parseTables($database, $table);
        }

        // Now populate only columns.
        foreach ($database->getTables() as $table) {
            $this->addColumns($table);
        }

        // Now add indexes and constraints.
        foreach ($database->getTables() as $table) {
            $this->addIndexes($table);
            $this->addForeignKeys($table);
        }

        return count($database->getTables());
    }

    protected function parseTables(Database $database, Table $filterTable = null)
    {
        $sql = "
        SELECT name
        FROM sqlite_master
        WHERE type='table'
        %filter%
        UNION ALL
        SELECT name
        FROM sqlite_temp_master
        WHERE type='table'
        %filter%
        ORDER BY name;";

        $filter = '';

        if ($filterTable) {
            if ($schema = $filterTable->getSchema()) {
                $filter = sprintf(" AND name LIKE '%s§%%'", $schema);
            }
            $filter .= sprintf(" AND (name = '%s' OR name LIKE '%%§%1\$s')", $filterTable->getCommonName());
        } else if ($schema = $database->getSchema()) {
            $filter = sprintf(" AND name LIKE '%s§%%'", $schema);
        }

        $sql = str_replace('%filter%', $filter, $sql);

        $dataFetcher = $this->dbh->query($sql);

        // First load the tables (important that this happen before filling out details of tables)
        foreach ($dataFetcher as $row) {
            $tableName = $row[0];
            $tableSchema = '';

            if ('sqlite_' == substr($tableName, 0, 7)) {
                continue;
            }

            if (false !== ($pos = strpos($tableName, '§'))) {
                $tableSchema = substr($tableName, 0, $pos);
                $tableName = substr($tableName, $pos + 2);
            }

            $table = new Table($tableName);

            if ($filterTable && $filterTable->getSchema()) {
                $table->setSchema($filterTable->getSchema());
            } else {
                if (!$database->getSchema() && $tableSchema) {
                    //we have no schema to filter, but this belongs to one, so next
                    continue;
                }
            }

            if ($tableName === $this->getMigrationTable()) {
                continue;
            }

            $table->setIdMethod($database->getDefaultIdMethod());
            $database->addTable($table);
        }
    }

    /**
     * Adds Columns to the specified table.
     *
     * @param Table $table The Table model class to add columns to.
     */
    protected function addColumns(Table $table)
    {
        $tableName = $table->getName();

//        var_dump("PRAGMA table_info('$tableName') //");
        $stmt = $this->dbh->query("PRAGMA table_info('$tableName')");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $name = $row['name'];

            $fulltype = $row['type'];
            $size = null;
            $scale = null;

            if (preg_match('/^([^\(]+)\(\s*(\d+)\s*,\s*(\d+)\s*\)$/', $fulltype, $matches)) {
                $type = $matches[1];
                $size = $matches[2];
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
            // We may want to provide an option to include this:
            // $column->getDomain()->replaceSqlType($type);
            $column->getDomain()->replaceSize($size);
            $column->getDomain()->replaceScale($scale);

            if (null !== $default) {
                if ("'" !== substr($default, 0, 1) && strpos($default, '(')) {
                    $defaultType = ColumnDefaultValue::TYPE_EXPR;
                    if ('datetime(CURRENT_TIMESTAMP, \'localtime\')' === $default) {
                            $default = 'CURRENT_TIMESTAMP';
                        }
                } else {
                    $defaultType = ColumnDefaultValue::TYPE_VALUE;
                    $default = str_replace("'", '', $default);
                }
                $column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, $defaultType));
            }

            $column->setNotNull($notNull);

            if (0 < $row['pk']+0) {
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
        $database = $table->getDatabase();

        $stmt = $this->dbh->query('PRAGMA foreign_key_list("' . $table->getName() . '")');

        $lastId = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($lastId !== $row['id']) {
                $fk = new ForeignKey();

                $onDelete = $row['on_delete'];
                if ($onDelete && 'NO ACTION' !== $onDelete) {
                    $fk->setOnDelete($onDelete);
                }

                $onUpdate = $row['on_update'];
                if ($onUpdate && 'NO ACTION' !== $onUpdate) {
                    $fk->setOnUpdate($onUpdate);
                }

                $foreignTable = $database->getTable($row['table'], true);

                if (!$foreignTable) {
                    continue;
                }

                // we need the reference earlier to build the FK name in Table class to prevent adding FK twice
                $fk->addReference($row['from'], $row['to']);
                $fk->setForeignTableCommonName($foreignTable->getCommonName());
                $table->addForeignKey($fk);

                $fk->setForeignTableCommonName($foreignTable->getCommonName());
                if ($table->guessSchemaName() != $foreignTable->guessSchemaName()) {
                    $fk->setForeignSchemaName($foreignTable->guessSchemaName());
                }
                $lastId = $row['id'];
            } else {
                $fk->addReference($row['from'], $row['to']);
            }
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
            $internalName = $name;

            if (0 === strpos($name, 'sqlite_autoindex')) {
                $internalName = '';
            }

            $index = $row['unique'] ? new Unique($internalName) : new Index($internalName);

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

            if ($index instanceof Unique) {
                $table->addUnique($index);
            } else {
                $table->addIndex($index);
            }
        }
    }
}
