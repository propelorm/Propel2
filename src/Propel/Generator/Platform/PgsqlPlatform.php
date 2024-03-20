<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;

/**
 * Postgresql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Niklas Närhinen <niklas@narhinen.net>
 */
class PgsqlPlatform extends DefaultPlatform
{
    /**
     * @var string
     */
    protected $createOrDropSequences = '';

    /**
     * Initializes db specific domain mapping.
     *
     * @return void
     */
    protected function initializeTypeMap(): void
    {
        parent::initializeTypeMap();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'BOOLEAN'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, 'INT2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, 'INT2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, 'INT8'));
        //$this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, 'FLOAT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, 'DOUBLE PRECISION'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::FLOAT, 'DOUBLE PRECISION'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'BYTEA'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'TEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'INT2'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::SET, 'INT4'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, 'NUMERIC'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATETIME, 'TIMESTAMP'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::UUID, 'uuid'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::UUID_BINARY, 'BYTEA'));
    }

    /**
     * @return string
     */
    public function getNativeIdMethod(): string
    {
        return PlatformInterface::SERIAL;
    }

    /**
     * @return string
     */
    public function getAutoIncrement(): string
    {
        return '';
    }

    /**
     * @return array<int>
     */
    public function getDefaultTypeSizes(): array
    {
        return [
            'char' => 1,
            'character' => 1,
            'integer' => 32,
            'bigint' => 64,
            'smallint' => 16,
            'double precision' => 54,
        ];
    }

    /**
     * @return int
     */
    public function getMaxColumnNameLength(): int
    {
        return 63;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function getBooleanString($value): string
    {
        // parent method does the checking for allows string
        // representations & returns integer
        $value = parent::getBooleanString($value);

        return ($value ? "'t'" : "'f'");
    }

    /**
     * @return bool
     */
    public function supportsNativeDeleteTrigger(): bool
    {
        return true;
    }

    /**
     * Override to provide sequence names that conform to postgres' standard when
     * no id-method-parameter specified.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getSequenceName(Table $table): string
    {
        $result = null;
        if ($table->getIdMethod() == IdMethod::NATIVE) {
            $idMethodParams = $table->getIdMethodParameters();
            if (!$idMethodParams) {
                // We're going to ignore a check for max length (mainly
                // because I'm not sure how Postgres would handle this w/ SERIAL anyway)
                foreach ($table->getColumns() as $col) {
                    if ($col->isAutoIncrement()) {
                        $result = $table->getName() . '_' . $col->getName() . '_seq';

                        break; // there's only one auto-increment column allowed
                    }
                }
            } else {
                $result = $idMethodParams[0]->getValue();
            }
        }

        return $result;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    protected function getAddSequenceDDL(Table $table): string
    {
        if (
            $table->getIdMethod() == IdMethod::NATIVE
            && $table->getIdMethodParameters() != null
        ) {
            $pattern = "
CREATE SEQUENCE %s;
";

            return sprintf(
                $pattern,
                $this->quoteIdentifier(strtolower($this->getSequenceName($table))),
            );
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    protected function getDropSequenceDDL(Table $table): string
    {
        if (
            $table->getIdMethod() == IdMethod::NATIVE
            && $table->getIdMethodParameters() != null
        ) {
            $pattern = "
DROP SEQUENCE %s;
";

            return sprintf(
                $pattern,
                $this->quoteIdentifier(strtolower($this->getSequenceName($table))),
            );
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return string
     */
    public function getAddSchemasDDL(Database $database): string
    {
        $ret = '';
        $schemas = [];
        foreach ($database->getTables() as $table) {
            $vi = $table->getVendorInfoForType('pgsql');
            if ($vi->hasParameter('schema') && !isset($schemas[$vi->getParameter('schema')])) {
                $schemas[$vi->getParameter('schema')] = true;
                $ret .= $this->getAddSchemaDDL($table);
            }
        }

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddSchemaDDL(Table $table): string
    {
        $vi = $table->getVendorInfoForType('pgsql');
        if ($vi->hasParameter('schema')) {
            $pattern = "
CREATE SCHEMA %s;
";

            return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getUseSchemaDDL(Table $table): string
    {
        $vi = $table->getVendorInfoForType('pgsql');
        if ($vi->hasParameter('schema')) {
            $pattern = "
SET search_path TO %s;
";

            return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getResetSchemaDDL(Table $table): string
    {
        $vi = $table->getVendorInfoForType('pgsql');
        if ($vi->hasParameter('schema')) {
            return "
SET search_path TO public;
";
        }

        return '';
    }

    /**
     * @param \Propel\Generator\Model\Database $database
     *
     * @return string
     */
    public function getAddTablesDDL(Database $database): string
    {
        $ret = $this->getAddSchemasDDL($database);

        foreach ($database->getTablesForSql() as $table) {
            $this->normalizeTable($table);
        }

        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getCommentBlockDDL($table->getName());
            $ret .= $this->getDropTableDDL($table);
            $ret .= $this->getAddTableDDL($table);
            $ret .= $this->getAddIndicesDDL($table);
        }
        foreach ($database->getTablesForSql() as $table) {
            $ret .= $this->getAddForeignKeysDDL($table);
        }

        if ($ret) {
            $ret = $this->getBeginDDL() . $ret . $this->getEndDDL();
        }

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return string
     */
    public function getForeignKeyDDL(ForeignKey $fk): string
    {
        $script = parent::getForeignKeyDDL($fk);

        $pgVendorInfo = $fk->getVendorInfoForType('pgsql');
        if (filter_var($pgVendorInfo->getParameter('deferrable'), FILTER_VALIDATE_BOOLEAN)) {
            $script .= ' DEFERRABLE';
            if (filter_var($pgVendorInfo->getParameter('initiallyDeferred'), FILTER_VALIDATE_BOOLEAN)) {
                $script .= ' INITIALLY DEFERRED';
            }
        }

        return $script;
    }

    /**
     * @return string
     */
    public function getBeginDDL(): string
    {
        return "
BEGIN;
";
    }

    /**
     * @return string
     */
    public function getEndDDL(): string
    {
        return "
COMMIT;
";
    }

    /**
     * @inheritDoc
     */
    public function getAddForeignKeysDDL(Table $table): string
    {
        $ret = '';
        foreach ($table->getForeignKeys() as $fk) {
            $ret .= $this->getAddForeignKeyDDL($fk);
        }

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getAddTableDDL(Table $table): string
    {
        $ret = $this->getUseSchemaDDL($table);
        $ret .= $this->getAddSequenceDDL($table);

        $lines = [];

        foreach ($table->getColumns() as $column) {
            $lines[] = $this->getColumnDDL($column);
        }

        if ($table->hasPrimaryKey()) {
            $lines[] = $this->getPrimaryKeyDDL($table);
        }

        foreach ($table->getUnices() as $unique) {
            $lines[] = $this->getUniqueDDL($unique);
        }

        $sep = ",
    ";
        $pattern = "
CREATE TABLE %s
(
    %s
);
";
        $ret .= sprintf(
            $pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines),
        );

        if ($table->hasDescription()) {
            $pattern = "
COMMENT ON TABLE %s IS %s;
";
            $ret .= sprintf(
                $pattern,
                $this->quoteIdentifier($table->getName()),
                $this->quote($table->getDescription()),
            );
        }

        $ret .= $this->getAddColumnsComments($table);
        $ret .= $this->getResetSchemaDDL($table);

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    protected function getAddColumnsComments(Table $table): string
    {
        $ret = '';
        foreach ($table->getColumns() as $column) {
            $ret .= $this->getAddColumnComment($column);
        }

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    protected function getAddColumnComment(Column $column): string
    {
        $pattern = "
COMMENT ON COLUMN %s.%s IS %s;
";
        if ($column->getDescription()) {
            return sprintf(
                $pattern,
                $this->quoteIdentifier($column->getTable()->getName()),
                $this->quoteIdentifier($column->getName()),
                $this->quote($column->getDescription()),
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
        $ret = $this->getUseSchemaDDL($table);
        $pattern = "
DROP TABLE IF EXISTS %s CASCADE;
";
        $ret .= sprintf($pattern, $this->quoteIdentifier($table->getName()));
        $ret .= $this->getDropSequenceDDL($table);
        $ret .= $this->getResetSchemaDDL($table);

        return $ret;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     *
     * @return string
     */
    public function getPrimaryKeyName(Table $table): string
    {
        $tableName = $table->getCommonName();

        return $tableName . '_pkey';
    }

    /**
     * @param \Propel\Generator\Model\Column $col
     *
     * @return string
     */
    public function getColumnDDL(Column $col): string
    {
        $domain = $col->getDomain();

        $ddl = [$this->quoteIdentifier($col->getName())];
        $sqlType = $domain->getSqlType();
        $table = $col->getTable();
        if ($col->isAutoIncrement() && $table && $table->getIdMethodParameters() == null) {
            $sqlType = $col->getType() === PropelTypes::BIGINT ? 'bigserial' : 'serial';
        }
        if ($this->hasSize($sqlType) && $col->isDefaultSqlType($this)) {
            if ($this->isNumber($sqlType)) {
                if (strtoupper($sqlType) === 'NUMERIC') {
                    $ddl[] = $sqlType . $col->getSizeDefinition();
                } else {
                    $ddl[] = $sqlType;
                }
            } else {
                $ddl[] = $sqlType . $col->getSizeDefinition();
            }
        } else {
            $ddl[] = $sqlType;
        }

        if (
            $col->getDefaultValue()
            && $col->getDefaultValue()->isExpression()
            && $col->getDefaultValue()->getValue() === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ) {
            $col->setDefaultValue(
                new ColumnDefaultValue('CURRENT_TIMESTAMP', ColumnDefaultValue::TYPE_EXPR),
            );
        }

        $default = $this->getColumnDefaultValueDDL($col);
        if ($default) {
            $ddl[] = $default;
        }

        $notNull = $this->getNullString($col->isNotNull());
        if ($notNull) {
            $ddl[] = $notNull;
        }

        $autoIncrement = $col->getAutoIncrementString();
        if ($autoIncrement) {
            $ddl[] = $autoIncrement;
        }

        return implode(' ', $ddl);
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
     * @param string $fromTableName
     * @param string $toTableName
     *
     * @return string
     */
    public function getRenameTableDDL(string $fromTableName, string $toTableName): string
    {
        $pos = strpos($toTableName, '.');
        if ($pos !== false) {
            $toTableName = substr($toTableName, $pos + 1);
        }

        $pattern = "
ALTER TABLE %s RENAME TO %s;
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($fromTableName),
            $this->quoteIdentifier($toTableName),
        );
    }

    /**
     * @see Platform::supportsSchemas()
     *
     * @return bool
     */
    public function supportsSchemas(): bool
    {
        return true;
    }

    /**
     * @param string $sqlType
     *
     * @return bool
     */
    public function hasSize(string $sqlType): bool
    {
        return !in_array(strtoupper($sqlType), ['BYTEA', 'TEXT', 'DOUBLE PRECISION'], true);
    }

    /**
     * @return bool
     */
    public function hasStreamBlobImpl(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportsVarcharWithoutSize(): bool
    {
        return true;
    }

    /**
     * @param \Propel\Generator\Model\Diff\TableDiff $tableDiff
     *
     * @return string
     */
    public function getModifyTableDDL(TableDiff $tableDiff): string
    {
        $ret = parent::getModifyTableDDL($tableDiff);

        if ($this->createOrDropSequences) {
            $ret = $this->createOrDropSequences . $ret;
        }

        $this->createOrDropSequences = '';

        return $ret;
    }

    /**
     * Overrides the implementation from DefaultPlatform
     *
     * @author Niklas Närhinen <niklas@narhinen.net>
     *
     * @see DefaultPlatform::getModifyColumnDDL
     *
     * @param \Propel\Generator\Model\Diff\ColumnDiff $columnDiff
     *
     * @return string
     */
    public function getModifyColumnDDL(ColumnDiff $columnDiff): string
    {
        $ret = '';
        $changedProperties = $columnDiff->getChangedProperties();

        $fromColumn = $columnDiff->getFromColumn();
        $toColumn = clone $columnDiff->getToColumn();

        $fromTable = $fromColumn->getTable();
        $table = $toColumn->getTable();

        $colName = $this->quoteIdentifier($toColumn->getName());

        $pattern = "
ALTER TABLE %s ALTER COLUMN %s;
";

        if ($table && isset($changedProperties['autoIncrement'])) {
            $tableName = $table->getName();
            $colPlainName = $toColumn->getName();
            $seqName = "{$tableName}_{$colPlainName}_seq";

            if ($toColumn->isAutoIncrement() && $table->getIdMethodParameters() == null) {
                $defaultValue = "nextval('$seqName'::regclass)";
                $toColumn->setDefaultValue($defaultValue);
                $changedProperties['defaultValueValue'] = [null, $defaultValue];

                //add sequence
                if (!$fromTable->getDatabase()->hasSequence($seqName)) {
                    $this->createOrDropSequences .= sprintf(
                        "
CREATE SEQUENCE %s;
",
                        $seqName,
                    );
                    $fromTable->getDatabase()->addSequence($seqName);
                }
            }

            if (!$toColumn->isAutoIncrement() && $fromColumn->isAutoIncrement()) {
                //remove sequence
                if ($fromTable->getDatabase()->hasSequence($seqName)) {
                    $this->createOrDropSequences .= sprintf(
                        "
DROP SEQUENCE %s CASCADE;
",
                        $seqName,
                    );
                    $fromTable->getDatabase()->removeSequence($seqName);
                }
            }
        }

        if (isset($changedProperties['size']) || isset($changedProperties['type']) || isset($changedProperties['sqlType']) || isset($changedProperties['scale'])) {
            $sqlType = $toColumn->getDomain()->getSqlType();

            if ($this->hasSize($sqlType) && $toColumn->isDefaultSqlType($this)) {
                if ($this->isNumber($sqlType)) {
                    if (strtoupper($sqlType) === 'NUMERIC') {
                        $sqlType .= $toColumn->getSizeDefinition();
                    }
                } else {
                    $sqlType .= $toColumn->getSizeDefinition();
                }
            }

            $using = $this->getUsingCast($fromColumn, $toColumn);
            if ($using) {
                $sqlType .= $using;
            }

            $ret .= sprintf(
                $pattern,
                $this->quoteIdentifier($table->getName()),
                $colName . ' TYPE ' . $sqlType,
            );
        }

        if (isset($changedProperties['defaultValueValue'])) {
            $property = $changedProperties['defaultValueValue'];
            if ($property[0] !== null && $property[1] === null) {
                $ret .= sprintf($pattern, $this->quoteIdentifier($table->getName()), $colName . ' DROP DEFAULT');
            } else {
                $ret .= sprintf($pattern, $this->quoteIdentifier($table->getName()), $colName . ' SET ' . $this->getColumnDefaultValueDDL($toColumn));
            }
        }

        if (isset($changedProperties['notNull'])) {
            $property = $changedProperties['notNull'];
            $notNull = ' DROP NOT NULL';
            if ($property[1]) {
                $notNull = ' SET NOT NULL';
            }
            $ret .= sprintf($pattern, $this->quoteIdentifier($table->getName()), $colName . $notNull);
        }

        return $ret;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isUuid(string $type): bool
    {
        $strings = ['UUID'];

        return in_array(strtoupper($type), $strings, true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isString(string $type): bool
    {
        $strings = ['VARCHAR'];

        return in_array(strtoupper($type), $strings, true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isNumber(string $type): bool
    {
        $numbers = ['INTEGER', 'INT4', 'INT2', 'NUMBER', 'NUMERIC', 'SMALLINT', 'BIGINT', 'DECIMAL', 'REAL', 'DOUBLE PRECISION', 'SERIAL', 'BIGSERIAL'];

        return in_array(strtoupper($type), $numbers, true);
    }

    /**
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return string
     */
    public function getUsingCast(Column $fromColumn, Column $toColumn): string
    {
        $fromSqlType = strtoupper($fromColumn->getDomain()->getSqlType());
        $toSqlType = strtoupper($toColumn->getDomain()->getSqlType());
        $name = $fromColumn->getName();

        if ($this->isString($fromSqlType) && $this->isNumber($toSqlType)) {
            //cast from string to int
            return "
   USING CASE WHEN trim($name) SIMILAR TO '[0-9]+'
        THEN CAST(trim($name) AS integer)
        ELSE NULL END";
        }

        if ($this->isNumber($fromSqlType) && $toSqlType === 'BYTEA') {
            return " USING decode(CAST($name as text), 'escape')";
        }

        if (
            ($this->isNumber($fromSqlType) && $this->isNumber($toSqlType)) ||
            ($this->isString($fromSqlType) && $this->isString($toSqlType)) ||
            ($this->isNumber($fromSqlType) && $this->isString($toSqlType)) ||
            ($this->isUuid($fromSqlType) && $this->isString($toSqlType))
        ) {
            // no cast necessary
            return '';
        }

        if ($this->isString($fromSqlType) && $this->isUuid($toSqlType)) {
            return " USING $name::uuid";
        }

        return ' USING NULL';
    }

    /**
     * Overrides the implementation from DefaultPlatform
     *
     * @author Niklas Närhinen <niklas@narhinen.net>
     *
     * @see DefaultPlatform::getModifyColumnsDDL
     *
     * @param array<\Propel\Generator\Model\Diff\ColumnDiff> $columnDiffs
     *
     * @return string
     */
    public function getModifyColumnsDDL(array $columnDiffs): string
    {
        $ret = '';
        foreach ($columnDiffs as $columnDiff) {
            $ret .= $this->getModifyColumnDDL($columnDiff);
        }

        return $ret;
    }

    /**
     * Overrides the implementation from DefaultPlatform
     *
     * @author Niklas Närhinen <niklas@narhinen.net>
     *
     * @see DefaultPlatform::getAddColumnsDLL
     *
     * @param array<\Propel\Generator\Model\Column> $columns
     *
     * @return string
     */
    public function getAddColumnsDDL(array $columns): string
    {
        $ret = '';
        foreach ($columns as $column) {
            $ret .= $this->getAddColumnDDL($column);
        }

        return $ret;
    }

    /**
     * Overrides the implementation from DefaultPlatform
     *
     * @author Niklas Närhinen <niklas@narhinen.net>
     *
     * @see DefaultPlatform::getDropIndexDDL
     *
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    public function getDropIndexDDL(Index $index): string
    {
        if ($index instanceof Unique) {
            $pattern = "
ALTER TABLE %s DROP CONSTRAINT %s;
";

            return sprintf(
                $pattern,
                $this->quoteIdentifier($index->getTable()->getName()),
                $this->quoteIdentifier($index->getName()),
            );
        }

        return parent::getDropIndexDDL($index);
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from PgsqlAdapter::getId().
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
            throw new EngineException('PostgreSQL needs a sequence name to fetch primary keys');
        }
        $snippet = "
\$dataFetcher = %s->query(\"SELECT nextval('%s')\");
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

    /**
     * @param \Propel\Generator\Model\Index $index
     *
     * @return string
     */
    public function getAddIndexDDL(Index $index): string
    {
        if (!$index->isUnique()) {
            return parent::getAddIndexDDL($index);
        }

        $pattern = "
ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (%s);
";

        return sprintf(
            $pattern,
            $this->quoteIdentifier($index->getTable()->getName()),
            $this->quoteIdentifier($index->getName()),
            $this->getColumnListDDL($index->getColumnObjects()),
        );
    }
}
