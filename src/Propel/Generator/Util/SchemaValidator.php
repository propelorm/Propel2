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
use Propel\Generator\Model\Entity;

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
        foreach ($database->getEntities() as $entity) {
            $list = &$phpNames;
            if ($entity->getNamespace()) {
                if (!isset($namespaces[$entity->getNamespace()])) {
                    $namespaces[$entity->getNamespace()] = array();
                }

                $list = &$namespaces[$entity->getNamespace()];
            }
            if (in_array($entity->getName(), $list)) {
                $this->errors[] = sprintf('Entity "%s" declares a name already used in another entity', $entity->getName());
            }
            $list[] = $entity->getName();
            $this->validateTableAttributes($entity);
            $this->validateTableColumns($entity);
        }
    }

    protected function validateTableAttributes(Entity $entity)
    {
        $reservedTableNames = array('table_name');
        $entityName = strtolower($entity->getTableName());
        if (in_array($entityName, $reservedTableNames)) {
            $this->errors[] = sprintf('Entity "%s" uses a reserved keyword as tableName', $entity->getName());
        }
    }

    protected function validateTableColumns(Entity $entity)
    {
        if (!$entity->hasPrimaryKey() && !$entity->isSkipSql()) {
            $this->errors[] = sprintf('Entity "%s" does not have a primary key defined. Propel requires all entities to have a primary key.', $entity->getName());
        }
        $phpNames = array();
        foreach ($entity->getFields() as $field) {
            if (in_array($field->getName(), $phpNames)) {
                $this->errors[] = sprintf('Field "%s" declares a name already used in entity "%s"', $field->getName(), $entity->getName());
            }
            $phpNames[]= $field->getName();
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
