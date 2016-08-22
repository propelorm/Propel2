<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use \MyNameSpace\ComplexFieldTypeEntity2;
use \MyNameSpace\ComplexFieldTypeEntity2Query;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Configuration;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for array column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectArrayFieldTypeActiveRecordTest extends TestCase
{
    /** @var  Configuration */
    private $con;

    public function setUp()
    {
        if (!class_exists('MyNameSpace\\ComplexFieldTypeEntity2')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_2" namespace="MyNameSpace" activeRecord="true">
    <entity name="ComplexFieldTypeEntity2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="tags" type="ARRAY" />
        <field name="value_set" type="ARRAY" />
        <field name="defaults" type="ARRAY" defaultValue="FOO" />
        <field name="multiple_defaults" type="ARRAY" defaultValue="FOO, BAR,BAZ" />
    </entity>
</database>
EOF;
            $this->con = QuickBuilder::buildSchema($schema);
        }

        if (null === $this->con) {
            $this->con = Configuration::getCurrentConfiguration();
        }

        $this->con->getRepository('MyNameSpace\\ComplexFieldTypeEntity2')->deleteAll();
    }

    public function testActiveRecordMethods()
    {
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'getTags'));
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'hasTag'));
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'setTags'));
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'addTag'));
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'removeTag'));
        // only plural column names get a tester, an adder, and a remover method
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'getValueSet'));
        $this->assertFalse(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'hasValueSet'));
        $this->assertTrue(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'setValueSet'));
        $this->assertFalse(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'addValueSet'));
        $this->assertFalse(method_exists('\MyNameSpace\ComplexFieldTypeEntity2', 'removeValueSet'));
    }

    public function testAdderAddsNewValueToExistingData()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->assertEquals(array('FOO'), $e->getDefaults());
        $e->addDefault('bar');
        $this->assertEquals(array('FOO', 'bar'), $e->getDefaults());
    }

    public function testAdderAddsNewValueToMultipleExistingData()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $e->getMultipleDefaults());
        $e->addMultipleDefault('bar');
        $this->assertEquals(array('FOO', 'BAR', 'BAZ', 'bar'), $e->getMultipleDefaults());
    }

    public function testTester()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->assertFalse($e->hasTag('foo'));
        $this->assertFalse($e->hasTag(1234));
        $value = array('foo', 1234);
        $e->setTags($value);
        $this->assertTrue($e->hasTag('foo'));
        $this->assertTrue($e->hasTag(1234));
        $this->assertFalse($e->hasTag('bar'));
        $this->assertFalse($e->hasTag(12));
    }

    public function testAdder()
    {
        $e = new ComplexFieldTypeEntity2();
        $e->addTag('foo');
        $this->assertEquals(array('foo'), $e->getTags());
        $e->addTag(1234);
        $this->assertEquals(array('foo', 1234), $e->getTags());
        $e->addTag('foo');
        $this->assertEquals(array('foo', 1234, 'foo'), $e->getTags());
        $e->setTags(array(12, 34));
        $e->addTag('foo');
        $this->assertEquals(array(12, 34, 'foo'), $e->getTags());
    }

    public function testRemover()
    {
        $e = new ComplexFieldTypeEntity2();
        $e->removeTag('foo');
        $this->assertEquals(array(), $e->getTags());
        $e->setTags(array('foo', 1234));
        $e->removeTag('foo');
        $this->assertEquals(array(1234), $e->getTags());
        $e->removeTag(1234);
        $this->assertEquals(array(), $e->getTags());
        $e->setTags(array(12, 34, 1234));
        $e->removeTag('foo');
        $this->assertEquals(array(12, 34, 1234), $e->getTags());
        $e->removeTag('1234');
        $this->assertEquals(array(12, 34), $e->getTags());
    }

    public function testValueIsPersisted()
    {
        $e = new ComplexFieldTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $e->save();

        $e = ComplexFieldTypeEntity2Query::create()->findOne();
        $this->assertEquals($value, $e->getTags(), 'array columns are persisted');
    }
}
