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
 * CUBRID database schema parser.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class CubridSchemaParser extends AbstractSchemaParser
{
    /**
     * Map CUBRID native types to Propel types.
     * @var array
     */
    private static $cubridTypeMap = array(
        'SHORT'    => PropelTypes::SMALLINT,
        'SMALLINT'   => PropelTypes::SMALLINT,
        'INT'        => PropelTypes::INTEGER,
        'INTEGER'    => PropelTypes::INTEGER,
        'BIGINT'     => PropelTypes::BIGINT,
        'REAL'       => PropelTypes::REAL,
        'FLOAT'      => PropelTypes::FLOAT,
        'DECIMAL'    => PropelTypes::DECIMAL,
        'NUMERIC'    => PropelTypes::NUMERIC,
        'DOUBLE'     => PropelTypes::DOUBLE,
        'CHAR'       => PropelTypes::CHAR,
        'VARCHAR'    => PropelTypes::VARCHAR,
        'STRING'   	 => PropelTypes::VARCHAR,
        'DATE'       => PropelTypes::DATE,
        'TIME'       => PropelTypes::TIME,
        'DATETIME'   => PropelTypes::TIMESTAMP,
        'TIMESTAMP'  => PropelTypes::TIMESTAMP,
        'ENUM'       => PropelTypes::CHAR,
        'BLOB'       => PropelTypes::BLOB,
    );

    protected static $defaultTypeSizes = array(
        'char'     => 1,
        'varchar' => 655535
    );

    /**
     * Gets a type mapping from native types to Propel types
     *
     * @return array
     */
    protected function getTypeMapping()
    {
        return self::$cubridTypeMap;
    }

    /**
     *
     */
    public function parse(Database $database)
    {
        $stmt = $this->dbh->query('SHOW FULL TABLES');

        // First load the tables (important that this happen before filling out details of tables)
        $tables = array();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $name = $row[0];
            $type = $row[1];

            if ($name == $this->getMigrationTable() || $type !== 'BASE TABLE') {
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

        // Now add indices and constraints.
        foreach ($tables as $table) {
            $this->addForeignKeys($table);
//            $this->addConstraints($table);
            $this->addPrimaryKey($table);
            $this->addIndexes($table);
        }

        // Now setup referrers.
//        foreach ($tables as $table) {
//            $table->setupReferrers();
//        }
        return count($tables);
    }

    /**
     * Adds Columns to the specified table.
     *
     * @param Table $table The Table model class to add columns to.
     */
    protected function addColumns(Table $table)
    {
        $stmt = $this->dbh->query(sprintf('SHOW COLUMNS FROM `%s`', $table->getName()));

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $column = $this->getColumnFromRow($row, $table);
            $table->addColumn($column);
        }
    }

    /**
     * Factory method creating a Column object
     * based on a row from the 'show columns from ' CUBRID query result.
     *
     * @param array $row An associative array with the following keys:
     *                       Field, Type, Null, Key, Default, Extra.
     * @return Column
     */
    public function getColumnFromRow($row, Table $table)
    {
        $name = $row['Field'];
        $nativeType = $row['Type'];
        $isNullable = ('YES' === $row['Null']);
        $default = $row['Default'];
        $autoincrement = (false !== strpos($row['Extra'], 'auto_increment'));
        $size = null;
        $precision = null;
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
                    $precision = $size;
                    $scale = (int) substr($matches[2], $cpos + 1);
                } else {
                    $size = (int) $matches[2];
                }
            }
            if ($matches[3]) {
                $sqlType = $row['Type'];
            }
            foreach (self::$defaultTypeSizes as $type => $defaultSize) {
                if ($nativeType === $type && $size === $defaultSize) {
                    $size = null;
                    continue;
                }
            }
        } elseif (preg_match('/^(\w+)\(/', $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($nativeType === 'enum') {
                $sqlType = $row['Type'];
            }
        } else {
            $nativeType = $row['Type'];
        }

        $propelType = $this->getMappedPropelType($nativeType);
        if (!$propelType) {
            $propelType = Column::DEFAULT_TYPE;
            $sqlType = $row['Type'];
            $this->warn("Column [" . $table->getName() . "." . $name. "] has a column type (".$nativeType.") that Propel does not support.");
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

        return $column;
    }

    protected function getReferentialAction($value)
    {
        switch ($value) {
            case 0: return ForeignKey::CASCADE;
            case 1: return ForeignKey::RESTRICT;
            case 2: return ForeignKey::NOACTION;
            default: return ForeignKey::SETNULL;  // case 3:
        }
    }
    /**
     * Load foreign keys for this table.
     */
    protected function addForeignKeys(Table $table)
    {
        $database = $table->getDatabase();

        // $fks is now an array()
        $fks = $this->dbh->cubrid_schema(\PDO::CUBRID_SCH_IMPORTED_KEYS, $table->getName());

        foreach ($fks as $fk) {
            $foreignTable = $database->getTable($fk['PKTABLE_NAME'], true);

            $foreignKey = new ForeignKey($fk['FK_NAME']);

            $foreignKey->setForeignTableCommonName($foreignTable->getCommonName());
            $foreignKey->setForeignSchemaName($foreignTable->getSchema());

            $foreignKey->setOnDelete($this->getReferentialAction($fk['DELETE_RULE']));
            $foreignKey->setOnUpdate($this->getReferentialAction($fk['UPDATE_RULE']));

            $foreignKey->addReference($fk['FKCOLUMN_NAME'], $fk['PKCOLUMN_NAME']);

            $table->addForeignKey($foreignKey);
        }
    }

    protected function getNewIndexByType($type)
    {
        switch ($type) {
            case 0: // unique
            case 2: // reverse unique

                    return new Unique();
            case 1: // index
            default:// case 3 is reverse index

                    return new Index();
        }
    }

    /**
     * Load constraints for this table. This function wraps the functionality
     * of two functions: addIndexes() and addPrimaryKey(). Should work but doesn't
     * because of a bug in CUBRID CCI driver.
     * Once fixed, this function will work with no changes.
     */
    protected function addConstraints(Table $table)
    {
        // $pks is now an array()
        $indexes = $this->dbh->cubrid_schema(\PDO::CUBRID_SCH_CONSTRAINT, $table->getName());

        foreach ($indexes as $ix) {
            if ($ix['PRIMARY_KEY']) {
                $table->getColumn($ix['ATTR_NAME'])->setPrimaryKey(true);
            } else {
                $tIndex = $this->getNewIndexByType($ix['TYPE']);
                $tIndex->setName($ix['NAME']);
                $tIndex->addColumn($ix['ATTR_NAME']);

                $table->addIndex($tIndex);
            }
        }
    }

    /**
     * Load indexes for this table
     */
    protected function addIndexes(Table $table)
    {
        $stmt = $this->dbh->query(sprintf('SHOW INDEX FROM `%s`', $table->getName()));

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.
        $indexes = array();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $col = $table->getColumn($row['Column_name']);

            if (!$col->isPrimaryKey()) {
                $name = $row['Key_name'];

                if (!isset($indexes[$name])) {
                    $isUnique = (0 == $row['Non_unique']);
                    if ($isUnique) {
                        $indexes[$name] = new Unique($name);
                    } else {
                        $indexes[$name] = new Index($name);
                    }

                    $table->addIndex($indexes[$name]);
                }

                $indexes[$name]->addColumn($col);
            }
        }
    }

    /**
     * Loads the primary key for this table.
     */
    protected function addPrimaryKey(Table $table)
    {
        // $pks is now an array()
        $pks = $this->dbh->cubrid_schema(\PDO::CUBRID_SCH_PRIMARY_KEY, $table->getName());

        foreach ($pks as $pk) {
            $table->getColumn($pk['ATTR_NAME'])->setPrimaryKey(true);
        }
    }
}
