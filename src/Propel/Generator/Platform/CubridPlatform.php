<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * MySql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class CubridPlatform extends DefaultPlatform
{

    /**
     * Default constructor.
     *
     * @param ConnectionInterface $con Optional database connection to use in this platform.
     */
    public function __construct(ConnectionInterface $con = null)
    {
        $this->isIdentifierQuotingEnabled = true;
        parent::__construct($con);
    }

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, 'SMALLINT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'NUMERIC'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'STRING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BIT VARYING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'BIT VARYING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'BIT VARYING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'STRING'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'STRING'));
    }

    /**
     * The identifier quoting must always be enabled
     */
    public function setIdentifierQuoting($enabled = true)
    {
        $this->isIdentifierQuotingEnabled = true;
    }

    /**
     * Quotes identifiers used in database SQL.
     * @param  string $text
     * @return string Quoted identifier.
     */
    public function quoteIdentifier($text)
    {
        return $this->isIdentifierQuotingEnabled ? '`' . strtr($text, array('.' => '`.`')) . '`' : $text;
    }

    public function getAutoIncrement()
    {
        return 'AUTO_INCREMENT';
    }

    public function getMaxColumnNameLength()
    {
        // according to http://www.cubrid.org/wiki_tutorials/entry/cubrid-rdbms-size-limits
        return 254;
    }

    public function supportsNativeDeleteTrigger()
    {
        return true;
    }

    /**
     * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
     *
     * @return boolean
    */
    public function hasStreamBlobImpl()
    {
        return true;
    }

    /**
    * Returns the DDL SQL to add the tables of a database
    * together with index and foreign keys
    *
    * @return string
    */
    public function getAddTablesDDL(Database $database)
    {
        $fks = '';
        $ret = $this->getBeginDDL();
        $definedTables = [];

        foreach ($database->getTablesForSql() as $table) {
            $definedTables[] = $table->getName();

            $ret .= $this->getCommentBlockDDL($table->getName());

            // before dropping the table, drop all its child tables
            // but only if child tables haven't been defined here.
            foreach ($table->getReferrers() as $fk) {
                if (!in_array($fk->getTable()->getName(), $definedTables)) {
                    $ret .= $this->getDropTableDDL($fk->getTable());
                }
            }

            $ret .= $this->getDropTableDDL($table);
            $ret .= $this->getAddTableDDL($table);
            $ret .= $this->getAddIndicesDDL($table);

            $fks .= $this->getCommentBlockDDL($table->getName() . ' Foreign Key Definition');
            $fks .= $this->getAddForeignKeysDDL($table);
        }

        // add foreign key definition at the very end to avoid
        // "The class 'table_name' referred by the foreign key does not exist" error
        // since in CUBRID there is no way to turn off foreign keys which is a bad practice anyway
        $ret .= $fks;
        $ret .= $this->getEndDDL();

        //echo $ret;
        return $ret;
    }

    public function getDropTableDDL(Table $table)
    {
        return "\nDROP TABLE IF EXISTS " . $this->quoteIdentifier($table->getName()) . ";\n";
    }

    /**
     * Builds the DDL SQL to rename a table
     * @return string
     */
    public function getRenameTableDDL($fromTableName, $toTableName)
    {
        $pattern = "RENAME TABLE %s TO %s;";

        return sprintf($pattern,
            $this->quoteIdentifier($fromTableName),
            $this->quoteIdentifier($toTableName)
        );
    }

    /**
      * Returns if the RDBMS-specific SQL type has a size attribute.
    * The list presented below do have size attribute
      *
      * @param  string  $sqlType the SQL type
      * @return boolean True if the type has a size attribute
      */
    public function hasSize($sqlType)
    {
        return in_array($sqlType, array(
            'NUMERIC',
            'DECIMAL',
            'FLOAT',
            'REAL',
            'BIT',
            'BIT VARYING',
            'CHAR',
            'VARCHAR'
        ));
    }

    /**
    * Returns the DDL SQL to drop the primary key of a table.
    *
    * @param  Table  $table
    * @return string
    */
    public function getDropPrimaryKeyDDL(Table $table)
    {
        $pattern = "
ALTER TABLE %s DROP PRIMARY KEY;
";

        return sprintf($pattern,
            $this->quoteIdentifier($table->getName()),
            $this->quoteIdentifier($this->getPrimaryKeyName($table))
        );
    }

    /**
     * Creates a comma-separated list of column names for the index.
     * For Cubrid indexes there is the option of specifying size, so we cannot simply use
     * the getColumnsList() method.
     * @param  Index  $index
     * @return string
     */
    protected function getIndexColumnListDDL(Index $index)
    {
        $list = array();
        foreach ($index->getColumns() as $col) {
            $list[] = $this->quoteIdentifier($col) . ($index->hasColumnSize($col) ? '(' . $index->getColumnSize($col) . ')' : '');
        }

        return implode(', ', $list);
    }

    /**
     * Builds the DDL SQL for an Index object.
     * @return string
     */
    public function getIndexDDL(Index $index)
    {
        return sprintf('%sINDEX %s ON %s(%s)',
            $index->isUnique() ? 'UNIQUE ' : '',
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTableName()),
            $this->getIndexColumnListDDL($index)
        );
    }

    /**
     * Builds the DDL SQL to modify a column
     *
     * @return string
     */
    public function getModifyColumnDDL(ColumnDiff $columnDiff)
    {
        $toColumn = $columnDiff->getToColumn();
        $fromColumn = $columnDiff->getFromColumn();

        $pattern = "
ALTER TABLE %s CHANGE %s %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($toColumn->getTable()->getName()),
            $this->quoteIdentifier($fromColumn->getName()),
            $this->getColumnDDL($toColumn)
        );
    }

    /**
     * Builds the DDL SQL to drop a foreign key.
     *
     * @param  ForeignKey $fk
     * @return string
     */
    public function getDropForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk->isSkipSql()) {
            return;
        }
        $pattern = "
ALTER TABLE %s DROP FOREIGN KEY %s;
";

        return sprintf($pattern,
            $this->quoteIdentifier($fk->getTable()->getName()),
            $this->quoteIdentifier($fk->getName())
        );
    }

    /**
     * Builds the DDL SQL to modify a list of columns
     *
     * @return string
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
            $this->quoteIdentifier($index->getName()),
            $this->quoteIdentifier($index->getTableName())
        );
    }

    /**
     * Builds the DDL SQL for a ForeignKey object.
     * Note: Cubrid supports CASCADE only for delete statement
     * @return string
     */
    public function getForeignKeyDDL(ForeignKey $fk)
    {
        if ($fk::CASCADE == $fk->getOnUpdate()) {
            $fk->setOnUpdate($fk::NONE);
        }

        return parent::getForeignKeyDDL($fk);
    }

    /**
     * @see PlatformInterface::bindValue
     *
     * @param $column
     * @param $identifier
     * @param $columnValueAccessor
     * @param  string       $tab
     * @return mixed|string
     */
    public function getColumnBindingPHP($column, $identifier, $columnValueAccessor, $tab = "            ")
    {
        if ($column->getPDOType() === \PDO::PARAM_BOOL) {
            return sprintf(
                "
%s\$stmt->bindValue(%s, (int) %s, PDO::PARAM_INT);",
                $tab,
                $identifier,
                $columnValueAccessor
            );
        }

        return parent::getColumnBindingPHP($column, $identifier, $columnValueAccessor, $tab);
    }
}
