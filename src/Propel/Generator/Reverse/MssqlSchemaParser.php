<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Reverse;

// TODO: to remove
use PDO;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use RuntimeException;

/**
 * Microsoft SQL Server database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Dominic Winkler <d.winkler@flexarts.at> (Flexarts)
 */
class MssqlSchemaParser extends AbstractSchemaParser
{
    /**
     * Map MSSQL native types to Propel types.
     *
     * @var array<string>
     */
    private static $mssqlTypeMap = [
        'binary' => PropelTypes::BINARY,
        'bit' => PropelTypes::BOOLEAN,
        'char' => PropelTypes::CHAR,
        'datetime' => PropelTypes::TIMESTAMP,
        'decimal() identity' => PropelTypes::DECIMAL,
        'decimal' => PropelTypes::DECIMAL,
        'image' => PropelTypes::LONGVARBINARY,
        'int' => PropelTypes::INTEGER,
        'int identity' => PropelTypes::INTEGER,
        'integer' => PropelTypes::INTEGER,
        'money' => PropelTypes::DECIMAL,
        'nchar' => PropelTypes::CHAR,
        'ntext' => PropelTypes::LONGVARCHAR,
        'numeric() identity' => PropelTypes::NUMERIC,
        'numeric' => PropelTypes::NUMERIC,
        'nvarchar' => PropelTypes::VARCHAR,
        'real' => PropelTypes::REAL,
        'float' => PropelTypes::FLOAT,
        'smalldatetime' => PropelTypes::TIMESTAMP,
        'smallint' => PropelTypes::SMALLINT,
        'smallint identity' => PropelTypes::SMALLINT,
        'smallmoney' => PropelTypes::DECIMAL,
        'sysname' => PropelTypes::VARCHAR,
        'text' => PropelTypes::LONGVARCHAR,
        'timestamp' => PropelTypes::BINARY,
        'tinyint identity' => PropelTypes::TINYINT,
        'tinyint' => PropelTypes::TINYINT,
        'uniqueidentifier' => PropelTypes::UUID,
        'varbinary' => PropelTypes::VARBINARY,
        'varbinary(max)' => PropelTypes::CLOB,
        'varchar' => PropelTypes::VARCHAR,
        'varchar(max)' => PropelTypes::CLOB,
        'geometry' => PropelTypes::GEOMETRY,
        // SQL Server 2000 only
        'bigint identity' => PropelTypes::BIGINT,
        'bigint' => PropelTypes::BIGINT,
        'sql_variant' => PropelTypes::VARCHAR,
    ];

    /**
     * @see AbstractSchemaParser::getTypeMapping()
     *
     * @return array<string>
     */
    protected function getTypeMapping(): array
    {
        return self::$mssqlTypeMap;
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     * @param array $additionalTables
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function parse(Database $database, array $additionalTables = []): int
    {
        $dataFetcher = $this->dbh->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME <> 'dtproperties'");

        if ($dataFetcher === false) {
            throw new RuntimeException('PdoConnection::query() did not return a result set as a statement object.');
        }

        // First load the tables (important that this happens before filling out details of tables)
        $tables = [];
        foreach ($dataFetcher as $row) {
            $name = $this->cleanDelimitedIdentifiers($row[0]);
            if ($name === $this->getMigrationTable()) {
                continue;
            }
            $table = new Table($name);
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
            $this->addForeignKeys($table);
            $this->addIndexes($table);
            $this->addPrimaryKey($table);
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
    protected function addColumns(Table $table): void
    {
        /** @var \Propel\Runtime\DataFetcher\PDODataFetcher $dataFetcher */
        $dataFetcher = $this->dbh->query("sp_columns '" . $table->getName() . "'");
        $dataFetcher->setStyle(PDO::FETCH_ASSOC);

        foreach ($dataFetcher as $row) {
            $name = $this->cleanDelimitedIdentifiers($row['COLUMN_NAME']);
            $type = $row['TYPE_NAME'];
            $size = $row['LENGTH'];
            $isNullable = $row['NULLABLE'];
            $default = $row['COLUMN_DEF'];
            $scale = $row['SCALE'];
            $autoincrement = false;
            if (strtolower($type) === 'int identity') {
                $autoincrement = true;
            }

            $propelType = $this->getMappedPropelType($type);
            if (!$propelType) {
                $propelType = Column::DEFAULT_TYPE;
                $this->warn(sprintf('Column [%s.%s] has a column type (%s) that Propel does not support.', $table->getName(), $name, $type));
            }

            $column = new Column($name);
            $column->setTable($table);
            $column->setDomainForType($propelType);
            // We may want to provide an option to include this:
            // $column->getDomain()->replaceSqlType($type);
            $column->getDomain()->replaceSize($size);
            $column->getDomain()->replaceScale($scale);
            if ($default !== null) {
                $column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, ColumnDefaultValue::TYPE_VALUE));
            }
            $column->setAutoIncrement($autoincrement);
            $column->setNotNull(!$isNullable);

            $table->addColumn($column);
        }
    }

    /**
     * Load foreign keys for this table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addForeignKeys(Table $table): void
    {
        $database = $table->getDatabase();

        /** @var \Propel\Runtime\DataFetcher\PDODataFetcher $dataFetcher */
        $dataFetcher = $this->dbh->query("select fk.name as CONSTRAINT_NAME, lcol.name as COLUMN_NAME, rtab.name as FK_TABLE_NAME, rcol.name as FK_COLUMN_NAME
         from sys.foreign_keys as fk
         inner join sys.foreign_key_columns ref on ref.constraint_object_id = fk.object_id
         inner join sys.columns lcol on lcol.object_id = ref.parent_object_id and lcol.column_id = ref.parent_column_id
         inner join sys.columns rcol on rcol.object_id = ref.referenced_object_id and rcol.column_id = ref.referenced_column_id
         inner join sys.tables rtab on rtab.object_id = ref.referenced_object_id
         where fk.parent_object_id = OBJECT_ID('" . $table->getName() . "')");
        $dataFetcher->setStyle(PDO::FETCH_ASSOC);

        $foreignKeys = []; // local store to avoid duplicates
        foreach ($dataFetcher as $row) {
            $name = $this->cleanDelimitedIdentifiers($row['CONSTRAINT_NAME']);
            $lcol = $this->cleanDelimitedIdentifiers($row['COLUMN_NAME']);
            $ftbl = $this->cleanDelimitedIdentifiers($row['FK_TABLE_NAME']);
            $fcol = $this->cleanDelimitedIdentifiers($row['FK_COLUMN_NAME']);

            $foreignTable = $database->getTable($ftbl);
            $foreignColumn = $foreignTable->getColumn($fcol);
            $localColumn = $table->getColumn($lcol);

            if (!isset($foreignKeys[$name])) {
                $fk = new ForeignKey($name);
                $fk->setForeignTableCommonName($foreignTable->getCommonName());
                $fk->setForeignSchemaName($foreignTable->getSchema());
                //$fk->setOnDelete($fkactions['ON DELETE']);
                //$fk->setOnUpdate($fkactions['ON UPDATE']);
                $table->addForeignKey($fk);
                $foreignKeys[$name] = $fk;
            }
            $foreignKeys[$name]->addReference($localColumn, $foreignColumn);
        }
    }

    /**
     * Load indexes for this table
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addIndexes(Table $table): void
    {
        /** @var \Propel\Runtime\DataFetcher\PDODataFetcher $dataFetcher */
        $dataFetcher = $this->dbh->query("sp_indexes_rowset '" . $table->getName() . "'");
        $dataFetcher->setStyle(PDO::FETCH_ASSOC);

        $indexes = [];
        foreach ($dataFetcher as $row) {
            $colName = $this->cleanDelimitedIdentifiers($row['COLUMN_NAME']);
            $name = $this->cleanDelimitedIdentifiers($row['INDEX_NAME']);

            $isPk = $this->cleanDelimitedIdentifiers($row['PRIMARY_KEY']);
            $isUnique = $this->cleanDelimitedIdentifiers($row['UNIQUE']);

            $localColumn = $table->getColumn($colName);

            // ignore PRIMARY index
            if ($isPk) {
                continue;
            }

            if (!isset($indexes[$name])) {
                if ($isUnique) {
                    $indexes[$name] = new Unique($name);
                } else {
                    $indexes[$name] = new Index($name);
                }
                $indexes[$name]->setTable($table);
            }

            $indexes[$name]->addColumn($localColumn);
        }

        foreach ($indexes as $index) {
            if ($index instanceof Unique) {
                $table->addUnique($index);
            } else {
                $table->addIndex($index);
            }
        }
    }

    /**
     * Loads the primary key for this table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function addPrimaryKey(Table $table): void
    {
        $dataFetcher = $this->dbh->query("SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            INNER JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE ON
            INFORMATION_SCHEMA.TABLE_CONSTRAINTS.CONSTRAINT_NAME = INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE.constraint_name
            WHERE     (INFORMATION_SCHEMA.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'PRIMARY KEY') AND
            (INFORMATION_SCHEMA.TABLE_CONSTRAINTS.TABLE_NAME = '" . $table->getName() . "')");

        if ($dataFetcher === false) {
            throw new RuntimeException('PdoConnection::query() did not return a result set as a statement object.');
        }

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.
        foreach ($dataFetcher as $row) {
            $name = $this->cleanDelimitedIdentifiers($row[0]);
            $table->getColumn($name)->setPrimaryKey(true);
        }
    }

    /**
     * according to the identifier definition, we have to clean simple quote (') around the identifier name
     * returns by mssql
     *
     * @see http://msdn.microsoft.com/library/ms175874.aspx
     *
     * @param string $identifier
     *
     * @return string
     */
    protected function cleanDelimitedIdentifiers(string $identifier): string
    {
        return preg_replace('/^\'(.*)\'$/U', '$1', $identifier);
    }
}
