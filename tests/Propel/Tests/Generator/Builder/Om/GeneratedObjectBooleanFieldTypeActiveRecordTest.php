<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Util\QuickBuilder;

use \Propel\Tests\TestCase;

/**
 * Tests the generated objects for boolean field types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectBooleanFieldTypeActiveRecordTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('ComplexFieldTypeEntity4')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_4" activeRecord="true">
    <entity name="ComplexFieldTypeEntity4">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="BOOLEAN" />
        <field name="true_bar" type="BOOLEAN" defaultValue="true" />
        <field name="false_bar" type="BOOLEAN" defaultValue="false" />
        <field name="is_baz" type="BOOLEAN" />
        <field name="has_xy" type="BOOLEAN" />
    </entity>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testIsserName()
    {
        $this->assertTrue(method_exists('\ComplexFieldTypeEntity4', 'isBar'));
        $this->assertTrue(method_exists('\ComplexFieldTypeEntity4', 'isBaz'));
        $this->assertTrue(method_exists('\ComplexFieldTypeEntity4', 'hasXy'));
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
        $e = new \ComplexFieldTypeEntity4();
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
        $e = new \ComplexFieldTypeEntity4();
        $this->assertNull($e->getBar());
        $this->assertTrue($e->getTrueBar());
        $this->assertFalse($e->getFalseBar());
    }
}
