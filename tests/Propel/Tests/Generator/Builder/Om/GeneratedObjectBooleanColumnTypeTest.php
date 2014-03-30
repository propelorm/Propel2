<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Propel;
use \Propel\Tests\TestCase;

/**
 * Tests the generated objects for boolean column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectBooleanColumnTypeTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('ComplexColumnTypeEntity4')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_4">
    <table name="complex_column_type_entity_4">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="BOOLEAN" />
        <column name="true_bar" type="BOOLEAN" defaultValue="true" />
        <column name="false_bar" type="BOOLEAN" defaultValue="false" />
        <column name="is_baz" type="BOOLEAN" />
        <column name="has_xy" type="BOOLEAN" />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testIsserName()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeEntity4', 'isBar'));
        $this->assertTrue(method_exists('ComplexColumnTypeEntity4', 'isBaz'));
        $this->assertTrue(method_exists('ComplexColumnTypeEntity4', 'hasXy'));
    }

    public function providerForSetter()
    {
        return array(
            array(true, true),
            array(false, false),
            array('true', true),
            array('false', false),
            array(1, true),
            array(0, false),
            array('1', true),
            array('0', false),
            array('on', true),
            array('off', false),
            array('yes', true),
            array('no', false),
            array('y', true),
            array('n', false),
            array('Y', true),
            array('N', false),
            array('+', true),
            array('-', false),
            array('', false),
        );
    }

    /**
     * @dataProvider providerForSetter
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

    public function testDefaultValue()
    {
        $e = new ComplexColumnTypeEntity4();
        $this->assertNull($e->getBar());
        $this->assertTrue($e->getTrueBar());
        $this->assertFalse($e->getFalseBar());
    }

}
