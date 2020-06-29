<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Stringifier;

/**
 * A class for representing a collection of Tables as a String
 */
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
