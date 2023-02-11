<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Platform\Util;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Index;
use Propel\Generator\Platform\MysqlPlatform;

/**
 * Creates migration statements for UUID columns in MySQL.
 *
 * @phpstan-consistent-constructor
 */
class MysqlUuidMigrationBuilder
{
    protected MysqlPlatform $platform;

    /**
     * @param \Propel\Generator\Platform\MysqlPlatform $platform
     */
    public function __construct(MysqlPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @param \Propel\Generator\Platform\MysqlPlatform $platform
     *
     * @return static
     */
    public static function create(MysqlPlatform $platform)
    {
        return new static($platform);
    }

    /**
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     * @param bool $toUuidBinary
     *
     * @return string
     */
    public function buildMigration(Column $fromColumn, Column $toColumn, bool $toUuidBinary): string
    {
        $sqlBlock = [AlterTableStatementMerger::NO_MERGE_ACROSS_THIS_LINE];
        $sqlBlock[] = $this->getMigrateUuidNotification($toColumn);
        $sqlBlock[] = AlterTableStatementMerger::NO_MERGE_ACROSS_THIS_LINE;

        // remove constraints and indexes
        $indexes = $this->getSharedIndexes($fromColumn, $toColumn);
        foreach ($indexes as $index) {
            $sqlBlock[] = $this->platform->getDropIndexDDL($index);
        }
        $movePrimaryKey = $fromColumn->isPrimaryKey() && $toColumn->isPrimaryKey();
        if ($movePrimaryKey) {
            $sqlBlock[] = $this->platform->getDropPrimaryKeyDDL($fromColumn->getTable());
        }

        $sqlBlock[] = $this->buildUuidMigrationStatements($toColumn, $toUuidBinary);

        // restore column constraints
        if ($movePrimaryKey) {
            $sqlBlock[] = $this->platform->getAddPrimaryKeyDDL($toColumn->getTable());
        }
        foreach ($indexes as $index) {
            $sqlBlock[] = $this->platform->getAddIndexDDL($index);
        }

        $sqlBlock[] = "# END migration of UUIDs in column '{$fromColumn->getName()}'\n";
        $sqlBlock[] = AlterTableStatementMerger::NO_MERGE_ACROSS_THIS_LINE;

        return implode('', $sqlBlock);
    }

    /**
     * Get foreign keys in both given columns.
     *
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return array
     */
    protected function getSharedFks(Column $fromColumn, Column $toColumn): array
    {
        $sharedKeys = [];

        foreach (['getReferrers', 'getForeignKeys'] as $getFk) {
            $oldFks = $fromColumn->$getFk();
            $newFks = $toColumn->$getFk();

            foreach ($oldFks as $oldFk) {
                foreach ($newFks as $newFk) {
                    if ($oldFk->equals($newFk)) {
                        continue;
                    }
                    $sharedKeys[] = $oldFk;
                }
            }
        }

        return $sharedKeys;
    }

    /**
     * Get indexes in both given columns.
     *
     * @param \Propel\Generator\Model\Column $fromColumn
     * @param \Propel\Generator\Model\Column $toColumn
     *
     * @return array
     */
    protected function getSharedIndexes(Column $fromColumn, Column $toColumn): array
    {
        $toIndexes = $toColumn->getTable()->getIndexesOnColumn($toColumn);
        $fromTable = $fromColumn->getTable();

        return array_filter($toIndexes, fn (Index $index) => $fromTable->hasIndex($index->getName()));
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    protected function getMigrateUuidNotification(Column $column)
    {
        $columnName = $column->getName();
        $tableName = $column->getTable()->getName();

        return <<< EOT
# START migration of UUIDs in column '$tableName.$columnName'.
# This can break your DB. Validate and edit these statements as you see fit.
# Please be aware of Propel's ABSOLUTELY NO WARRANTY policy!

EOT;
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     * @param bool $toUuidBinary
     *
     * @return string
     */
    protected function buildUuidMigrationStatements(Column $column, bool $toUuidBinary): string
    {
        $tableName = $this->quoteIdentifier($column->getTable()->getName());
        $columnName = $this->quoteIdentifier($column->getName());
        $tmpColumnName = $this->quoteIdentifier($column->getName() . '_' . bin2hex(random_bytes(4)));
        $swapFlag = $column->getTable()->getVendorInfoForType('mysql')->getUuidSwapFlagLiteral();
        $columnDefinition = $this->platform->getColumnDDL($column);
        $sqlType = $this->platform->getSqlTypeExpression($column);

        $convertFunction = ($toUuidBinary) ? 'UUID_TO_BIN' : 'BIN_TO_UUID';

        return <<< EOT
ALTER TABLE $tableName ADD COLUMN $tmpColumnName $sqlType AFTER $columnName;
UPDATE $tableName SET $tmpColumnName = $convertFunction($columnName, $swapFlag);
ALTER TABLE $tableName DROP COLUMN $columnName;
ALTER TABLE $tableName CHANGE COLUMN $tmpColumnName $columnDefinition;
EOT;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return $this->platform->quoteIdentifier($identifier);
    }
}
