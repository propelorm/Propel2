<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Runtime\Configuration;

/**
 * MySql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class MysqlPlatform extends SqlDefaultPlatform
{
    protected $tableEngineKeyword = 'ENGINE';  // overwritten in propel config
    protected $defaultEntityEngine = 'InnoDB';  // overwritten in propel config

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'TINYINT', 1));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'DECIMAL'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'LONGTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'TINYINT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'DOUBLE'));
    }


    protected function setupReferrers(Entity $entity, $throwErrors = false)
    {
        parent::setupReferrers($entity, $throwErrors);
        $this->addExtraIndices($entity);
    }

    /**
     * Adds extra indices for reverse foreign keys
     * This is required for MySQL databases,
     * and is called from Database::doFinalInitialization()
     */
    protected function addExtraIndices(Entity $entity)
    {
        /**
         * A collection of indexed columns. The keys is the column name
         * (concatenated with a comma in the case of multi-col index), the value is
         * an array with the names of the indexes that index these columns. We use
         * it to determine which additional indexes must be created for foreign
         * keys. It could also be used to detect duplicate indexes, but this is not
         * implemented yet.
         * @var array
         */
        $indices = [];

        $this->collectIndexedFields('PRIMARY', $entity->getPrimaryKey(), $indices);

        /** @var Index[] $entityIndices */
        $entityIndices = array_merge($entity->getIndices(), $entity->getUnices());
        foreach ($entityIndices as $index) {
            $this->collectIndexedFields($index->getSqlName(), $index->getFields(), $indices);
        }

        // we're determining which entities have foreign keys that point to this entity,
        // since MySQL needs an index on any column that is referenced by another entity
        // (yep, MySQL _is_ a PITA)
        $counter = 0;
        foreach ($entity->getReferrers() as $relation) {
            $referencedFields = $relation->getForeignFieldObjects();
            $referencedFieldsHash = $this->getFieldList($referencedFields);
            if (empty($referencedFields) || isset($indices[$referencedFieldsHash])) {
                continue;
            }

            // no matching index defined in the schema, so we have to create one
            $name = sprintf('i_referenced_%s_%s', $relation->getSqlName(), ++$counter);
            if ($entity->hasIndex($name)) {
                // if we have already a index with this name, then it looks like the columns of this index have just
                // been changed, so remove it and inject it again. This is the case if a referenced entity is handled
                // later than the referencing entity.
                $entity->removeIndex($name);
            }

            $index = $entity->createIndex($name, $referencedFields);
            // Add this new index to our collection, otherwise we might add it again (bug #725)
            $this->collectIndexedFields($index->getSqlName(), $referencedFields, $indices);
        }

        // we're adding indices for this entity foreign keys
        foreach ($entity->getRelations() as $relation) {
            $localFields = $relation->getLocalFieldObjects();
            $localFieldsHash = $this->getFieldList($localFields);
            if (empty($localFields) || isset($indices[$localFieldsHash])) {
                continue;
            }

            // No matching index defined in the schema, so we have to create one.
            // MySQL needs indices on any columns that serve as foreign keys.
            // These are not auto-created prior to 4.1.2.

            $name = substr_replace($relation->getSqlName(), 'rl_',  strrpos($relation->getSqlName(), 'rl_'), 3);
            if ($entity->hasIndex($name)) {
                // if we already have an index with this name, then it looks like the columns of this index have just
                // been changed, so remove it and inject it again. This is the case if a referenced entity is handled
                // later than the referencing entity.
                $entity->removeIndex($name);
            }

            $index = $entity->createIndex($name, $localFields);
            $this->collectIndexedFields($index->getSqlName(), $localFields, $indices);
        }
    }

    /**
     * Helper function to collect indexed columns.
     *
     * @param string $indexName        The name of the index
     * @param array  $columns          The column names or objects
     * @param array  $collectedIndexes The collected indexes
     */
    protected function collectIndexedFields($indexName, $columns, &$collectedIndexes)
    {
        /**
         * "If the entity has a multiple-column index, any leftmost prefix of the
         * index can be used by the optimizer to find rows. For example, if you
         * have a three-column index on (col1, col2, col3), you have indexed search
         * capabilities on (col1), (col1, col2), and (col1, col2, col3)."
         * @link http://dev.mysql.com/doc/refman/5.5/en/mysql-indexes.html
         */
        $indexedFields = [];
        foreach ($columns as $column) {
            $indexedFields[] = $column;
            $indexedFieldsHash = $this->getFieldList($indexedFields);
            if (!isset($collectedIndexes[$indexedFieldsHash])) {
                $collectedIndexes[$indexedFieldsHash] = [];
            }
            $collectedIndexes[$indexedFieldsHash][] = $indexName;
        }
    }


    /**
     * Returns a delimiter-delimited string list of column names.
     *
     * @see Platform::getFieldList() if quoting is required
     * @param array
     * @param  string $delimiter
     * @return string
     */
    public function getFieldList($columns, $delimiter = ',')
    {
        $list = [];
        foreach ($columns as $col) {
            if ($col instanceof Field) {
                $col = $col->getSqlName();
            }
            $list[] = $col;
        }

        return implode($delimiter, $list);
    }

    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        parent::setGeneratorConfig($generatorConfig);
        if ($defaultEntityEngine = $generatorConfig->get()['database']['adapters']['mysql']['tableType']) {
            $this->defaultEntityEngine = $defaultEntityEngine;
        }
        if ($tableEngineKeyword = $generatorConfig->get()['database']['adapters']['mysql']['tableEngineKeyword']) {
            $this->tableEngineKeyword = $tableEngineKeyword;
        }
    }

    /**
     * Setter for the tableEngineKeyword property
     *
     * @param string $tableEngineKeyword
     */
    public function setEntityEngineKeyword($tableEngineKeyword)
    {
        $this->tableEngineKeyword = $tableEngineKeyword;
    }

    /**
     * Getter for the tableEngineKeyword property
     *
     * @return string
     */
    public function getEntityEngineKeyword()
    {
        return $this->tableEngineKeyword;
    }

    /**
     * Setter for the defaultEntityEngine property
     *
     * @param string $defaultEntityEngine
     */
    public function setDefaultEntityEngine($defaultEntityEngine)
    {
        $this->defaultEntityEngine = $defaultEntityEngine;
    }

    /**
     * Getter for the defaultEntityEngine property
     *
     * @return string
     */
    public function getDefaultEntityEngine()
    {
        return $this->defaultEntityEngine;
    }

    public function getAutoIncrement()
    {
        return 'AUTO_INCREMENT';
    }

    public function getMaxFieldNameLength()
    {
        return 64;
    }

    public function supportsNativeDeleteTrigger()
    {
        return strtolower($this->getDefaultEntityEngine()) == 'innodb';
    }

    public function supportsIndexSize()
    {
        return true;
    }

    public function supportsRelations(Entity $entity)
    {
        $vendorSpecific = $entity->getVendorInfoForType('mysql');
        if ($vendorSpecific->hasParameter('Type')) {
            $mysqlEntityType = $vendorSpecific->getParameter('Type');
        } elseif ($vendorSpecific->hasParameter('Engine')) {
            $mysqlEntityType = $vendorSpecific->getParameter('Engine');
        } else {
            $mysqlEntityType = $this->getDefaultEntityEngine();
        }

        return strtolower($mysqlEntityType) == 'innodb';
    }

    public function getAddEntitiesDDL(Database $database)
    {
        $ret = '';
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getCommentBlockDDL($entity->getSqlName());
            $ret .= $this->getDropEntityDDL($entity);
            $ret .= $this->getAddEntityDDL($entity);
        }
        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    public function getBeginDDL()
    {
        return "
# This is a fix for InnoDB in MySQL >= 4.1.x
# It \"suspends judgement\" for fkey relationships until tables are set.
SET FOREIGN_KEY_CHECKS = 0;
";
    }

    public function getEndDDL()
    {
        return "
# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
";
    }

    /**
     * Returns the SQL for the primary key of a Entity object
     *
     * @param Entity $entity
     *
     * @return string
     */
    public function getPrimaryKeyDDL(Entity $entity)
    {
        if ($entity->hasPrimaryKey()) {

            $keys = $entity->getPrimaryKey();

            //MySQL throws an 'Incorrect entity definition; there can be only one auto field and it must be defined as a key'
            //if the primary key consists of multiple fields and if the first is not the autoIncrement one. So
            //this pushes the autoIncrement field to the first position if its not already.
            $autoIncrement = $entity->getAutoIncrementPrimaryKey();
            if ($autoIncrement && $keys[0] != $autoIncrement) {
                $idx = array_search($autoIncrement, $keys);
                if ($idx !== false) {
                    unset($keys[$idx]);
                    array_unshift($keys, $autoIncrement);
                }
            }

            return 'PRIMARY KEY (' . $this->getFieldListDDL($keys) . ')';
        }
    }

    public function getAddEntityDDL(Entity $entity)
    {
        $lines = array();

        foreach ($entity->getFields() as $field) {
            $lines[] = $this->getFieldDDL($field);
        }

        if ($entity->hasPrimaryKey()) {
            $lines[] = $this->getPrimaryKeyDDL($entity);
        }

        foreach ($entity->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        foreach ($entity->getIndices() as $index) {
            $lines[] = $this->getIndexDDL($index);
        }

        if ($this->supportsRelations($entity)) {
            foreach ($entity->getRelations() as $relation) {
                if ($relation->isSkipSql()) {
                    continue;
                }
                $lines[] = str_replace("
    ", "
        ", $this->getRelationDDL($relation));
            }
        }

        $vendorSpecific = $entity->getVendorInfoForType('mysql');
        if ($vendorSpecific->hasParameter('Type')) {
            $mysqlEntityType = $vendorSpecific->getParameter('Type');
        } elseif ($vendorSpecific->hasParameter('Engine')) {
            $mysqlEntityType = $vendorSpecific->getParameter('Engine');
        } else {
            $mysqlEntityType = $this->getDefaultEntityEngine();
        }

        $entityOptions = $this->getEntityOptions($entity);

        if ($entity->getDescription()) {
            $entityOptions[] = 'COMMENT=' . $this->quote($entity->getDescription());
        }

        $entityOptions = $entityOptions ? ' ' . implode(' ', $entityOptions) : '';
        $sep = ",
    ";

        $pattern = "
CREATE TABLE %s
(
    %s
) %s=%s%s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($entity->getSqlName()),
            implode($sep, $lines),
            $this->getEntityEngineKeyword(),
            $mysqlEntityType,
            $entityOptions
        );
    }

    protected function getEntityOptions(Entity $entity)
    {
        $dbVI = $entity->getDatabase()->getVendorInfoForType('mysql');
        $entityVI = $entity->getVendorInfoForType('mysql');
        $vi = $dbVI->getMergedVendorInfo($entityVI);
        $entityOptions = array();
        // List of supported entity options
        // see http://dev.mysql.com/doc/refman/5.5/en/create-entity.html
        $supportedOptions = array(
            'AutoIncrement'   => 'AUTO_INCREMENT',
            'AvgRowLength'    => 'AVG_ROW_LENGTH',
            'Charset'         => 'CHARACTER SET',
            'Checksum'        => 'CHECKSUM',
            'Collate'         => 'COLLATE',
            'Connection'      => 'CONNECTION',
            'DataDirectory'   => 'DATA DIRECTORY',
            'Delay_key_write' => 'DELAY_KEY_WRITE',
            'DelayKeyWrite'   => 'DELAY_KEY_WRITE',
            'IndexDirectory'  => 'INDEX DIRECTORY',
            'InsertMethod'    => 'INSERT_METHOD',
            'KeyBlockSize'    => 'KEY_BLOCK_SIZE',
            'MaxRows'         => 'MAX_ROWS',
            'MinRows'         => 'MIN_ROWS',
            'Pack_Keys'       => 'PACK_KEYS',
            'PackKeys'        => 'PACK_KEYS',
            'RowFormat'       => 'ROW_FORMAT',
            'Union'           => 'UNION',
        );

        $noQuotedValue = array_flip([
            'InsertMethod',
            'Pack_Keys',
            'PackKeys',
            'RowFormat',
        ]);

        foreach ($supportedOptions as $name => $sqlName) {
            $parameterValue = null;

            if ($vi->hasParameter($name)) {
                $parameterValue = $vi->getParameter($name);
            } elseif ($vi->hasParameter($sqlName)) {
                $parameterValue = $vi->getParameter($sqlName);
            }

            // if we have a param value, then parse it out
            if (!is_null($parameterValue)) {
                // if the value is numeric or is parameter is in $noQuotedValue, then there is no need for quotes
                if (!is_numeric($parameterValue) && !isset($noQuotedValue[$name])) {
                    $parameterValue = $this->quote($parameterValue);
                }

                $entityOptions [] = sprintf('%s=%s', $sqlName, $parameterValue);
            }
        }

        return $entityOptions;
    }

    public function getDropEntityDDL(Entity $entity)
    {
        return "
DROP TABLE IF EXISTS " . $this->quoteIdentifier($entity->getSqlName()) . ";
";
    }

    public function getFieldDDL(Field $col)
    {
        $domain = $col->getDomain();
        $sqlType = $domain->getSqlType();
        $notNullString = $this->getNullString($col->isNotNull());
        $defaultSetting = $this->getFieldDefaultValueDDL($col);

        // Special handling of TIMESTAMP/DATETIME types ...
        // See: http://propel.phpdb.org/trac/ticket/538
        if ($sqlType === 'DATETIME') {
            $def = $domain->getDefaultValue();
            if ($def && $def->isExpression()) {
                // DATETIME values can only have constant expressions
                $sqlType = 'TIMESTAMP';
            }
        } elseif ($sqlType === 'DATE') {
            $def = $domain->getDefaultValue();
            if ($def && $def->isExpression()) {
                throw new EngineException('DATE fields cannot have default *expressions* in MySQL.');
            }
        } elseif ($sqlType === 'TEXT' || $sqlType === 'BLOB') {
            if ($domain->getDefaultValue()) {
                throw new EngineException('BLOB and TEXT fields cannot have DEFAULT values. in MySQL.');
            }
        }

        $ddl = array($this->quoteIdentifier($col->getSqlName()));
        if ($this->hasSize($sqlType) && $col->isDefaultSqlType($this)) {
            $ddl[] = $sqlType . $col->getSizeDefinition();
        } else {
            $ddl[] = $sqlType;
        }
        $colinfo = $col->getVendorInfoForType($this->getDatabaseType());
        if ($colinfo->hasParameter('Charset')) {
            $ddl[] = 'CHARACTER SET '. $this->quote($colinfo->getParameter('Charset'));
        }
        if ($colinfo->hasParameter('Collation')) {
            $ddl[] = 'COLLATE '. $this->quote($colinfo->getParameter('Collation'));
        } elseif ($colinfo->hasParameter('Collate')) {
            $ddl[] = 'COLLATE '. $this->quote($colinfo->getParameter('Collate'));
        }
        if ($sqlType === 'TIMESTAMP') {
            if ($notNullString == '') {
                $notNullString = 'NULL';
            }
            if ($defaultSetting == '' && $notNullString === 'NOT NULL') {
                $defaultSetting = 'DEFAULT CURRENT_TIMESTAMP';
            }
            if ($notNullString) {
                $ddl[] = $notNullString;
            }
            if ($defaultSetting) {
                $ddl[] = $defaultSetting;
            }
        } else {
            if ($defaultSetting) {
                $ddl[] = $defaultSetting;
            }
            if ($notNullString) {
                $ddl[] = $notNullString;
            }
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }
        if ($col->getDescription()) {
            $ddl[] = 'COMMENT ' . $this->quote($col->getDescription());
        }

        return implode(' ', $ddl);
    }

    /**
     * Creates a comma-separated list of field names for the index.
     * For MySQL unique indexes there is the option of specifying size, so we cannot simply use
     * the getFieldsList() method.
     * @param  Index  $index
     * @return string
     */
    protected function getIndexFieldListDDL(Index $index)
    {
        $list = array();
        foreach ($index->getFieldObjects() as $col) {
            $list[] = $this->quoteIdentifier($col->getSqlName()) . ($index->hasFieldSize($col->getName()) ? '(' . $index->getFieldSize($col->getName()) . ')' : '');
        }

        return implode(', ', $list);
    }

    /**
     * Builds the DDL SQL to drop the primary key of a entity.
     *
     * @param  Entity  $entity
     * @return string
     */
    public function getDropPrimaryKeyDDL(Entity $entity)
    {
        if (!$entity->hasPrimaryKey()) {
            return '';
        }

        $pattern = "
ALTER TABLE %s DROP PRIMARY KEY;
";

        return sprintf($pattern,
            $this->quoteIdentifier($entity->getSqlName())
        );
    }

    /**
     * Builds the DDL SQL to add an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getAddIndexDDL(Index $index)
    {
        $pattern = "
CREATE %sINDEX %s ON %s (%s);
";

        return sprintf($pattern,
            $this->getIndexType($index),
            $this->quoteIdentifier($index->getSqlName()),
            $this->quoteIdentifier($index->getEntity()->getSqlName()),
            $this->getIndexFieldListDDL($index)
        );
    }

    /**
     * Builds the DDL SQL to drop an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getDropIndexDDL(Index $index)
    {
        $pattern = "
DROP INDEX %s ON %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($index->getSqlName()),
            $this->quoteIdentifier($index->getEntity()->getSqlName())
        );
    }

    /**
     * Builds the DDL SQL for an Index object.
     * @return string
     */
    public function getIndexDDL(Index $index)
    {
        return sprintf('%sINDEX %s (%s)',
            $this->getIndexType($index),
            $this->quoteIdentifier($index->getSqlName()),
            $this->getIndexFieldListDDL($index)
        );
    }

    protected function getIndexType(Index $index)
    {
        $type = '';
        $vendorInfo = $index->getVendorInfoForType($this->getDatabaseType());
        if ($vendorInfo && $vendorInfo->getParameter('Index_type')) {
            $type = $vendorInfo->getParameter('Index_type') . ' ';
        } elseif ($index->isUnique()) {
            $type = 'UNIQUE ';
        }

        return $type;
    }

    public function getUniqueDDL(Unique $unique)
    {
        return sprintf('UNIQUE INDEX %s (%s)',
            $this->quoteIdentifier($unique->getSqlName()),
            $this->getIndexFieldListDDL($unique)
        );
    }

    public function getAddRelationDDL(Relation $relation)
    {
        if ($this->supportsRelations($relation->getEntity())) {
            return parent::getAddRelationDDL($relation);
        }

        return '';
    }

    /**
     * Builds the DDL SQL for a Relation object.
     *
     * @param Relation $relation
     *
     * @return string
     */
    public function getRelationDDL(Relation $relation)
    {
        if ($this->supportsRelations($relation->getEntity())) {
            return parent::getRelationDDL($relation);
        }

        return '';
    }

    public function getDropRelationDDL(Relation $relation)
    {
        if (!$this->supportsRelations($relation->getEntity())) return;

        return parent::getDropRelationDDL($relation);
    }

    public function getCommentBlockDDL($comment)
    {
        $pattern = "
-- ---------------------------------------------------------------------
-- %s
-- ---------------------------------------------------------------------
";

        return sprintf($pattern, $comment);
    }

    /**
     * Builds the DDL SQL to modify a database
     * based on a DatabaseDiff instance
     *
     * @return string
     */
    public function getModifyDatabaseDDL(DatabaseDiff $databaseDiff)
    {

        $ret = '';

        foreach ($databaseDiff->getRemovedEntities() as $entity) {
            $ret .= $this->getDropEntityDDL($entity);
        }

        foreach ($databaseDiff->getSqlRenamedEntities() as $fromEntityName => $toEntityName) {
            $ret .= $this->getRenameEntityDDL($fromEntityName, $toEntityName);
        }

        foreach ($databaseDiff->getModifiedEntities() as $entityDiff) {
            $ret .= $this->getModifyEntityDDL($entityDiff);
        }

        foreach ($databaseDiff->getAddedEntities() as $entity) {
            $ret .= $this->getAddEntityDDL($entity);
        }

        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    /**
     * Builds the DDL SQL to rename a entity
     * @return string
     */
    public function getRenameEntityDDL($fromEntityName, $toEntityName)
    {
        $pattern = "
RENAME TABLE %s TO %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($fromEntityName),
            $this->quoteIdentifier($toEntityName)
        );
    }

    /**
     * Builds the DDL SQL to remove a field
     *
     * @return string
     */
    public function getRemoveFieldDDL(Field $field)
    {
        $pattern = "
ALTER TABLE %s DROP %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($field->getEntity()->getSqlName()),
            $this->quoteIdentifier($field->getSqlName())
        );
    }

    /**
     * Builds the DDL SQL to rename a field
     * @return string
     */
    public function getRenameFieldDDL(Field $fromField, Field $toField)
    {
        return $this->getChangeFieldDDL($fromField, $toField);
    }

    /**
     * Builds the DDL SQL to modify a field
     *
     * @return string
     */
    public function getModifyFieldDDL(FieldDiff $fieldDiff)
    {
        return $this->getChangeFieldDDL($fieldDiff->getFromField(), $fieldDiff->getToField());
    }

    /**
     * Builds the DDL SQL to change a field
     * @return string
     */
    public function getChangeFieldDDL(Field $fromField, Field $toField)
    {
        $pattern = "
ALTER TABLE %s CHANGE %s %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($fromField->getEntity()->getSqlName()),
            $this->quoteIdentifier($fromField->getSqlName()),
            $this->getFieldDDL($toField)
        );
    }
    /**
     * Builds the DDL SQL to modify a list of fields
     *
     * @return string
     */
    public function getModifyFieldsDDL($fieldDiffs)
    {
        $ret = '';
        foreach ($fieldDiffs as $fieldDiff) {
            $ret .= $this->getModifyFieldDDL($fieldDiff);
        }

        return $ret;
    }

    /**
     * @see Platform::supportsSchemas()
     */
    public function supportsSchemas()
    {
        return true;
    }

    public function hasSize($sqlType)
    {
        return !in_array($sqlType, array(
            'MEDIUMTEXT',
            'LONGTEXT',
            'BLOB',
            'MEDIUMBLOB',
            'LONGBLOB',
        ));
    }

    public function getDefaultTypeSizes()
    {
        return array(
            'char'     => 1,
            'tinyint'  => 4,
            'smallint' => 6,
            'int'      => 11,
            'bigint'   => 20,
            'decimal'  => 10,
        );
    }

    /**
     * Escape the string for RDBMS.
     * @param  string $text
     * @return string
     */
    public function disconnectedEscapeText($text)
    {
        return addslashes($text);
    }

    /**
     * {@inheritdoc}
     *
     * MySQL documentation says that identifiers cannot contain '.'. Thus it
     * should be safe to split the string by '.' and quote each part individually
     * to allow for a <schema>.<entity> or <entity>.<field> syntax.
     *
     * @param  string $text the identifier
     * @return string the quoted identifier
     */
    public function doQuoting($text)
    {
        return '`' . strtr($text, array('.' => '`.`')) . '`';
    }

    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s';
    }

    public function getFieldBindingPHP(Field $field, $identifier, $fieldValueAccessor, $tab = "            ")
    {
        // FIXME - This is a temporary hack to get around apparent bugs w/ PDO+MYSQL
        // See http://pecl.php.net/bugs/bug.php?id=9919
        if ($field->getPDOType() === \PDO::PARAM_BOOL) {
            return sprintf(
                "
%s\$stmt->bindValue(%s, (int) %s, PDO::PARAM_INT);",
                $tab,
                $identifier,
                $fieldValueAccessor
            );
        }

        return parent::getFieldBindingPHP($field, $identifier, $fieldValueAccessor, $tab);
    }
}
