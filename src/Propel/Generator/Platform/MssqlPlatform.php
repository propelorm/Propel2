<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;

/**
 * MS SQL PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class MssqlPlatform extends SqlDefaultPlatform
{
    protected static $dropCount = 0;

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();

        $this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, "INT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "INT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "FLOAT"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "VARCHAR(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "VARCHAR(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, "DATETIME"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BU_DATE, "DATETIME"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, "DATETIME"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, "DATETIME"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BU_TIMESTAMP, "DATETIME"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BINARY(7132)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, "VARBINARY(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, "VARCHAR(MAX)"));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, "VARCHAR(MAX)"));
    }

    public function getMaxFieldNameLength()
    {
        return 128;
    }

    public function getNullString($notNull)
    {
        return $notNull ? 'NOT NULL' : 'NULL';
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

    public function supportsInsertNullPk()
    {
        return false;
    }

    public function getDropEntityDDL(Entity $entity)
    {
        $ret = '';
        foreach ($entity->getRelations() as $relation) {
            $ret .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='" . $relation->getName() . "')
    ALTER TABLE " . $this->quoteIdentifier($entity->getFQTableName()) . " DROP CONSTRAINT " . $this->quoteIdentifier($relation->getName()) . ";
";
        }

        self::$dropCount++;

        $ret .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = '" . $entity->getFQTableName() . "')
BEGIN
    DECLARE @reftable_" . self::$dropCount . " nvarchar(60), @constraintname_" . self::$dropCount . " nvarchar(60)
    DECLARE refcursor CURSOR FOR
    select reftables.name tablename, cons.name constraintname
        from sysobjects tables,
            sysobjects reftables,
            sysobjects cons,
            sysreferences ref
        where tables.id = ref.rkeyid
            and cons.id = ref.constid
            and reftables.id = ref.fkeyid
            and tables.name = '" . $entity->getFQTableName() . "'
    OPEN refcursor
    FETCH NEXT from refcursor into @reftable_" . self::$dropCount . ", @constraintname_" . self::$dropCount . "
    while @@FETCH_STATUS = 0
    BEGIN
        exec ('alter table '+@reftable_" . self::$dropCount . "+' drop constraint '+@constraintname_" . self::$dropCount . ")
        FETCH NEXT from refcursor into @reftable_" . self::$dropCount . ", @constraintname_" . self::$dropCount . "
    END
    CLOSE refcursor
    DEALLOCATE refcursor
    DROP TABLE " . $this->quoteIdentifier($entity->getFQTableName()) . "
END
";

        return $ret;
    }

    public function getPrimaryKeyDDL(Entity $entity)
    {
        if ($entity->hasPrimaryKey()) {
            $pattern = 'CONSTRAINT %s PRIMARY KEY (%s)';

            return sprintf($pattern,
                $this->quoteIdentifier($this->getPrimaryKeyName($entity)),
                $this->getFieldListDDL($entity->getPrimaryKey())
            );
        }
    }

    public function getAddRelationDDL(Relation $relation)
    {
        if ($relation->isSkipSql()) {
            return;
        }
        $pattern = "
BEGIN
ALTER TABLE %s ADD %s
END
;
";

        return sprintf($pattern,
            $this->quoteIdentifier($relation->getEntity()->getFQTableName()),
            $this->getRelationDDL($relation)
        );
    }

    public function getRelationDDL(Relation $relation)
    {
        if ($relation->isSkipSql()) {
            return;
        }
        $pattern = 'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)';
        $script = sprintf($pattern,
            $this->quoteIdentifier($relation->getName()),
            $this->getFieldListDDL($relation->getLocalFieldObjects()),
            $this->quoteIdentifier($relation->getForeignEntity()->getFQTableName()),
            $this->getFieldListDDL($relation->getForeignFieldObjects())
        );
        if ($relation->hasOnUpdate() && $relation->getOnUpdate() != Relation::SETNULL) {
            $script .= ' ON UPDATE ' . $relation->getOnUpdate();
        }
        if ($relation->hasOnDelete() && $relation->getOnDelete() != Relation::SETNULL) {
            $script .= ' ON DELETE '.  $relation->getOnDelete();
        }

        return $script;
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
        return !('INT' === $sqlType || 'TEXT' === $sqlType);
    }

    /**
     * {@inheritdoc}
     */
    public function doQuoting($text)
    {
        return '[' . strtr($text, array('.' => '].[')) . ']';
    }

    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s';
    }
}
