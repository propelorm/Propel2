<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;

/**
 * Oracle PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Denis Dalmais
 */
class OraclePlatform extends SqlDefaultPlatform
{

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain(PropelTypes::BOOLEAN_EMU, 'NUMBER', '1', '0');
        $this->schemaDomainMap[PropelTypes::CLOB] = new Domain(PropelTypes::CLOB_EMU, 'CLOB');
        $this->schemaDomainMap[PropelTypes::CLOB_EMU] = $this->schemaDomainMap[PropelTypes::CLOB];
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, 'NUMBER', '3', '0'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, 'NUMBER', '5', '0'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, 'NUMBER', '20', '0'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, 'FLOAT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARCHAR, 'NVARCHAR2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'NVARCHAR2', '2000'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, 'DATE'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, 'DATE'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'TIMESTAMP'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'LONG RAW'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONG RAW'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'LONG RAW'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'NVARCHAR2', '2000'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'NVARCHAR2'));

    }

    public function getMaxFieldNameLength()
    {
        return 30;
    }

    public function getNativeIdMethod()
    {
        return PlatformInterface::SEQUENCE;
    }

    public function getAutoIncrement()
    {
        return '';
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

    public function getBeginDDL()
    {
        return "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';
";
    }

    public function getAddEntitiesDDL(Database $database)
    {
        $ret = $this->getBeginDDL();
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret .= $this->getCommentBlockDDL($entity->getName());
            $ret .= $this->getDropEntityDDL($entity);
            $ret .= $this->getAddEntityDDL($entity);
            $ret .= $this->getAddIndicesDDL($entity);
        }
        $ret2 = '';
        foreach ($database->getEntitiesForSql() as $entity) {
            $ret2 .= $this->getAddRelationsDDL($entity);
        }
        if ($ret2) {
            $ret .= $this->getCommentBlockDDL('Foreign Keys') . $ret2;
        }
        $ret .= $this->getEndDDL();

        return $ret;
    }

    public function getAddEntityDDL(Entity $entity)
    {
        $entityDescription = $entity->hasDescription() ? $this->getCommentLineDDL($entity->getDescription()) : '';

        $lines = array();

        foreach ($entity->getFields() as $field) {
            $lines[] = $this->getFieldDDL($field);
        }

        foreach ($entity->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        $sep = ",
    ";

        $pattern = "
%sCREATE TABLE %s
(
    %s
)%s;
";
        $ret = sprintf($pattern,
            $entityDescription,
            $this->quoteIdentifier($entity->getName()),
            implode($sep, $lines),
            $this->generateBlockStorage($entity)
        );

        $ret .= $this->getAddPrimaryKeyDDL($entity);
        $ret .= $this->getAddSequencesDDL($entity);

        return $ret;
    }

    public function getAddPrimaryKeyDDL(Entity $entity)
    {
        if (is_array($entity->getPrimaryKey()) && count($entity->getPrimaryKey())) {
            return parent::getAddPrimaryKeyDDL($entity);
        }
    }

    public function getAddSequencesDDL(Entity $entity)
    {
        if ('native' === $entity->getIdMethod()) {
            $pattern = "
CREATE SEQUENCE %s
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";

            return sprintf($pattern,
                $this->quoteIdentifier($this->getSequenceName($entity))
            );
        }
    }

    public function getDropEntityDDL(Entity $entity)
    {
        $ret = "
DROP TABLE " . $this->quoteIdentifier($entity->getName(), $entity) . " CASCADE CONSTRAINTS;
";
        if ($entity->getIdMethod() == IdMethod::NATIVE) {
            $ret .= "
DROP SEQUENCE " . $this->quoteIdentifier($this->getSequenceName($entity)) . ";
";
        }

        return $ret;
    }

    public function getPrimaryKeyName(Entity $entity)
    {
        $entityName = $entity->getName();
        // pk constraint name must be 30 chars at most
        $entityName = substr($entityName, 0, min(27, strlen($entityName)));

        return $entityName . '_pk';
    }

    public function getPrimaryKeyDDL(Entity $entity)
    {
        if ($entity->hasPrimaryKey()) {
            $pattern = 'CONSTRAINT %s PRIMARY KEY (%s)%s';

            return sprintf($pattern,
                $this->quoteIdentifier($this->getPrimaryKeyName($entity)),
                $this->getFieldListDDL($entity->getPrimaryKey()),
                $this->generateBlockStorage($entity, true)
            );
        }
    }

    public function getUniqueDDL(Unique $unique)
    {
        return sprintf('CONSTRAINT %s UNIQUE (%s)',
            $this->quoteIdentifier($unique->getName()),
            $this->getFieldListDDL($unique->getFieldObjects())
        );
    }

    public function getRelationDDL(Relation $relation)
    {
        if ($relation->isSkipSql()) {
            return;
        }
        $pattern = "CONSTRAINT %s
    FOREIGN KEY (%s) REFERENCES %s (%s)";
        $script = sprintf($pattern,
            $this->quoteIdentifier($relation->getName()),
            $this->getFieldListDDL($relation->getLocalFieldObjects()),
            $this->quoteIdentifier($relation->getForeignEntity()->getFQTableName()),
            $this->getFieldListDDL($relation->getForeignFieldObjects())
        );
        if ($relation->hasOnDelete()) {
            $script .= "
    ON DELETE " . $relation->getOnDelete();
        }

        return $script;
    }

    /**
     * Whether the underlying PDO driver for this platform returns BLOB fields as streams (instead of strings).
     * @return boolean
     */
    public function hasStreamBlobImpl()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function doQuoting($text)
    {
        return $text;
    }

    public function getTimestampFormatter()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @note       While Oracle supports schemas, they're user-based and
     *             are really only good for creating a database layout in
     *             one fell swoop.
     * @see Platform::supportsSchemas()
     */
    public function supportsSchemas()
    {
        return false;
    }

    /**
     * Generate oracle block storage
     *
     * @param Entity|Index $object       object with vendor parameters
     * @param boolean     $isPrimaryKey is a primary key vendor part
     *
     * @return string oracle vendor sql part
     */
    public function generateBlockStorage($object, $isPrimaryKey = false)
    {
        $vendorSpecific = $object->getVendorInfoForType('oracle');
        if ($vendorSpecific->isEmpty()) {
            return '';
        }

        if ($isPrimaryKey) {
            $physicalParameters = "
USING INDEX
";
            $prefix = "PK";
        } else {
            $physicalParameters = "\n";
            $prefix = "";
        }

        if ($vendorSpecific->hasParameter($prefix.'PCTFree')) {
            $physicalParameters .= "PCTFREE " . $vendorSpecific->getParameter($prefix.'PCTFree') . "
";
        }
        if ($vendorSpecific->hasParameter($prefix.'InitTrans')) {
            $physicalParameters .= "INITRANS " . $vendorSpecific->getParameter($prefix.'InitTrans') . "
";
        }
        if ($vendorSpecific->hasParameter($prefix.'MinExtents') || $vendorSpecific->hasParameter($prefix.'MaxExtents') || $vendorSpecific->hasParameter($prefix.'PCTIncrease')) {
            $physicalParameters .= "STORAGE
(
";
            if ($vendorSpecific->hasParameter($prefix.'MinExtents')) {
                $physicalParameters .= "    MINEXTENTS " . $vendorSpecific->getParameter($prefix.'MinExtents') . "
";
            }
            if ($vendorSpecific->hasParameter($prefix.'MaxExtents')) {
                $physicalParameters .= "    MAXEXTENTS " . $vendorSpecific->getParameter($prefix.'MaxExtents') . "
";
            }
            if ($vendorSpecific->hasParameter($prefix.'PCTIncrease')) {
                $physicalParameters .= "    PCTINCREASE " . $vendorSpecific->getParameter($prefix.'PCTIncrease') . "
";
            }
            $physicalParameters .= ")
";
        }
        if ($vendorSpecific->hasParameter($prefix.'Tablespace')) {
            $physicalParameters .= "TABLESPACE " . $vendorSpecific->getParameter($prefix.'Tablespace');
        }

        return $physicalParameters;
    }

    /**
     * Builds the DDL SQL to add an Index.
     *
     * @param  Index  $index
     * @return string
     */
    public function getAddIndexDDL(Index $index)
    {
        // don't create index form primary key
        if ($this->getPrimaryKeyName($index->getEntity()) == $this->quoteIdentifier($index->getName())) {
            return '';
        }

        $pattern = "
CREATE %sINDEX %s ON %s (%s)%s;
";

        return sprintf($pattern,
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getEntity()->getName()),
            $this->getFieldListDDL($index->getFieldObjects()),
            $this->generateBlockStorage($index)
        );
    }

    /**
     * Get the PHP snippet for binding a value to a field.
     * Warning: duplicates logic from OracleAdapter::bindValue().
     * Any code modification here must be ported there.
     */
    public function getFieldBindingPHP(Field $field, $identifier, $fieldValueAccessor, $tab = "            ")
    {
        if ($field->getPDOType() == PropelTypes::CLOB_EMU) {
            return sprintf(
                "%s\$stmt->bindParam(%s, %s, %s, strlen(%s));
",
                $tab,
                $identifier,
                $fieldValueAccessor,
                PropelTypes::getPdoTypeString($field->getType()),
                $fieldValueAccessor
            );
        }

        return parent::getFieldBindingPHP($field, $identifier, $fieldValueAccessor, $tab);
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from OracleAdapter::getId().
     * Any code modification here must be ported there.
     */
    public function getIdentifierPhp($fieldValueMutator, $connectionVariableName = '$con', $sequenceName = '', $tab = "            ")
    {
        if (!$sequenceName) {
            throw new EngineException('Oracle needs a sequence name to fetch primary keys');
        }
        $snippet = "
\$dataFetcher = %s->query('SELECT %s.nextval FROM dual');
%s = \$dataFetcher->fetchField();";
        $script = sprintf($snippet,
            $connectionVariableName,
            $sequenceName,
            $fieldValueMutator
        );

        return preg_replace('/^/m', $tab, $script);
    }
}
