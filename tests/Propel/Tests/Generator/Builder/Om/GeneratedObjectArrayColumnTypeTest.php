<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Generator\Builder\Om;

use MyNameSpace\ComplexColumnTypeEntity2;
use MyNameSpace\ComplexColumnTypeEntity2Query;
use MyNameSpace\Map\ComplexColumnTypeEntity2TableMap;
use MyNameSpace\ComplexColumnTypeEntityWithConstructorQuery;

use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\Generator\Builder\Om\Fixtures\ComplexColumnTypeEntityWithConstructor;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for array column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectArrayColumnTypeTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('MyNameSpace\\ComplexColumnTypeEntity2')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_2" namespace="MyNameSpace">
    <table name="complex_column_type_entity_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="tags" type="ARRAY" />
        <column name="value_set" type="ARRAY" />
        <column name="defaults" type="ARRAY" defaultValue="FOO" />
        <column name="multiple_defaults" type="ARRAY" defaultValue="FOO, BAR,BAZ" />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }

        ComplexColumnTypeEntity2TableMap::doDeleteAll();
    }

    public function testActiveRecordMethods()
    {
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'getTags'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'hasTag'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'setTags'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'addTag'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'removeTag'));
        // only plural column names get a tester, an adder, and a remover method
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'getValueSet'));
        $this->assertFalse(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'hasValueSet'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'setValueSet'));
        $this->assertFalse(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'addValueSet'));
        $this->assertFalse(method_exists('MyNameSpace\ComplexColumnTypeEntity2', 'removeValueSet'));
    }

    public function testGetterDefaultValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(array(), $e->getTags(), 'array columns return an empty array by default');
    }

    public function testGetterDefaultValueWithData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(array('FOO'), $e->getDefaults());
    }

    public function testGetterDefaultValueWithMultipleData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $e->getMultipleDefaults());
    }

    public function testAdderAddsNewValueToExistingData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(array('FOO'), $e->getDefaults());
        $e->addDefault('bar');
        $this->assertEquals(array('FOO', 'bar'), $e->getDefaults());
    }

    public function testAdderAddsNewValueToMultipleExistingData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $e->getMultipleDefaults());
        $e->addMultipleDefault('bar');
        $this->assertEquals(array('FOO', 'BAR', 'BAZ', 'bar'), $e->getMultipleDefaults());
    }

    public function testDefaultValuesAreWellPersisted()
    {
        $e = new ComplexColumnTypeEntity2();
        $e->save();

        ComplexColumnTypeEntity2TableMap::clearInstancePool();
        $e = ComplexColumnTypeEntity2Query::create()->findOne();

        $this->assertEquals(array('FOO'), $e->getDefaults());
    }

    public function testMultipleDefaultValuesAreWellPersisted()
    {
        $e = new ComplexColumnTypeEntity2();
        $e->save();

        ComplexColumnTypeEntity2TableMap::clearInstancePool();
        $e = ComplexColumnTypeEntity2Query::create()->findOne();

        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $e->getMultipleDefaults());
    }

    public function testSetterArrayValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $this->assertEquals($value, $e->getTags(), 'array columns can store arrays');
    }

    public function testSetterResetValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $e->setTags(array());
        $this->assertEquals(array(), $e->getTags(), 'object columns can be reset');
    }

    public function testTester()
    {
        $e = new ComplexColumnTypeEntity2();
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
        $e = new ComplexColumnTypeEntity2();
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
        $e = new ComplexColumnTypeEntity2();
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
        $e = new ComplexColumnTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $e->save();
        ComplexColumnTypeEntity2TableMap::clearInstancePool();
        $e = ComplexColumnTypeEntity2Query::create()->findOne();
        $this->assertEquals($value, $e->getTags(), 'array columns are persisted');
    }

    public function testGetterDoesNotKeepValueBetweenTwoHydrationsWhenUsingOnDemandFormatter()
    {
        ComplexColumnTypeEntity2Query::create()->deleteAll();

        $e = new ComplexColumnTypeEntity2();
        $e->setTags(array(1,2));
        $e->save();

        $e = new ComplexColumnTypeEntity2();
        $e->setTags(array(3,4));
        $e->save();

        $q = ComplexColumnTypeEntity2Query::create()
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find();

        $tags = array();
        foreach ($q as $e) {
            $tags[] = $e->getTags();
        }
        $this->assertNotEquals($tags[0], $tags[1]);
    }

    public function testHydrateOverwritePreviousValues()
    {
        $schema = <<<EOF
<database name="generated_object_complex_type_test_with_constructor" namespace="MyNameSpace">
  <table name="complex_column_type_entity_with_constructor">
    <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    <column name="tags" type="ARRAY" />
  </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema);

        Propel::disableInstancePooling(); // need to be disabled to test the hydrate() method

        $obj = new ComplexColumnTypeEntityWithConstructor();
        $this->assertEquals(array('foo', 'bar'), $obj->getTags());

        $obj->setTags(array('baz'));
        $this->assertEquals(array('baz'), $obj->getTags());

        $obj->save();

        $obj = ComplexColumnTypeEntityWithConstructorQuery::create()
            ->findOne();
        $this->assertEquals(array('baz'), $obj->getTags());

        Propel::enableInstancePooling();
    }
}
