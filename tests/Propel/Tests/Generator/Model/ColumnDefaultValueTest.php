<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\FieldDefaultValue;
use \Propel\Tests\TestCase;

/**
 * Tests for FieldDefaultValue class.
 *
 */
class FieldDefaultValueTest extends TestCase
{
    public function equalsProvider()
    {
        return array(
            array(new FieldDefaultValue('foo', 'bar'), new FieldDefaultValue('foo', 'bar'), true),
            array(new FieldDefaultValue('foo', 'bar'), new FieldDefaultValue('foo1', 'bar'), false),
            array(new FieldDefaultValue('foo', 'bar'), new FieldDefaultValue('foo', 'bar1'), false),
            array(new FieldDefaultValue('current_timestamp', 'bar'), new FieldDefaultValue('now()', 'bar'), true),
            array(new FieldDefaultValue('current_timestamp', 'bar'), new FieldDefaultValue('now()', 'bar1'), false),
        );
    }

    /**
     * @dataProvider equalsProvider
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
