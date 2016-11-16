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

    public function testDefaultValue()
    {
        $e = new \ComplexFieldTypeEntity4();
        $this->assertNull($e->getBar());
        $this->assertTrue($e->getTrueBar());
        $this->assertFalse($e->getFalseBar());
    }
}
