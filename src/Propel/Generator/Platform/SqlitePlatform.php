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
use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;

/**
 * SQLite PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqlitePlatform extends SqlDefaultPlatform
{
    /**
     * If we should generate FOREIGN KEY statements.
     * This is since SQLite version 3.6.19 possible.
     *
     * @var bool
     */
    protected $relationSupport = null;

    /**
     * If we should alter the entity through creating a temporarily created entity,
     * moving all items to the new one and finally rename the temp entity.
     *
     * @var bool
     */
    protected $entityAlteringWorkaround = true;

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();

        $version = \SQLite3::version();
        $version = $version['versionString'];

        $this->relationSupport = version_compare($version, '3.6.19') >= 0;

        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'DECIMAL'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'LONGTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'TEXT'));
    }

    public function getSchemaDelimiter()
    {
        return '§';
    }

    public function getDefaultTypeSizes()
    {
        return array(
            'char'      => 1,
            'character' => 1,
            'integer'   => 32,
            'bigint'    => 64,
            'smallint'  => 16,
            'double precision' => 54
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        parent::setGeneratorConfig($generatorConfig);

        if (null !== ($relationSupport = $generatorConfig->getConfigProperty('database.adapter.sqlite.relation'))) {
            $this->relationSupport = filter_var($relationSupport, FILTER_VALIDATE_BOOLEAN);;
        }
        if (null !== ($entityAlteringWorkaround = $generatorConfig->getConfigProperty('database.adapter.sqlite.entityAlteringWorkaround'))) {
            $this->entityAlteringWorkaround = filter_var($entityAlteringWorkaround, FILTER_VALIDATE_BOOLEAN);;;
        }
    }

    /**
     * Builds the DDL SQL to remove a list of fields
     *
     * @param  Field[] $fields
     * @return string
     */
    public function getAddFieldsDDL($fields)
    {
        $ret = '';
        $pattern = "
ALTER TABLE %s ADD %s;
";
        foreach ($fields as $field) {
            $ret .= sprintf($pattern,
                $this->quoteIdentifier($field->getEntity()->getFQTableName()),
                $this->getFieldDDL($field)
            );
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getModifyEntityDDL(EntityDiff $entityDiff)
    {
        $changedNotEdientityThroughDirectDDL = $this->entityAlteringWorkaround && (false
            || $entityDiff->hasModifiedFks()
            || $entityDiff->hasModifiedIndices()
            || $entityDiff->hasModifiedFields()
            || $entityDiff->hasRenamedFields()

            || $entityDiff->hasRemovedFks()
            || $entityDiff->hasRemovedIndices()
            || $entityDiff->hasRemovedFields()

            || $entityDiff->hasAddedIndices()
            || $entityDiff->hasAddedFks()
            || $entityDiff->hasAddedPkFields()
        );

        if ($this->entityAlteringWorkaround && !$changedNotEdientityThroughDirectDDL && $entityDiff->hasAddedFields()) {

            $addedCols = $entityDiff->getAddedFields();
            foreach ($addedCols as $field) {

                $sqlChangeNotSupported = false

                    //The field may not have a PRIMARY KEY or UNIQUE constraint.
                    || $field->isPrimaryKey()
                    || $field->isUnique()

                    //The field may not have a default value of CURRENT_TIME, CURRENT_DATE, CURRENT_TIMESTAMP,
                    //or an expression in parentheses.
                    || false !== array_search(
                        $field->getDefaultValue(), array('CURRENT_TIME', 'CURRENT_DATE', 'CURRENT_TIMESTAMP'))
                    || substr(trim($field->getDefaultValue()), 0, 1) == '('

                    //If a NOT NULL constraint is specified, then the field must have a default value other than NULL.
                    || ($field->isNotNull() && $field->getDefaultValue() == 'NULL')
                ;

                if ($sqlChangeNotSupported) {
                    $changedNotEdientityThroughDirectDDL = true;
                    break;
                }

            }
        }

        if ($changedNotEdientityThroughDirectDDL) {
            return $this->getMigrationEntityDDL($entityDiff);
        }

        return parent::getModifyEntityDDL($entityDiff);
    }

    /**
     * Creates a temporarily created entity with the new schema,
     * moves all items into it and drops the origin as well as renames the temp entity to the origin then.
     *
     * @param  EntityDiff $entityDiff
     * @return string
     */
    public function getMigrationEntityDDL(EntityDiff $entityDiff)
    {
        $pattern = "
CREATE TEMPORARY TABLE %s AS SELECT %s FROM %s;
DROP TABLE %s;
%s
INSERT INTO %s (%s) SELECT %s FROM %s;
DROP TABLE %s;
";

        $originEntity     = clone $entityDiff->getFromEntity();
        $newEntity        = clone $entityDiff->getToEntity();

        $tempEntityName   = $newEntity->getTableName().'__temp__'.uniqid();

        $originEntityFields = $this->getFieldListDDL($originEntity->getFields());

        $fieldMap = []; /** struct: [<oldCol> => <newCol>] */
        //start with modified fields
        foreach ($entityDiff->getModifiedFields() as $diff) {
            $fieldMap[$diff->getFromField()->getColumnName()] = $diff->getToField()->getColumnName();
        }

        foreach ($entityDiff->getRenamedFields() as $field) {
            list ($from, $to) = $field;
            $fieldMap[$from->getColumnName()] = $to->getColumnName();
        }

        foreach ($newEntity->getFields() as $field) {
            if ($originEntity->hasField($field)) {
                if (!isset($fieldMap[$field->getColumnName()])) {
                    $fieldMap[$field->getColumnName()] = $field->getColumnName();
                }
            }

        }

        $createEntity = $this->getAddEntityDDL($newEntity);
        $createEntity .= $this->getAddIndicesDDL($newEntity);

        $sql = sprintf($pattern,
            $this->quoteIdentifier($tempEntityName), //CREATE TEMPORARY TABLE %s
            $originEntityFields, //select %s
            $this->quoteIdentifier($originEntity->getFQTableName()), //from %s
            $this->quoteIdentifier($originEntity->getFQTableName()), //drop entity %s
            $createEntity, //[create entity] %s
            $this->quoteIdentifier($originEntity->getFQTableName()), //insert into %s
            implode(', ', $fieldMap), //(%s)
            implode(', ', array_keys($fieldMap)), //select %s
            $this->quoteIdentifier($tempEntityName), //from %s
            $this->quoteIdentifier($tempEntityName) //drop entity %s
        );

        return $sql;
    }

    public function getBeginDDL()
    {
        return '
PRAGMA foreign_keys = OFF;
';
    }

    public function getEndDDL()
    {
        return '
PRAGMA foreign_keys = ON;
';
    }

    /**
     * {@inheritdoc}
     */
    public function getAddEntitiesDDL(Database $database)
    {
        $ret = '';
        foreach ($database->getEntitiesForSql() as $entity) {
            $this->normalizeEntity($entity);
        }
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getCommentBlockDDL($entity->getFQTableName());
            $ret .= $this->getDropEntityDDL($entity);
            $ret .= $this->getAddEntityDDL($entity);
            $ret .= $this->getAddIndicesDDL($entity);
        }

        return $ret;
    }

    /**
     * Unfortunately, SQLite does not support composite pks where one is AUTOINCREMENT,
     * so we have to flag both as NOT NULL and create in either way a UNIQUE constraint over pks since
     * those UNIQUE is otherwise automatically created by the sqlite engine.
     *
     * @param Entity $entity
     */
    public function normalizeEntity(Entity $entity)
    {
        if ($entity->getPrimaryKey()) {
            //search if there is already a UNIQUE constraint over the primary keys
            $pkUniqueExist = false;
            foreach ($entity->getUnices() as $unique) {
                $coversAllPrimaryKeys = true;
                foreach ($unique->getFields() as $fieldName) {
                    if (!$entity->getField($fieldName)->isPrimaryKey()) {
                        $coversAllPrimaryKeys = false;
                        break;
                    }
                }
                if ($coversAllPrimaryKeys) {
                    //there's already a unique constraint with the composite pk
                    $pkUniqueExist = true;
                    break;
                }
            }

            //there is none, let's create it
            if (!$pkUniqueExist) {
                $unique = new Unique();
                foreach ($entity->getPrimaryKey() as $pk) {
                    $unique->addField($pk);
                }
                $entity->addUnique($unique);
            }

            if ($entity->hasAutoIncrementPrimaryKey()) {
                foreach ($entity->getPrimaryKey() as $pk) {
                    //no pk can be NULL, as usual
                    $pk->setNotNull(true);
                    //in SQLite the field with the AUTOINCREMENT MUST be a primary key, too.
                    if (!$pk->isAutoIncrement()) {
                        //for all other sub keys we remove it, since we create a UNIQUE constraint over all primary keys.
                        $pk->setPrimaryKey(false);
                    }
                }
            }
        }

        parent::normalizeEntity($entity);
    }

    /**
     * Returns the SQL for the primary key of a Entity object
     * @return string
     */
    public function getPrimaryKeyDDL(Entity $entity)
    {
        if ($entity->hasPrimaryKey() && !$entity->hasAutoIncrementPrimaryKey()) {
            return 'PRIMARY KEY (' . $this->getFieldListDDL($entity->getPrimaryKey()) . ')';
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoveFieldDDL(Field $field)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getRenameFieldDDL(Field $fromField, Field $toField)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getModifyFieldDDL(FieldDiff $fieldDiff)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getModifyFieldsDDL($fieldDiffs)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDropPrimaryKeyDDL(Entity $entity)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAddPrimaryKeyDDL(Entity $entity)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAddRelationDDL(Relation $relation)
    {
        //not supported
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDropRelationDDL(Relation $relation)
    {
        //not supported
        return '';
    }

    /**
     * @link       http://www.sqlite.org/autoinc.html
     */
    public function getAutoIncrement()
    {
        return 'PRIMARY KEY AUTOINCREMENT';
    }

    public function getMaxFieldNameLength()
    {
        return 1024;
    }

    public function getFieldDDL(Field $col)
    {
        if ($col->isAutoIncrement()) {
            $col->setType('INTEGER');
            $col->setDomainForType('INTEGER');
        }

        if ($col->getDefaultValue()
            && $col->getDefaultValue()->isExpression()
            && 'CURRENT_TIMESTAMP' === $col->getDefaultValue()->getValue()) {
            //sqlite use CURRENT_TIMESTAMP different than mysql/pgsql etc
            //we set it to the more common behavior
            $col->setDefaultValue(
                new FieldDefaultValue("(datetime(CURRENT_TIMESTAMP, 'localtime'))", FieldDefaultValue::TYPE_EXPR)
            );
        }

        return parent::getFieldDDL($col);
    }

    public function getAddEntityDDL(Entity $entity)
    {
        $entity = clone $entity;
        $entityDescription = $entity->hasDescription() ? $this->getCommentLineDDL($entity->getDescription()) : '';

        $lines = array();

        foreach ($entity->getFields() as $field) {
            $lines[] = $this->getFieldDDL($field);
        }

        if ($entity->hasPrimaryKey() && ($pk = $this->getPrimaryKeyDDL($entity))) {
            $lines[] = $pk;
        }

        foreach ($entity->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        if ($this->relationSupport) {
            foreach ($entity->getRelations() as $relation) {
                if ($relation->isSkipSql()) {
                    continue;
                }
                $lines[] = str_replace("
    ", "
        ", $this->getRelationDDL($relation));
            }
        }

        $sep = ",
    ";

        $pattern = "
%sCREATE TABLE %s
(
    %s
);
";

        return sprintf($pattern,
            $entityDescription,
            $this->quoteIdentifier($entity->getFQTableName()),
            implode($sep, $lines)
        );
    }

    public function getRelationDDL(Relation $relation)
    {
        if ($relation->isSkipSql() || !$this->relationSupport) {
            return;
        }

        $pattern = "FOREIGN KEY (%s) REFERENCES %s (%s)";

        $script = sprintf($pattern,
            $this->getFieldListDDL($relation->getLocalFieldObjects()),
            $this->quoteIdentifier($relation->getForeignEntity()->getFQTableName()),
            $this->getFieldListDDL($relation->getForeignFieldObjects())
        );

        if ($relation->hasOnUpdate()) {
            $script .= "
    ON UPDATE " . $relation->getOnUpdate();
        }
        if ($relation->hasOnDelete()) {
            $script .= "
    ON DELETE " . $relation->getOnDelete();
        }

        return $script;
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

    /**
     * {@inheritdoc}
     */
    public function doQuoting($text)
    {
        return '[' . strtr($text, array('.' => '].[')) . ']';
    }

    public function supportsSchemas()
    {
        return true;
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

}
