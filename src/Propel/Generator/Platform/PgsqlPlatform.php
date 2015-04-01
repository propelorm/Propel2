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
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\ColumnDiff;

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
     */
    protected function initialize()
    {
        parent::initialize();
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
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, 'NUMERIC'));
    }

    public function getNativeIdMethod()
    {
        return PlatformInterface::SERIAL;
    }

    public function getAutoIncrement()
    {
        return '';
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

    public function getMaxColumnNameLength()
    {
        return 63;
    }

    public function getBooleanString($b)
    {
        // parent method does the checking for allows string
        // representations & returns integer
        $b = parent::getBooleanString($b);

        return ($b ? "'t'" : "'f'");
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

    /**
     * Override to provide sequence names that conform to postgres' standard when
     * no id-method-parameter specified.
     *
     * @param Table $table
     *
     * @return string
     */
    public function getSequenceName(Table $table)
    {
        $result = null;
        if ($table->getIdMethod() == IdMethod::NATIVE) {
            $idMethodParams = $table->getIdMethodParameters();
            if (empty($idMethodParams)) {
                $result = null;
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

    protected function getAddSequenceDDL(Table $table)
    {
        if ($table->getIdMethod() == IdMethod::NATIVE
         && $table->getIdMethodParameters() != null) {
            $pattern = "
CREATE SEQUENCE %s;
";

            return sprintf($pattern,
                $this->quoteIdentifier(strtolower($this->getSequenceName($table)))
            );
        }
    }

    protected function getDropSequenceDDL(Table $table)
    {
        if ($table->getIdMethod() == IdMethod::NATIVE
         && $table->getIdMethodParameters() != null) {
            $pattern = "
DROP SEQUENCE %s;
";

            return sprintf($pattern,
                $this->quoteIdentifier(strtolower($this->getSequenceName($table)))
            );
        }
    }

    public function getAddSchemasDDL(Database $database)
    {
        $ret = '';
        $schemas = array();
        foreach ($database->getTables() as $table) {
            $vi = $table->getVendorInfoForType('pgsql');
            if ($vi->hasParameter('schema') && !isset($schemas[$vi->getParameter('schema')])) {
                $schemas[$vi->getParameter('schema')] = true;
                $ret .= $this->getAddSchemaDDL($table);
            }
        }

        return $ret;
    }

    public function getAddSchemaDDL(Table $table)
    {
        $vi = $table->getVendorInfoForType('pgsql');
        if ($vi->hasParameter('schema')) {
            $pattern = "
CREATE SCHEMA %s;
";

            return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
        };
    }

    public function getUseSchemaDDL(Table $table)
    {
        $vi = $table->getVendorInfoForType('pgsql');
        if ($vi->hasParameter('schema')) {
            $pattern = "
SET search_path TO %s;
";

            return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
        }
    }

    public function getResetSchemaDDL(Table $table)
    {
        $vi = $table->getVendorInfoForType('pgsql');
        if ($vi->hasParameter('schema')) {
            return "
SET search_path TO public;
";
        }
    }

    public function getAddTablesDDL(Database $database)
    {
        $ret = $this->getBeginDDL();
        $ret .= $this->getAddSchemasDDL($database);

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
        $ret .= $this->getEndDDL();

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function getAddForeignKeysDDL(Table $table)
    {
        $ret = '';
        foreach ($table->getForeignKeys() as $fk) {
            $ret .= $this->getAddForeignKeyDDL($fk);
        }

        return $ret;
    }

    public function getAddTableDDL(Table $table)
    {
        $ret = '';
        $ret .= $this->getUseSchemaDDL($table);
        $ret .= $this->getAddSequenceDDL($table);

        $lines = array();

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
        $ret .= sprintf($pattern,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );

        if ($table->hasDescription()) {
            $pattern = "
COMMENT ON TABLE %s IS %s;
";
            $ret .= sprintf($pattern,
                $this->quoteIdentifier($table->getName()),
                $this->quote($table->getDescription())
            );
        }

        $ret .= $this->getAddColumnsComments($table);
        $ret .= $this->getResetSchemaDDL($table);

        return $ret;
    }

    protected function getAddColumnsComments(Table $table)
    {
        $ret = '';
        foreach ($table->getColumns() as $column) {
            $ret .= $this->getAddColumnComment($column);
        }

        return $ret;
    }

    protected function getAddColumnComment(Column $column)
    {
        $pattern = "
COMMENT ON COLUMN %s.%s IS %s;
";
        if ($description = $column->getDescription()) {
            return sprintf($pattern,
                $this->quoteIdentifier($column->getTable()->getName()),
                $this->quoteIdentifier($column->getName()),
                $this->quote($description)
            );
        }
    }

    public function getDropTableDDL(Table $table)
    {
        $ret = '';
        $ret .= $this->getUseSchemaDDL($table);
        $pattern = "
DROP TABLE IF EXISTS %s CASCADE;
";
        $ret .= sprintf($pattern, $this->quoteIdentifier($table->getName()));
        $ret .= $this->getDropSequenceDDL($table);
        $ret .= $this->getResetSchemaDDL($table);

        return $ret;
    }

    public function getPrimaryKeyName(Table $table)
    {
        $tableName = $table->getCommonName();

        return $tableName . '_pkey';
    }

    public function getColumnDDL(Column $col)
    {
        $domain = $col->getDomain();

        $ddl = array($this->quoteIdentifier($col->getName()));
        $sqlType = $domain->getSqlType();
        $table = $col->getTable();
        if ($col->isAutoIncrement() && $table && $table->getIdMethodParameters() == null) {
            $sqlType = $col->getType() === PropelTypes::BIGINT ? 'bigserial' : 'serial';
        }
        if ($this->hasSize($sqlType) && $col->isDefaultSqlType($this)) {
            if ($this->isNumber($sqlType)) {
                if ('NUMERIC' === strtoupper($sqlType)) {
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
        if ($default = $this->getColumnDefaultValueDDL($col)) {
            $ddl[] = $default;
        }
        if ($notNull = $this->getNullString($col->isNotNull())) {
            $ddl[] = $notNull;
        }
        if ($autoIncrement = $col->getAutoIncrementString()) {
            $ddl[] = $autoIncrement;
        }

        return implode(' ', $ddl);
    }

    public function getUniqueDDL(Unique $unique)
    {
        return sprintf('CONSTRAINT %s UNIQUE (%s)',
            $this->quoteIdentifier($unique->getName()),
            $this->getColumnListDDL($unique->getColumnObjects())
        );
    }

    public function getRenameTableDDL($fromTableName, $toTableName)
    {
        if (false !== ($pos = strpos($toTableName, '.'))) {
            $toTableName = substr($toTableName, $pos + 1);
        }

        $pattern = "
ALTER TABLE %s RENAME TO %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($fromTableName),
            $this->quoteIdentifier($toTableName)
        );
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
        return !in_array($sqlType, array('BYTEA', 'TEXT', 'DOUBLE PRECISION'));
    }

    public function hasStreamBlobImpl()
    {
        return true;
    }

    public function supportsVarcharWithoutSize()
    {
        return true;
    }

    public function getModifyTableDDL(TableDiff $tableDiff)
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
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getModifyColumnDDL
     */
    public function getModifyColumnDDL(ColumnDiff $columnDiff)
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

        if (isset($changedProperties['autoIncrement'])) {
            $tableName = $table->getName();
            $colPlainName = $toColumn->getName();
            $seqName = "{$tableName}_{$colPlainName}_seq";

            if ($toColumn->isAutoIncrement() && $table && $table->getIdMethodParameters() == null) {

                $defaultValue = "nextval('$seqName'::regclass)";
                $toColumn->setDefaultValue($defaultValue);
                $changedProperties['defaultValueValue'] = [null, $defaultValue];

                //add sequence
                if (!$fromTable->getDatabase()->hasSequence($seqName)) {
                    $this->createOrDropSequences .= sprintf("
CREATE SEQUENCE %s;
",
                        $seqName
                    );
                    $fromTable->getDatabase()->addSequence($seqName);
                }
            }

            if (!$toColumn->isAutoIncrement() && $fromColumn->isAutoIncrement()) {
                $changedProperties['defaultValueValue'] = [$fromColumn->getDefaultValueString(), null];
                $toColumn->setDefaultValue(null);

                //remove sequence
                if ($fromTable->getDatabase()->hasSequence($seqName)) {
                    $this->createOrDropSequences .= sprintf("
DROP SEQUENCE %s CASCADE;
",
                        $seqName
                    );
                    $fromTable->getDatabase()->removeSequence($seqName);
                }
            }
        }

        if (isset($changedProperties['size']) || isset($changedProperties['type']) || isset($changedProperties['scale'])) {

            $sqlType = $toColumn->getDomain()->getSqlType();

            if ($this->hasSize($sqlType) && $toColumn->isDefaultSqlType($this)) {
                if ($this->isNumber($sqlType)) {
                    if ('NUMERIC' === strtoupper($sqlType)) {
                        $sqlType .= $toColumn->getSizeDefinition();
                    }
                } else {
                    $sqlType .= $toColumn->getSizeDefinition();
                }
            }

            if ($using = $this->getUsingCast($fromColumn, $toColumn)) {
                $sqlType .= $using;
            }
            $ret .= sprintf($pattern,
                $this->quoteIdentifier($table->getName()),
                $colName . ' TYPE ' . $sqlType
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

    public function isString($type)
    {
        $strings = ['VARCHAR'];

        return in_array(strtoupper($type), $strings);
    }

    public function isNumber($type)
    {
        $numbers = ['INTEGER', 'INT4', 'INT2', 'NUMBER', 'NUMERIC', 'SMALLINT', 'BIGINT', 'DECICAML', 'REAL', 'DOUBLE PRECISION', 'SERIAL', 'BIGSERIAL'];

        return in_array(strtoupper($type), $numbers);
    }

    public function getUsingCast(Column $fromColumn, Column $toColumn)
    {
        $fromSqlType = strtoupper($fromColumn->getDomain()->getSqlType());
        $toSqlType = strtoupper($toColumn->getDomain()->getSqlType());
        $name = $fromColumn->getName();

        if ($this->isNumber($fromSqlType) && $this->isString($toSqlType)) {
            //cast from int to string
            return '  ';
        }
        if ($this->isString($fromSqlType) && $this->isNumber($toSqlType)) {
            //cast from string to int
            return "
   USING CASE WHEN trim($name) SIMILAR TO '[0-9]+'
        THEN CAST(trim($name) AS integer)
        ELSE NULL END";
        }

        if ($this->isNumber($fromSqlType) && 'BYTEA' === $toSqlType) {
            return " USING decode(CAST($name as text), 'escape')";
        }

        if ('DATE' === $fromSqlType && 'TIME' === $toSqlType) {
            return " USING NULL";
        }

        if ($this->isNumber($fromSqlType) && $this->isNumber($toSqlType)) {
            return '';
        }

        if ($this->isString($fromSqlType) && $this->isString($toSqlType)) {
            return '';
        }

        return " USING NULL";
    }

    /**
     * Overrides the implementation from DefaultPlatform
     *
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getModifyColumnsDDL
     */
    public function getModifyColumnsDDL($columnDiffs)
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
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getAddColumnsDLL
     */
    public function getAddColumnsDDL($columns)
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
     * @author     Niklas Närhinen <niklas@narhinen.net>
     * @return string
     * @see DefaultPlatform::getDropIndexDDL
     */
    public function getDropIndexDDL(Index $index)
    {
        if ($index instanceof Unique) {
            $pattern = "
    ALTER TABLE %s DROP CONSTRAINT %s;
    ";

            return sprintf($pattern,
                $this->quoteIdentifier($index->getTable()->getName()),
                $this->quoteIdentifier($index->getName())
            );
        } else {
            return parent::getDropIndexDDL($index);
        }
    }

    /**
     * Get the PHP snippet for getting a Pk from the database.
     * Warning: duplicates logic from PgsqlAdapter::getId().
     * Any code modification here must be ported there.
     */
    public function getIdentifierPhp($columnValueMutator, $connectionVariableName = '$con', $sequenceName = '', $tab = "            ")
    {
        if (!$sequenceName) {
            throw new EngineException('PostgreSQL needs a sequence name to fetch primary keys');
        }
        $snippet = "
\$dataFetcher = %s->query(\"SELECT nextval('%s')\");
%s = \$dataFetcher->fetchColumn();";
        $script = sprintf($snippet,
            $connectionVariableName,
            $sequenceName,
            $columnValueMutator
        );

        return preg_replace('/^/m', $tab, $script);
    }

}
