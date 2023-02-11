<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     *
     * @return void
     */
    protected function initializeTypeMap(): void
    {
        parent::initializeTypeMap();
        $this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain(PropelTypes::BOOLEAN_EMU, 'NUMBER', 1, 0);
        $this->schemaDomainMap[PropelTypes::CLOB] = new Domain(PropelTypes::CLOB_EMU, 'CLOB');
        $this->schemaDomainMap[PropelTypes::CLOB_EMU] = $this->schemaDomainMap[PropelTypes::CLOB];
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, 'NUMBER', 3, 0));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, 'NUMBER', 5, 0));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, 'NUMBER', 20, 0));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, 'FLOAT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARCHAR, 'NVARCHAR2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'NVARCHAR2', 2000));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, 'DATE'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, 'DATE'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATETIME, 'TIMESTAMP'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'TIMESTAMP'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'LONG RAW'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONG RAW'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'LONG RAW'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'NVARCHAR2', 2000));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'NUMBER', 3, 0));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SET, 'NUMBER'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::UUID, 'UUID'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::UUID_BINARY, 'RAW(16)'));
    }

    /**
     * @return int
     */
    public function getMaxColumnNameLength(): int
    {
        return 30;
    }

    /**
     * @return string
     */
    public function getNativeIdMethod(): string
    {
        return PlatformInterface::SEQUENCE;
    }

    /**
     * @return string
     */
    public function getAutoIncrement(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    public function supportsNativeDeleteTrigger(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getBeginDDL(): string
    {
        return "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';
";
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return string
     */
    public function getAddTablesDDL(Database $database): string
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

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddTableDDL(Table $table): string
    {
        $tableDescription = $table->hasDescription() ? $this->getCommentLineDDL($table->getDescription()) : '';

        $lines = [];

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
        $ret = sprintf(
            $pattern,
            $tableDescription,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines),
            $this->generateBlockStorage($table),
        );

        $ret .= $this->getAddPrimaryKeyDDL($table);
        $ret .= $this->getAddSequencesDDL($table);

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddPrimaryKeyDDL(Table $table): string
    {
        if (is_array($table->getPrimaryKey()) && count($table->getPrimaryKey())) {
            return parent::getAddPrimaryKeyDDL($table);
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddSequencesDDL(Table $table): string
    {
        if ($table->getIdMethod() === 'native') {
            $pattern = "
CREATE SEQUENCE %s
    INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";

            return sprintf(
                $pattern,
                $this->quoteIdentifier($this->getSequenceName($table)),
            );
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getDropTableDDL(Table $table): string
    {
        $ret = "
DROP TABLE " . $this->quoteIdentifier($table->getName()) . " CASCADE CONSTRAINTS;
";
        if ($table->getIdMethod() == IdMethod::NATIVE) {
            $ret .= "
DROP SEQUENCE " . $this->quoteIdentifier($this->getSequenceName($table)) . ";
";
        }

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getPrimaryKeyName(Table $table): string
    {
        $tableName = $table->getName();
        // pk constraint name must be 30 chars at most
        $tableName = substr($tableName, 0, min(27, strlen($tableName)));

        return $tableName . '_pk';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getPrimaryKeyDDL(Table $table): string
    {
        if ($table->hasPrimaryKey()) {
            $pattern = 'CONSTRAINT %s PRIMARY KEY (%s)%s';

            return sprintf(
                $pattern,
                $this->quoteIdentifier($this->getPrimaryKeyName($table)),
                $this->getColumnListDDL($table->getPrimaryKey()),
                $this->generateBlockStorage($table, true),
            );
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Unique $unique
     *
     * @return string
     */
    public function getUniqueDDL(Unique $unique): string
    {
        return sprintf(
            'CONSTRAINT %s UNIQUE (%s)',
            $this->quoteIdentifier($unique->getName()),
            $this->getColumnListDDL($unique->getColumnObjects()),
        );
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string
     */
    public function getForeignKeyDDL(ForeignKey $fk): string
    {
        if ($fk->isSkipSql() || $fk->isPolymorphic()) {
            return '';
        }

        $pattern = "CONSTRAINT %s
    FOREIGN KEY (%s) REFERENCES %s (%s)";
        $script = sprintf(
            $pattern,
            $this->quoteIdentifier($fk->getName()),
            $this->getColumnListDDL($fk->getLocalColumnObjects()),
            $this->quoteIdentifier($fk->getForeignTableName()),
            $this->getColumnListDDL($fk->getForeignColumnObjects()),
        );
        if ($fk->hasOnDelete()) {
            $script .= "
    ON DELETE " . $fk->getOnDelete();
        }

        return $script;
    }

    /**
     * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
     *
     * @return bool
     */
    public function hasStreamBlobImpl(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function doQuoting(string $text): string
    {
        return $text;
    }

    /**
     * @return string
     */
    public function getTimestampFormatter(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @note While Oracle supports schemas, they're user-based and
     *             are really only good for creating a database layout in
     *             one fell swoop.
     *
     * @see Platform::supportsSchemas()
     *
     * @return bool
     */
    public function supportsSchemas(): bool
    {
        return false;
    }

    /**
     * Generate oracle block storage
     *
     * @param \Propel\Generator\Model\Table|\Propel\Generator\Model\Index $object object with vendor parameters
     * @param bool $isPrimaryKey is a primary key vendor part
     *
     * @return string oracle vendor sql part
     */
    public function generateBlockStorage($object, bool $isPrimaryKey = false): string
    {
        $vendorSpecific = $object->getVendorInfoForType('oracle');
        if ($vendorSpecific->isEmpty()) {
            return '';
        }

        if ($isPrimaryKey) {
            $physicalParameters = "
USING INDEX
";
            $prefix = 'PK';
        } else {
            $physicalParameters = "\n";
            $prefix = '';
        }

        if ($vendorSpecific->hasParameter($prefix . 'PCTFree')) {
            $physicalParameters .= 'PCTFREE ' . $vendorSpecific->getParameter($prefix . 'PCTFree') . "
";
        }
        if ($vendorSpecific->hasParameter($prefix . 'InitTrans')) {
            $physicalParameters .= 'INITRANS ' . $vendorSpecific->getParameter($prefix . 'InitTrans') . "
";
        }
        if ($vendorSpecific->hasParameter($prefix . 'MinExtents') || $vendorSpecific->hasParameter($prefix . 'MaxExtents') || $vendorSpecific->hasParameter($prefix . 'PCTIncrease')) {
            $physicalParameters .= "STORAGE
(
";
            if ($vendorSpecific->hasParameter($prefix . 'MinExtents')) {
                $physicalParameters .= '    MINEXTENTS ' . $vendorSpecific->getParameter($prefix . 'MinExtents') . "
";
            }
            if ($vendorSpecific->hasParameter($prefix . 'MaxExtents')) {
                $physicalParameters .= '    MAXEXTENTS ' . $vendorSpecific->getParameter($prefix . 'MaxExtents') . "
";
            }
            if ($vendorSpecific->hasParameter($prefix . 'PCTIncrease')) {
                $physicalParameters .= '    PCTINCREASE ' . $vendorSpecific->getParameter($prefix . 'PCTIncrease') . "
";
            }
            $physicalParameters .= ")
";
        }
        if ($vendorSpecific->hasParameter($prefix . 'Tablespace')) {
            $physicalParameters .= 'TABLESPACE ' . $vendorSpecific->getParameter($prefix . 'Tablespace');
        }

        return $physicalParameters;
    }

    /**
     * Builds the DDL SQL to add an Index.
     *
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    public function getAddIndexDDL(Index $index): string
    {
        // don't create index form primary key
        if ($this->getPrimaryKeyName($index->getTable()) == $this->quoteIdentifier($index->getName())) {
            return '';
        }

        $pattern = "
CREATE %sINDEX %s ON %s (%s)%s;
";

        return sprintf(
            $pattern,
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTable()->getName()),
            $this->getColumnListDDL($index->getColumnObjects()),
            $this->generateBlockStorage($index),
        );
    }

    /**
     * Get the PHP snippet for binding a value to a column.
     * Warning: duplicates logic from OracleAdapter::bindValue().
     * Any code modification here must be ported there.
     *
     * @param \Propel\Generator\Model\Column $column
     * @param string $identifier
     * @param string $columnValueAccessor
     * @param string $tab
     *
     * @return string
     */
    public function getColumnBindingPHP(Column $column, string $identifier, string $columnValueAccessor, string $tab = '            '): string
    {
        if ($column->getType() === PropelTypes::CLOB_EMU) {
            return sprintf(
                "%s\$stmt->bindParam(%s, %s, %s, strlen(%s));
",
                $tab,
                $identifier,
                $columnValueAccessor,
                PropelTypes::getPdoTypeString($column->getType()),
                $columnValueAccessor,
            );
        }

        return parent::getColumnBindingPHP($column, $identifier, $columnValueAccessor, $tab);
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from OracleAdapter::getId().
     * Any code modification here must be ported there.
     *
     * @param string $columnValueMutator
     * @param string $connectionVariableName
     * @param string $sequenceName
     * @param string $tab
     * @param string|null $phpType
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return string
     */
    public function getIdentifierPhp(
        string $columnValueMutator,
        string $connectionVariableName = '$con',
        string $sequenceName = '',
        string $tab = '            ',
        ?string $phpType = null
    ): string {
        if (!$sequenceName) {
            throw new EngineException('Oracle needs a sequence name to fetch primary keys');
        }
        $snippet = "
\$dataFetcher = %s->query('SELECT %s.nextval FROM dual');
%s = %s\$dataFetcher->fetchColumn();";
        $script = sprintf(
            $snippet,
            $connectionVariableName,
            $sequenceName,
            $columnValueMutator,
            $phpType ? '(' . $phpType . ') ' : '',
        );

        return preg_replace('/^/m', $tab, $script);
    }
}
