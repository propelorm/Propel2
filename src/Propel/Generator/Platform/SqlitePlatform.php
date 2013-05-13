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
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;

/**
 * SQLite PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SqlitePlatform extends DefaultPlatform
{

    /**
     * Initializes db specific domain mapping.
     */
    protected function initialize()
    {
        parent::initialize();

        $this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, 'DECIMAL'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, 'DATETIME'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, 'BLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, 'MEDIUMBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, 'LONGBLOB'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, 'LONGTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::PHP_ARRAY, 'MEDIUMTEXT'));
        $this->setSchemaDomainMapping(new Domain(PropelTypes::ENUM, 'TINYINT'));
    }

    /**
     * @link       http://www.sqlite.org/autoinc.html
     */
    public function getAutoIncrement()
    {
        return 'PRIMARY KEY';
    }

    public function getMaxColumnNameLength()
    {
        return 1024;
    }

    public function getAddTableDDL(Table $table)
    {
        $tableDescription = $table->hasDescription() ? $this->getCommentLineDDL($table->getDescription()) : '';

        $lines = array();

        foreach ($table->getColumns() as $column) {
            $lines[] = $this->getColumnDDL($column);
        }

        if (null !== $primaryKeyDDL = $this->getPrimaryKeyDDL($table)) {
            $lines[] = $primaryKeyDDL;
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
);
";

        return sprintf($pattern,
            $tableDescription,
            $this->quoteIdentifier($table->getName()),
            implode($sep, $lines)
        );
    }

    /**
     * Returns the SQL for the primary key of a Table object.
     *
     * @return string
     */
    public function getPrimaryKeyDDL(Table $table)
    {
        if ($table->hasPrimaryKey() && 1 < count($table->getPrimaryKey())) {
            if ($table->hasAutoIncrementPrimaryKey()) {
                return 'UNIQUE (' . $this->getColumnListDDL($table->getPrimaryKey()) . ')';
            }

            return 'PRIMARY KEY (' . $this->getColumnListDDL($table->getPrimaryKey()) . ')';
        }
    }

    public function getDropPrimaryKeyDDL(Table $table)
    {
        // FIXME: not supported by SQLite
        return '';
    }

    public function getAddPrimaryKeyDDL(Table $table)
    {
        // FIXME: not supported by SQLite
        return '';
    }

    public function getAddForeignKeyDDL(ForeignKey $fk)
    {
        // no need for an alter table to return comments
        return $this->getForeignKeyDDL($fk);
    }

    public function getDropForeignKeyDDL(ForeignKey $fk)
    {
        return '';
    }

    public function getForeignKeyDDL(ForeignKey $fk)
    {
        $pattern = "
-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY (%s) REFERENCES %s (%s)
";

        return sprintf($pattern,
            $this->getColumnListDDL($fk->getLocalColumns()),
            $fk->getForeignTableName(),
            $this->getColumnListDDL($fk->getForeignColumns())
        );
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

    public function quoteIdentifier($text)
    {
        return $this->isIdentifierQuotingEnabled ? '[' . $text . ']' : $text;
    }

    /**
     * @see Platform::supportsMigrations()
     */
    public function supportsMigrations()
    {
        return false;
    }

    public function getDropTableDDL(Table $table)
    {
        return "
DROP TABLE IF EXISTS " . $table->getName() . ";
";
    }
}
