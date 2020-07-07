<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Util;

use Propel\Generator\Model\Table;

/**
 * @deprecated Not in use
 */
class DataRow
{
    private $table;

    private $columnValues;

    public function __construct(Table $table, $columnValues)
    {
        $this->table = $table;
        $this->columnValues = $columnValues;
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return \Propel\Generator\Model\ColumnDefaultValue[]
     */
    public function getColumnValues()
    {
        return $this->columnValues;
    }
}
