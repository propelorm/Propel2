<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Util;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Schema;
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
    /**
     * @var \Propel\Generator\Model\Schema
     */
    protected $schema;

    /**
     * @var array<string>
     */
    protected $errors = [];

    /**
     * @param \Propel\Generator\Model\Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return bool true if valid, false otherwise
     */
    public function validate(): bool
    {
        foreach ($this->schema->getDatabases() as $database) {
            $this->validateDatabaseTables($database);
        }

        return count($this->errors) === 0;
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return void
     */
    protected function validateDatabaseTables(Database $database): void
    {
        $phpNames = [];
        $namespaces = [];
        foreach ($database->getTables() as $table) {
            /** @var array<string> $list */
            $list = &$phpNames;
            if ($table->getNamespace()) {
                if (!isset($namespaces[$table->getNamespace()])) {
                    $namespaces[$table->getNamespace()] = [];
                }

                $list = &$namespaces[$table->getNamespace()];
            }
            if (in_array($table->getPhpName(), $list, true)) {
                $this->errors[] = sprintf('Table "%s" declares a phpName already used in another table', $table->getName());
            }
            $list[] = $table->getPhpName();
            $this->validateTableAttributes($table);
            $this->validateTableColumns($table);
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function validateTableAttributes(Table $table): void
    {
        $reservedTableNames = ['table_name'];
        $tableName = strtolower($table->getName());
        if (in_array($tableName, $reservedTableNames, true)) {
            $this->errors[] = sprintf('Table "%s" uses a reserved keyword as name', $table->getName());
        }
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    protected function validateTableColumns(Table $table): void
    {
        if (!$table->hasPrimaryKey() && !$table->isSkipSql()) {
            $this->errors[] = sprintf('Table "%s" does not have a primary key defined. Propel requires all tables to have a primary key.', $table->getName());
        }
        $phpNames = [];
        foreach ($table->getColumns() as $column) {
            if (in_array($column->getPhpName(), $phpNames, true)) {
                $this->errors[] = sprintf('Column "%s" declares a phpName already used in table "%s"', $column->getName(), $table->getName());
            }
            $phpNames[] = $column->getPhpName();
        }
    }

    /**
     * Returns the list of error messages
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
