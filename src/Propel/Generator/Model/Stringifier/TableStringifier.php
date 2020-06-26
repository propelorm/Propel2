<?php


namespace Propel\Generator\Model\Stringifier;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;

class TableStringifier
{

    /**
     * Constructs a stringifier to represent multiple tables
     *
     */
    public function __construct()
    {
        $this->tableStringifier = new TableStringifier()
    }

    /**
     * Returns an SQL string representation of the tables
     *
     * @param Table $table
     *
     * @return void
     */
    public function stringify(Table $table) {
        $columns = $this->getColumns($table->getColumns());

        $tableDef = sprintf("  %s (%s):\n%s",
            $table->getName(),
            $table->getCommonName(),
            implode("\n", $columns)
        );

        $fks = $this->getForeignKeys($table->getForeignKeys());
        if ($fks) {
            $tableDef .= "\n    FKs:\n" . implode("\n", $fks);
        }

        $indices = $this->$this->getIndices($table->getIndices());
        if ($indices) {
            $tableDef .= "\n    indices:\n" . implode("\n", $indices);
        }

        $unices = $this->getUnices($table->getUnices());
        if ($unices) {
            $tableDef .= "\n    unices:\n". implode("\n", $unices);
        }

        return $tableDef;
    }

    protected function getColumns(array $columns): array {
        $stringColumns = [];
        foreach ($columns as $column) {
            $stringColumns[] = sprintf("      %s %s %s %s %s %s %s",
                $column->getName(),
                $column->getType(),
                $column->getSize() ? '(' . $column->getSize() . ')' : '',
                $column->isPrimaryKey() ? 'PK' : '',
                $column->isNotNull() ? 'NOT NULL' : '',
                $column->getDefaultValueString() ? "'".$column->getDefaultValueString()."'" : '',
                $column->isAutoIncrement() ? 'AUTO_INCREMENT' : ''
            );
        }

        return $columns;
    }

    protected function getForeignKeys(array $foreignKeys): array {
        $stringForeignKeys = [];
        foreach ($foreignKeys as $fk) {
            $stringForeignKeys[] = sprintf("      %s to %s.%s (%s => %s)",
                $fk->getName(),
                $fk->getForeignSchemaName(),
                $fk->getForeignTableCommonName(),
                join(', ', $fk->getLocalColumns()),
                join(', ', $fk->getForeignColumns())
            );
        }

        return $stringForeignKeys;
    }

    protected function getIndices(array $indices): array {
        $stringIndices = [];
        foreach ($indices as $index) {
            $indexColumns = [];
            foreach ($index->getColumns() as $indexColumnName) {
                $indexColumns[] = sprintf('%s (%s)', $indexColumnName, $index->getColumnSize($indexColumnName));
            }
            $stringIndices[] = sprintf("      %s (%s)",
                $index->getName(),
                join(', ', $indexColumns)
            );
        }

        return $stringIndices;
    }

    protected function getUnices(array $unices): array {
        $stringUnices = [];
        foreach ($unices as $index) {
            $unices[] = sprintf("      %s (%s)",
                $index->getName(),
                join(', ', $index->getColumns())
            );
        }

        return $stringUnices;
    }
}
