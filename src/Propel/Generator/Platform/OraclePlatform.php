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
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;

/**
 * Oracle PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Denis Dalmais
 */
class OraclePlatform extends DefaultPlatform
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
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'NUMBER', '3', '0'));

    }

    public function getMaxColumnNameLength()
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

    public function getAddTablesDDL(Database $database)
    {
        $ret = $this->getBeginDDL();
        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getCommentBlockDDL($table->getName());
            $ret .= $this->getDropTableDDL($table);
            $ret .= $this->getAddTableDDL($table);
            $ret .= $this->getAddIndicesDDL($table);
        }
        $ret2 = '';
        foreach ($database->getTablesForSql() as $table) {
            $ret2 .= $this->getAddForeignKeysDDL($table);
        }
        if ($ret2) {
            $ret .= $this->getCommentBlockDDL('Foreign Keys') . $ret2;
        }
        $ret .= $this->getEndDDL();

        return $ret;
    }

    public function getAddTableDDL(Table $table)
    {
        $tableDescription = $table->hasDescription() ? $this->getCommentLineDDL($table->getDescription()) : '';

        $lines = array();

        foreach ($table->getColumns() as $column) {
            $lines[] = $this->getColumnDDL($column);
        }

        foreach ($table->getUnices() as $unique) {
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
            $tableDescription,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines),
            $this->generateBlockStorage($table)
        );

        $ret .= $this->getAddPrimaryKeyDDL($table);
        $ret .= $this->getAddSequencesDDL($table);

        return $ret;
    }

    public function getAddPrimaryKeyDDL(Table $table)
    {
        if (is_array($table->getPrimaryKey()) && count($table->getPrimaryKey())) {
            return parent::getAddPrimaryKeyDDL($table);
        }
    }

    public function getAddSequencesDDL(Table $table)
    {
        if ('native' === $table->getIdMethod()) {
            $pattern = "
CREATE SEQUENCE %s
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";

            return sprintf($pattern,
                $this->quoteIdentifier($this->getSequenceName($table))
            );
        }
    }

    public function getDropTableDDL(Table $table)
    {
        $ret = "
DROP TABLE " . $this->quoteIdentifier($table->getName(), $table) . " CASCADE CONSTRAINTS;
";
        if ($table->getIdMethod() == IdMethod::NATIVE) {
            $ret .= "
DROP SEQUENCE " . $this->quoteIdentifier($this->getSequenceName($table)) . ";
";
        }

        return $ret;
    }

    public function getPrimaryKeyName(Table $table)
    {
        $tableName = $table->getName();
        // pk constraint name must be 30 chars at most
        $tableName = substr($tableName, 0, min(27, strlen($tableName)));

        return $tableName . '_pk';
    }

    public function getPrimaryKeyDDL(Table $table)
    {
        if ($table->hasPrimaryKey()) {
            $pattern = 'CONSTRAINT %s PRIMARY KEY (%s)%s';

            return sprintf($pattern,
                $this->quoteIdentifier($this->getPrimaryKeyName($table)),
                $this->getColumnListDDL($table->getPrimaryKey()),
                $this->generateBlockStorage($table, true)
            );
        }
    }

    public function getUniqueDDL(Unique $unique)
    {
        return sprintf('CONSTRAINT %s UNIQUE (%s)',
            $this->quoteIdentifier($unique->getName()),
            $this->getColumnListDDL($unique->getColumnObjects())
        );
    }

    public function getForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return;
        }
        $pattern = "CONSTRAINT %s
    FOREIGN KEY (%s) REFERENCES %s (%s)";
        $script = sprintf($pattern,
            $this->quoteIdentifier($fk->getName()),
            $this->getColumnListDDL($fk->getLocalColumnObjects()),
            $this->quoteIdentifier($fk->getForeignTableName()),
            $this->getColumnListDDL($fk->getForeignColumnObjects())
        );
        if ($fk->hasOnDelete()) {
            $script .= "
    ON DELETE " . $fk->getOnDelete();
        }

        return $script;
    }

    /**
     * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
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
     * @param Table|Index $object       object with vendor parameters
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
        if ($this->getPrimaryKeyName($index->getTable()) == $this->quoteIdentifier($index->getName())) {
            return '';
        }

        $pattern = "
CREATE %sINDEX %s ON %s (%s)%s;
";

        return sprintf($pattern,
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTable()->getName()),
            $this->getColumnListDDL($index->getColumnObjects()),
            $this->generateBlockStorage($index)
        );
    }

    /**
     * Get the PHP snippet for binding a value to a column.
     * Warning: duplicates logic from OracleAdapter::bindValue().
     * Any code modification here must be ported there.
     */
    public function getColumnBindingPHP(Column $column, $identifier, $columnValueAccessor, $tab = "            ")
    {
        if ($column->getPDOType() == PropelTypes::CLOB_EMU) {
            return sprintf(
                "%s\$stmt->bindParam(%s, %s, %s, strlen(%s));
",
                $tab,
                $identifier,
                $columnValueAccessor,
                PropelTypes::getPdoTypeString($column->getType()),
                $columnValueAccessor
            );
        }

        return parent::getColumnBindingPHP($column, $identifier, $columnValueAccessor, $tab);
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from OracleAdapter::getId().
     * Any code modification here must be ported there.
     */
    public function getIdentifierPhp($columnValueMutator, $connectionVariableName = '$con', $sequenceName = '', $tab = "            ")
    {
        if (!$sequenceName) {
            throw new EngineException('Oracle needs a sequence name to fetch primary keys');
        }
        $snippet = "
\$dataFetcher = %s->query('SELECT %s.nextval FROM dual');
%s = \$dataFetcher->fetchColumn();";
        $script = sprintf($snippet,
            $connectionVariableName,
            $sequenceName,
            $columnValueMutator
        );

        return preg_replace('/^/m', $tab, $script);
    }
}
