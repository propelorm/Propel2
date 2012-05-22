<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Util;

use Propel\Generator\Model\Column;

class ColumnValue
{
    private $col;

    private $val;

    public function __construct(Column $col, $val)
    {
        $this->col = $col;
        $this->val = $val;
    }

    public function getColumn()
    {
        return $this->col;
    }

    public function getValue()
    {
        return $this->val;
    }
}
