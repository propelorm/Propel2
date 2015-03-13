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
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\ColumnDefaultValue;

/**
 * Mysql database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class MysqlSchemaParser extends AbstractSchemaParser
{
    /**
     * @var boolean
     */
    private $addVendorInfo = false;

    /**
     * Map MySQL native types to Propel types.
     * @var array
     */
    private static $mysqlTypeMap = array(
        'tinyint'    => PropelTypes::TINYINT,
        'smallint'   => PropelTypes::SMALLINT,
        'mediumint'  => PropelTypes::SMALLINT,
        'int'        => PropelTypes::INTEGER,
        'integer'    => PropelTypes::INTEGER,
        'bigint'     => PropelTypes::BIGINT,
        'int24'      => PropelTypes::BIGINT,
        'real'       => PropelTypes::DOUBLE,
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
        'mediumblob' => PropelTypes::VARBINARY,
        'longblob'   => PropelTypes::LONGVARBINARY,
        'longtext'   => PropelTypes::CLOB,
        'tinytext'   => PropelTypes::VARCHAR,
        'mediumtext' => PropelTypes::LONGVARCHAR,
        'text'       => PropelTypes::LONGVARCHAR,
        'enum'       => PropelTypes::CHAR,
        'set'        => PropelTypes::CHAR,
    );

    protected static $defaultTypeSizes = array(
        'char'     => 1,
        'tinyint'  => 4,
        'smallint' => 6,
        'int'      => 11,
        'bigint'   => 20,
        'decimal'  => 10,
    );

    /**
     * Gets a type mapping from native types to Propel types
     *
     * @return array
     */
    protected function getTypeMapping()
    {
        return self::$mysqlTypeMap;
    }

    /**
     * @param  Database $database
     * @param  Table[]  $additionalTables
     * @return int
     */
    public function parse(Database $database, array $additionalTables = array())
    {
        if (null !== $this->getGeneratorConfig()) {
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
        }

        return count($database->getTables());
    }

    protected function parseTables(Database $database, $filterTable = null)
    {
        $sql = 'SHOW FULL TABLES';

        if ($filterTable) {
            if ($schema = $filterTable->getSchema()) {
                $sql .= ' FROM ' . $database->getPlatform()->doQuoting($schema);
            }
            $sql .= sprintf(" LIKE '%s'", $filterTable->getCommonName());
        } else if ($schema = $database->getSchema()) {
            $sql .= ' FROM ' . $database->getPlatform()->doQuoting($schema);
        }

        $dataFetcher = $this->dbh->query($sql);

        // First load the tables (important that this happen before filling out details of tables)
        $tables = array();
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
            $tables[] = $table;
        }
    }

    /**
     * Adds Columns to the specified table.
     *
     * @param Table $table The Table model class to add columns to.
     */
    protected function addColumns(Table $table)
    {
        $stmt = $this->dbh->query(sprintf('SHOW COLUMNS FROM %s', $this->getPlatform()->doQuoting($table->getName())));

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $column = $this->getColumnFromRow($row, $table);
            $table->addColumn($column);
        }
    }

    /**
     * Factory method creating a Column object
     * based on a row from the 'show columns from ' MySQL query result.
     *
     * @param  array  $row An associative array with the following keys:
     *                     Field, Type, Null, Key, Default, Extra.
     * @return Column
     */
    public function getColumnFromRow($row, Table $table)
    {
        $name = $row['Field'];
        $isNullable = ('YES' === $row['Null']);
        $autoincrement = (false !== strpos($row['Extra'], 'auto_increment'));
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
                if (false !== ($cpos = strpos($matches[2], ','))) {
                    $size = (int) substr($matches[2], 0, $cpos);
                    $scale = (int) substr($matches[2], $cpos + 1);
                } else {
                    $size = (int) $matches[2];
                }
            }
            if ($matches[3]) {
                $sqlType = $row['Type'];
            }
            if (isset(static::$defaultTypeSizes[$nativeType]) && null == $scale && $size === static::$defaultTypeSizes[$nativeType]) {
                $size = null;
            }
        } elseif (preg_match('/^(\w+)\(/', $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($nativeType === 'enum') {
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
            $this->warn("Column [" . $table->getName() . "." . $name. "] has a column type (".$nativeType.") that Propel does not support.");
        }

        // Special case for TINYINT(1) which is a BOOLEAN
        if (PropelTypes::TINYINT === $propelType && 1 === $size) {
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
            if (in_array($default, array('CURRENT_TIMESTAMP'))) {
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
     * Load foreign keys for this table.
     */
    protected function addForeignKeys(Table $table)
    {
        $database = $table->getDatabase();

        $dataFetcher = $this->dbh->query(sprintf('SHOW CREATE TABLE %s', $this->getPlatform()->doQuoting($table->getName())));
        $row = $dataFetcher->fetch();

        $foreignKeys = array(); // local store to avoid duplicates

        // Get the information on all the foreign keys
        $pattern = '/CONSTRAINT `([^`]+)` FOREIGN KEY \((.+)\) REFERENCES `([^\s]+)` \((.+)\)(.*)/';
        if (preg_match_all($pattern, $row[1], $matches)) {
            $tmpArray = array_keys($matches[0]);
            foreach ($tmpArray as $curKey) {
                $name    = $matches[1][$curKey];
                $rawlcol = $matches[2][$curKey];
                $ftbl    = str_replace('`', '', $matches[3][$curKey]);
                $rawfcol = $matches[4][$curKey];
                $fkey    = $matches[5][$curKey];

                $lcols = array();
                foreach (preg_split('/`, `/', $rawlcol) as $piece) {
                    $lcols[] = trim($piece, '` ');
                }

                $fcols = array();
                foreach (preg_split('/`, `/', $rawfcol) as $piece) {
                    $fcols[] = trim($piece, '` ');
                }

                // typical for mysql is RESTRICT
                $fkactions = array(
                    'ON DELETE' => ForeignKey::RESTRICT,
                    'ON UPDATE' => ForeignKey::RESTRICT,
                );

                if ($fkey) {
                    // split foreign key information -> search for ON DELETE and afterwords for ON UPDATE action
                    foreach (array_keys($fkactions) as $fkaction) {
                        $result = null;
                        preg_match('/' . $fkaction . ' (' . ForeignKey::CASCADE . '|' . ForeignKey::SETNULL . ')/', $fkey, $result);
                        if ($result && is_array($result) && isset($result[1])) {
                            $fkactions[$fkaction] = $result[1];
                        }
                    }
                }

                // restrict is the default
                foreach ($fkactions as $key => $action) {
                    if (ForeignKey::RESTRICT === $action) {
                        $fkactions[$key] = null;
                    }
                }

                $localColumns = array();
                $foreignColumns = array();
                if ($table->guessSchemaName() != $database->getSchema() && false == strpos($ftbl, $database->getPlatform()->getSchemaDelimiter())) {
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
     */
    protected function addIndexes(Table $table)
    {
        $stmt = $this->dbh->query(sprintf('SHOW INDEX FROM %s', $this->getPlatform()->doQuoting($table->getName())));

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.

        /** @var $indexes Index[] */
        $indexes = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $colName = $row['Column_name'];
            $colSize = $row['Sub_part'];
            $name = $row['Key_name'];

            if ('PRIMARY' === $name) {
                continue;
            }

            if (!isset($indexes[$name])) {
                $isUnique = (0 == $row['Non_unique']);
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
                'size' => $colSize
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
     */
    protected function addPrimaryKey(Table $table)
    {
        $stmt = $this->dbh->query(sprintf('SHOW KEYS FROM %s', $this->getPlatform()->doQuoting($table->getName())));

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // Skip any non-primary keys.
            if ('PRIMARY' !== $row['Key_name']) {
                continue;
            }
            $name = $row['Column_name'];
            $table->getColumn($name)->setPrimaryKey(true);
        }
    }

    /**
     * Adds vendor-specific info for table.
     *
     * @param Table $table
     */
    protected function addTableVendorInfo(Table $table)
    {
        $stmt = $this->dbh->query("SHOW TABLE STATUS LIKE '" . $table->getName() . "'");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$this->addVendorInfo) {
            // since we depend on `Engine` in the MysqlPlatform, we always have to extract this vendor information
            $row = array('Engine' => $row['Engine']);
        }
        $vi = $this->getNewVendorInfoObject($row);
        $table->addVendorInfo($vi);
    }
}
