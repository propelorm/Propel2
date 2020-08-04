<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Tests\TestCase;

/**
 * Tests for ColumnDefaultValue class.
 */
class ColumnDefaultValueTest extends TestCase
{
    public function equalsProvider()
    {
        return [
            [new ColumnDefaultValue('foo', 'bar'), new ColumnDefaultValue('foo', 'bar'), true],
            [new ColumnDefaultValue('foo', 'bar'), new ColumnDefaultValue('foo1', 'bar'), false],
            [new ColumnDefaultValue('foo', 'bar'), new ColumnDefaultValue('foo', 'bar1'), false],
            [new ColumnDefaultValue('current_timestamp', 'bar'), new ColumnDefaultValue('now()', 'bar'), true],
            [new ColumnDefaultValue('current_timestamp', 'bar'), new ColumnDefaultValue('now()', 'bar1'), false],
        ];
    }

    /**
     * @dataProvider equalsProvider
     *
     * @return void
     */
    public function testEquals($def1, $def2, $test)
    {
        if ($test) {
            $this->assertTrue($def1->equals($def2));
        } else {
            $this->assertFalse($def1->equals($def2));
        }
    }
}
