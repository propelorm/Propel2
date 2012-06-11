<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Util;

use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;

/**
 * Service class for validating XML schemas.
 * Only implements validation rules that cannot be implemented in XSD.
 *
 * @example Basic usage:
 * <code>
 * $validator = new SchemaValidator($schema);
 * if (!$validator->validate()) {
 *   throw new Exception("Invalid schema:\n" . join("\n", $validator->getErrors()));
 * }
 * </code>
 *
 * @author François Zaninotto
 */
class SchemaValidator
{
    protected $schema;
    protected $errors = array();

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return boolean true if valid, false otherwise
     */
    public function validate()
    {
        foreach ($this->schema->getDatabases() as $database) {
            $this->validateDatabaseTables($database);
        }

        return 0 === count($this->errors);
    }

    protected function validateDatabaseTables(Database $database)
    {
        $phpNames = array();
        $namespaces = array();
        foreach ($database->getTables() as $table) {
            $list = &$phpNames;
            if ($table->getNamespace()) {
                if (!isset($namespaces[$table->getNamespace()])) {
                    $namespaces[$table->getNamespace()] = array();
                }

                $list = &$namespaces[$table->getNamespace()];
            }
            if (in_array($table->getPhpName(), $list)) {
                $this->errors[] = sprintf('Table "%s" declares a phpName already used in another table', $table->getName());
            }
            $list[] = $table->getPhpName();
            $this->validateTableAttributes($table);
            $this->validateTableColumns($table);
        }
    }

    protected function validateTableAttributes(Table $table)
    {
        $reservedTableNames = array('table_name');
        $tableName = strtolower($table->getName());
        if (in_array($tableName, $reservedTableNames)) {
            $this->errors[] = sprintf('Table "%s" uses a reserved keyword as name', $table->getName());
        }
    }

    protected function validateTableColumns(Table $table)
    {
        if (!$table->hasPrimaryKey() && !$table->isSkipSql()) {
            $this->errors[] = sprintf('Table "%s" does not have a primary key defined. Propel requires all tables to have a primary key.', $table->getName());
        }
        $phpNames = array();
        foreach ($table->getColumns() as $column) {
            if (in_array($column->getPhpName(), $phpNames)) {
                $this->errors[] = sprintf('Column "%s" declares a phpName already used in table "%s"', $column->getName(), $table->getName());
            }
            $phpNames[]= $column->getPhpName();
        }
    }

    /**
     * Returns the list of error messages
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
