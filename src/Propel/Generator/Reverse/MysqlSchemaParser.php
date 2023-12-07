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
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Runtime\Connection\ConnectionInterface;
use RuntimeException;

/**
 * Mysql database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class MysqlSchemaParser extends AbstractSchemaParser
{
    /**
     * @var bool
     */
    private $addVendorInfo = false;

    /**
     * Map MySQL native types to Propel types.
     *
     * @var array<string>
     */
    private static $mysqlTypeMap = [
        'tinyint' => PropelTypes::TINYINT,
        'smallint' => PropelTypes::SMALLINT,
        'mediumint' => PropelTypes::SMALLINT,
        'int' => PropelTypes::INTEGER,
        'integer' => PropelTypes::INTEGER,
        'bigint' => PropelTypes::BIGINT,
        'int24' => PropelTypes::BIGINT,
        'real' => PropelTypes::DOUBLE,
        'float' => PropelTypes::FLOAT,
        'decimal' => PropelTypes::DECIMAL,
        'numeric' => PropelTypes::NUMERIC,
        'double' => PropelTypes::DOUBLE,
        'char' => PropelTypes::CHAR,
        'varchar' => PropelTypes::VARCHAR,
        'date' => PropelTypes::DATE,
        'time' => PropelTypes::TIME,
        'year' => PropelTypes::INTEGER,
        'datetime' => PropelTypes::DATETIME,
        'timestamp' => PropelTypes::TIMESTAMP,
        'tinyblob' => PropelTypes::BINARY,
        'blob' => PropelTypes::BLOB,
        'mediumblob' => PropelTypes::VARBINARY,
        'longblob' => PropelTypes::LONGVARBINARY,
        'longtext' => PropelTypes::CLOB,
        'tinytext' => PropelTypes::VARCHAR,
        'mediumtext' => PropelTypes::LONGVARCHAR,
        'text' => PropelTypes::LONGVARCHAR,
        'enum' => PropelTypes::CHAR,
        'set' => PropelTypes::CHAR,
        'binary' => PropelTypes::BINARY,
        'uuid' => PropelTypes::UUID, // for MariaDB
    ];

    /**
     * @var array<int>
     */
    protected static $defaultTypeSizes = [
        'char' => 1,
        'tinyint' => 4,
        'smallint' => 6,
        'int' => 11,
        'bigint' => 20,
        'decimal' => 10,
    ];

    /**
     * Gets a type mapping from native types to Propel types
     *
     * @return array<string>
     */
    protected function getTypeMapping(): array
    {
        return self::$mysqlTypeMap;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $dbh Optional database connection
     */
    public function __construct(?ConnectionInterface $dbh = null)
    {
        parent::__construct($dbh);

        $this->setPlatform(new MysqlPlatform());
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     * @param array<\Propel\Generator\Model\Table> $additionalTables
     *
     * @return int
     */
    public function parse(Database $database, array $additionalTables = []): int
    {
        if ($this->getGeneratorConfig() !== null) {
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

        // Now add indices and constraints.
        foreach ($database->getTables() as $table) {
            $this->addForeignKeys($table);
            $this->addIndexes($table);
            $this->addPrimaryKey($table);

            $this->addTableVendorInfo($table);
            $this->addDescriptionToTable($table);
            $this->addColumnDescriptionsToTable($table);
        }

        return count($database->getTables());
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     * @param \Propel\Generator\Model\Table|null $filterTable
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function parseTables(Database $database, ?Table $filterTable = null): void
    {
        $sql = 'SHOW FULL TABLES';

        if ($filterTable) {
            $schema = $filterTable->getSchema();
            if ($schema) {
                $sql .= ' FROM ' . $database->getPlatform()->doQuoting($schema);
            }

            $sql .= sprintf(" LIKE '%s'", $filterTable->getCommonName());
        } else {
            $schema = $database->getSchema();
            if ($schema) {
                $sql .= ' FROM ' . $database->getPlatform()->doQuoting($schema);
            }
        }

        $dataFetcher = $this->dbh->query($sql);

        if ($dataFetcher === false) {
            throw new RuntimeException('PdoConnection::query() did not return a result set as a statement object.');
        }

        // First load the tables (important that this happens before filling out details of tables)
        foreach ($dataFetcher as $row) {
            $name = $row[0];
            $type = $row[1];

            if ($name == $this->getMigrationTable() || $type !== 'BASE TABLE') {
                continue;
            }

            $table = new Table($name);
            $table->setIdMethod($database->getDefaultIdMethod());
            if ($filterTable && $filterTable->getSchema()) {
                $table->setSchema($filterTable->getSchema());
            }
            $database->addTable($table);
        }
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
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query(sprintf('SHOW COLUMNS FROM %s', $this->getPlatform()->doQuoting($table->getName())));

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $column = $this->getColumnFromRow($row, $table);
            $table->addColumn($column);
        }
    }

    /**
     * Factory method creating a Column object
     * based on a row from the 'show columns from ' MySQL query result.
     *
     * @param array $row An associative array with the following keys:
     *                     Field, Type, Null, Key, Default, Extra.
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Model\Column
     */
    public function getColumnFromRow(array $row, Table $table): Column
    {
        $name = $row['Field'];
        $isNullable = ($row['Null'] === 'YES');
        $autoincrement = (strpos($row['Extra'], 'auto_increment') !== false);
        $size = null;
        $scale = null;
        $sqlType = false;

        $regexp = '/^
            (\w+)        # column type [1]
            [\(]         # (
                ?([\d,]*)  # size or size, precision [2]
            [\)]         # )
            ?\s*         # whitespace
            (\w*)        # extra description (UNSIGNED, CHARACTER SET, ...) [3]
        $/x';
        if (preg_match($regexp, $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($matches[2]) {
                $cpos = strpos($matches[2], ',');
                if ($cpos !== false) {
                    $size = (int)substr($matches[2], 0, $cpos);
                    $scale = (int)substr($matches[2], $cpos + 1);
                } else {
                    $size = (int)$matches[2];
                }
            }
            if ($matches[3]) {
                $sqlType = $row['Type'];
            }
            if (isset(static::$defaultTypeSizes[$nativeType]) && $scale == null && $size === static::$defaultTypeSizes[$nativeType]) {
                $size = null;
            }
        } elseif (preg_match('/^(\w+)\(/', $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($nativeType === 'enum' || $nativeType === 'set') {
                $sqlType = $row['Type'];
            }
        } else {
            $nativeType = $row['Type'];
        }

        // BLOBs can't have any default values in MySQL
        $default = preg_match('~blob|text~', $nativeType) ? null : $row['Default'];

        $propelType = $this->getMappedPropelType($nativeType);
        if (!$propelType) {
            $propelType = Column::DEFAULT_TYPE;
            $sqlType = $row['Type'];
            $this->warn('Column [' . $table->getName() . '.' . $name . '] has a column type (' . $nativeType . ') that Propel does not support.');
        }

        // Special case for TINYINT(1) which is a BOOLEAN
        if ($propelType === PropelTypes::TINYINT && $size === 1) {
            $propelType = PropelTypes::BOOLEAN;
        }

        $column = new Column($name);
        $column->setTable($table);
        $column->setDomainForType($propelType);
        if ($sqlType) {
            $column->getDomain()->replaceSqlType($sqlType);
        }
        $column->getDomain()->replaceSize($size);
        $column->getDomain()->replaceScale($scale);
        if ($default !== null) {
            if ($propelType == PropelTypes::BOOLEAN) {
                if ($default == '1') {
                    $default = 'true';
                }
                if ($default == '0') {
                    $default = 'false';
                }
            }
            if (in_array($default, ['CURRENT_TIMESTAMP', 'current_timestamp()'], true)) {
                $default = 'CURRENT_TIMESTAMP';
                if (strpos(strtolower($row['Extra']), 'on update current_timestamp') !== false) {
                    $default = 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
                }
                $type = ColumnDefaultValue::TYPE_EXPR;
            } else {
                $type = ColumnDefaultValue::TYPE_VALUE;
            }
            $column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, $type));
        }
        $column->setAutoIncrement($autoincrement);
        $column->setNotNull(!$isNullable);

        if ($this->addVendorInfo) {
            $vi = $this->getNewVendorInfoObject($row);
            $column->addVendorInfo($vi);
        }

        return $column;
    }

    /**
     * Load and set table description.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addDescriptionToTable(Table $table): void
    {
        $tableDescription = $this->loadTableDescription($table);
        if ($tableDescription) {
            $table->setDescription($tableDescription);
        }
    }

    /**
     * Sets column descriptions according to source.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addColumnDescriptionsToTable(Table $table): void
    {
        foreach ($table->getColumns() as $column) {
            $columnDescription = $this->loadColumnDescription($column);
            if ($columnDescription) {
                $column->setDescription($columnDescription);
            }
        }
    }

    /**
     * Load a comment for this table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @throws \RuntimeException
     *
     * @return string|null
     */
    protected function loadTableDescription(Table $table): ?string
    {
        $tableName = $this->getPlatform()->quote($table->getName());
        $query = <<< EOT
SELECT table_comment
FROM INFORMATION_SCHEMA.TABLES
WHERE table_schema=DATABASE()
  AND table_name=($tableName)
EOT;

        $dataFetcher = $this->dbh->query($query);
        if ($dataFetcher === false) {
            throw new RuntimeException('PdoConnection::query() did not return a result set as a statement object.');
        }

        /** @phpstan-var string|null */
        return $dataFetcher->fetchColumn();
    }

    /**
     * Load a comment for this column.
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @throws \RuntimeException
     *
     * @return string|null
     */
    protected function loadColumnDescription(Column $column): ?string
    {
        $tableName = $this->getPlatform()->quote($column->getTableName());
        $columnName = $this->getPlatform()->quote($column->getName());
        $query = <<< EOT
SELECT column_comment
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_schema=DATABASE()
  AND table_name=($tableName)
  AND column_name=($columnName)
EOT;

        $dataFetcher = $this->dbh->query($query);
        if ($dataFetcher === false) {
            throw new RuntimeException('PdoConnection::query() did not return a result set as a statement object.');
        }

        /** @phpstan-var string|null */
        return $dataFetcher->fetchColumn();
    }

    /**
     * Load foreign keys for this table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function addForeignKeys(Table $table): void
    {
        $database = $table->getDatabase();

        $dataFetcher = $this->dbh->query(sprintf('SHOW CREATE TABLE %s', $this->getPlatform()->doQuoting($table->getName())));

        if ($dataFetcher === false) {
            throw new RuntimeException('PdoConnection::query() did not return a result set as a statement object.');
        }

        $row = $dataFetcher->fetch();
        $foreignKeys = []; // local store to avoid duplicates

        // Get the information on all the foreign keys
        $pattern = '/CONSTRAINT `([^`]+)` FOREIGN KEY \((.+)\) REFERENCES `([^\s]+)` \((.+)\)(.*)/';
        if (preg_match_all($pattern, $row[1], $matches)) {
            $tmpArray = array_keys($matches[0]);
            foreach ($tmpArray as $curKey) {
                $name = $matches[1][$curKey];
                $rawlcol = $matches[2][$curKey];
                $ftbl = str_replace('`', '', $matches[3][$curKey]);
                $rawfcol = $matches[4][$curKey];
                $fkey = $matches[5][$curKey];

                $lcols = [];
                $pieces = explode('`, `', $rawlcol);
                foreach ($pieces as $piece) {
                    $lcols[] = trim($piece, '` ');
                }

                $fcols = [];
                $pieces = explode('`, `', $rawfcol);
                foreach ($pieces as $piece) {
                    $fcols[] = trim($piece, '` ');
                }

                $fkactions = [
                    'ON DELETE' => null,
                    'ON UPDATE' => null,
                ];

                $availableActions = [ForeignKey::CASCADE, ForeignKey::SETNULL, ForeignKey::RESTRICT, ForeignKey::NOACTION];
                $pipedActionsString = implode('|', $availableActions);

                if ($fkey) {
                    // split foreign key information -> search for ON DELETE and afterwards for ON UPDATE action
                    foreach (array_keys($fkactions) as $fkaction) {
                        $result = null;
                        $regex = sprintf('/ %s (%s)/', $fkaction, $pipedActionsString);
                        preg_match($regex, $fkey, $result);
                        if ($result && is_array($result) && isset($result[1])) {
                            $fkactions[$fkaction] = $result[1];
                        }
                    }
                }

                $localColumns = [];
                $foreignColumns = [];
                if ($table->guessSchemaName() != $database->getSchema() && strpos($ftbl, $database->getPlatform()->getSchemaDelimiter()) === false) {
                    $ftbl = $table->guessSchemaName() . $database->getPlatform()->getSchemaDelimiter() . $ftbl;
                }

                $foreignTable = $database->getTable($ftbl, true);

                if (!$foreignTable) {
                    continue;
                }

                foreach ($fcols as $fcol) {
                    $foreignColumns[] = $foreignTable->getColumn($fcol);
                }
                foreach ($lcols as $lcol) {
                    $localColumns[] = $table->getColumn($lcol);
                }

                if (!isset($foreignKeys[$name])) {
                    $fk = new ForeignKey($name);
                    $fk->setForeignTableCommonName($foreignTable->getCommonName());
                    if ($table->guessSchemaName() != $foreignTable->guessSchemaName()) {
                        $fk->setForeignSchemaName($foreignTable->guessSchemaName());
                    }
                    $fk->setOnDelete($fkactions['ON DELETE']);
                    $fk->setOnUpdate($fkactions['ON UPDATE']);
                    $table->addForeignKey($fk);
                    $foreignKeys[$name] = $fk;
                }

                $max = count($localColumns);
                for ($i = 0; $i < $max; $i++) {
                    $foreignKeys[$name]->addReference($localColumns[$i], $foreignColumns[$i]);
                }
            }
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
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query(sprintf('SHOW INDEX FROM %s', $this->getPlatform()->doQuoting($table->getName())));

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.

        /** @var array<\Propel\Generator\Model\Index> $indexes */
        $indexes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colName = $row['Column_name'];
            $colSize = $row['Sub_part'];
            $name = (string)$row['Key_name'];

            if ($name === 'PRIMARY') {
                continue;
            }

            if (!isset($indexes[$name])) {
                $isUnique = ($row['Non_unique'] == 0);
                if ($isUnique) {
                    $indexes[$name] = new Unique($name);
                } else {
                    $indexes[$name] = new Index($name);
                }
                if ($this->addVendorInfo) {
                    $vi = $this->getNewVendorInfoObject($row);
                    $indexes[$name]->addVendorInfo($vi);
                }
                $indexes[$name]->setTable($table);
            }

            $indexes[$name]->addColumn([
                'name' => $colName,
                'size' => $colSize,
            ]);
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
     * @return void
     */
    protected function addPrimaryKey(Table $table): void
    {
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query(sprintf('SHOW KEYS FROM %s', $this->getPlatform()->doQuoting($table->getName())));

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Skip any non-primary keys.
            if ($row['Key_name'] !== 'PRIMARY') {
                continue;
            }
            $name = $row['Column_name'];
            $column = $table->getColumn($name);
            if ($column) {
                $column->setPrimaryKey(true);
            }
        }
    }

    /**
     * Adds vendor-specific info for table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function addTableVendorInfo(Table $table): void
    {
        /** @var \PDOStatement $stmt */
        $stmt = $this->dbh->query("SHOW TABLE STATUS LIKE '" . $table->getName() . "'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$this->addVendorInfo) {
            // since we depend on `Engine` in the MysqlPlatform, we always have to extract this vendor information
            $row = ['Engine' => $row ? $row['Engine'] : null];
        }
        $vi = $this->getNewVendorInfoObject($row);
        $table->addVendorInfo($vi);
    }
}
