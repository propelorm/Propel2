<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use ComplexColumnTypeEntity4;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for boolean column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectBooleanColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('ComplexColumnTypeEntity4')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_4">
    <table name="complex_column_type_entity_4">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="BOOLEAN"/>
        <column name="true_bar" type="BOOLEAN" defaultValue="true"/>
        <column name="false_bar" type="BOOLEAN" defaultValue="false"/>
        <column name="is_baz" type="BOOLEAN"/>
        <column name="has_xy" type="BOOLEAN"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testIsserName()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeEntity4', 'isBar'));
        $this->assertTrue(method_exists('ComplexColumnTypeEntity4', 'isBaz'));
        $this->assertTrue(method_exists('ComplexColumnTypeEntity4', 'hasXy'));
    }

    /**
     * @return array
     */
    public function providerForSetter()
    {
        return [
            [true, true],
            [false, false],
            ['true', true],
            ['false', false],
            [1, true],
            [0, false],
            ['1', true],
            ['0', false],
            ['on', true],
            ['off', false],
            ['yes', true],
            ['no', false],
            ['y', true],
            ['n', false],
            ['Y', true],
            ['N', false],
            ['+', true],
            ['-', false],
            ['', false],
        ];
    }

    /**
     * @dataProvider providerForSetter
     *
     * @return void
     */
    public function testSetterBooleanValue($value, $expected)
    {
        $e = new ComplexColumnTypeEntity4();
        $e->setBar($value);
        if ($expected) {
            $this->assertTrue($e->getBar());
            $this->assertTrue($e->isBar());
        } else {
            $this->assertFalse($e->getBar());
            $this->assertFalse($e->isBar());
        }
    }

    /**
     * @return void
     */
    public function testDefaultValue()
    {
        $e = new ComplexColumnTypeEntity4();
        $this->assertNull($e->getBar());
        $this->assertTrue($e->getTrueBar());
        $this->assertFalse($e->getFalseBar());
    }
}
