<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Platform\Util;

use Propel\Generator\Model\Table;

/**
 * Merges several ALTER TABLE statements when creating migrations.
 *
 * @phpstan-consistent-constructor
 */
class AlterTableStatementMerger
{
    /**
     * @var string
     */
    public const NO_MERGE_ACROSS_THIS_LINE = "\n;--sql statement block;\n";

    /**
     * @var \Propel\Generator\Model\Table table
     */
    protected Table $table;

    protected string $quotedTableName;

    /**
     * Summary of merge
     *
     * @param \Propel\Generator\Model\Table $table
     * @param string $sql
     *
     * @return string
     */
    public static function merge(Table $table, string $sql): string
    {
        $merger = new static($table);

        return $merger->mergeStatements($sql);
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;

        // quoting needs to come from platform, not from table (bug?)
        //$this->quotedTableName = $this->table->quoteIdentifier($this->table->getName());
        $this->quotedTableName = $this->table->getPlatform()->quoteIdentifier($this->table->getName());
    }

    /**
     * Merges column changes into one command. This is more compatible
     * especially with PK constraints.
     *
     * @param string $sql
     *
     * @return string
     */
    public function mergeStatements(string $sql): string
    {
        $statements = explode(';', $sql);
        $blocks = [];
        $currentBlock = [];

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (!$statement) {
                continue;
            }

            $canMerge = $this->canMergeStatement($statement);
            if ($canMerge) {
                $currentBlock[] = $statement;

                continue;
            }

            if ($currentBlock) {
                $blocks[] = $this->mergeAlterTableStatements($currentBlock);
                $currentBlock = [];
            }

            if ("\n;$statement;\n" === static::NO_MERGE_ACROSS_THIS_LINE) {
                continue;
            }
            $blocks[] = $statement . ';';
        }
        if ($currentBlock) {
            $blocks[] = $this->mergeAlterTableStatements($currentBlock);
        }

        return "\n" . implode("\n\n", $blocks);
    }

    /**
     * @param array $statements
     *
     * @return string
     */
    protected function mergeAlterTableStatements(array $statements): string
    {
        if (!$statements) {
            return '';
        }
        $alterTableExpression = "ALTER TABLE {$this->quotedTableName} ";
        $changeStatements = array_map(fn ($statement) => str_replace($alterTableExpression, '', $statement), $statements);
        $mergedStatements = "\n\n  " . implode(",\n\n  ", $changeStatements);

        return "ALTER TABLE {$this->quotedTableName}$mergedStatements;\n";
    }

    /**
     * @param string $statement
     *
     * @return bool
     */
    protected function canMergeStatement(string $statement): bool
    {
        $canMergeStatementRegex = "/ALTER TABLE {$this->quotedTableName} (?!RENAME)/"; // alter table statements but not rename

        return (bool)preg_match($canMergeStatementRegex, $statement);
    }
}
