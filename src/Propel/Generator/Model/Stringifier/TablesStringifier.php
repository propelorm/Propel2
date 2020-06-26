<?php


namespace Propel\Generator\Model\Stringifier;

class TablesStringifier
{
    /**
     * @var TableStringifier
     */
    protected $tableStringifier;

    /**
     * Constructs a stringifier to represent multiple tables
     */
    public function __construct()
    {
        $this->tableStringifier = new TableStringifier();
    }

    /**
     * Returns an SQL string representation of the tables
     *
     * @param Table[] $tables
     *
     * @return string
     */
    public function stringify(array $tables): string
    {
        $stringTables = [];
        foreach ($tables as $table) {
            $stringTables[] = $this->tableStringifier->stringify($table);
        }

        return implode("\n", $stringTables);
    }
}
