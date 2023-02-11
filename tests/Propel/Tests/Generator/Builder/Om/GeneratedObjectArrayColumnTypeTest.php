<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use MyNameSpace\ComplexColumnTypeEntity2;
use MyNameSpace\ComplexColumnTypeEntity2Query;
use MyNameSpace\ComplexColumnTypeEntityWithConstructorQuery;
use MyNameSpace\Map\ComplexColumnTypeEntity2TableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Propel;
use Propel\Tests\Fixtures\Generator\Builder\Om\ComplexColumnTypeEntityWithConstructor;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for array column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectArrayColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('MyNameSpace\\ComplexColumnTypeEntity2')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_2" namespace="MyNameSpace">
    <table name="complex_column_type_entity_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="tags" type="ARRAY"/>
        <column name="value_set" type="ARRAY"/>
        <column name="defaults" type="ARRAY" defaultValue="FOO"/>
        <column name="multiple_defaults" type="ARRAY" defaultValue="FOO, BAR,BAZ"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }

        ComplexColumnTypeEntity2TableMap::doDeleteAll();
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testGetterDefaultValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals([], $e->getTags(), 'array columns return an empty array by default');
    }

    /**
     * @return void
     */
    public function testGetterDefaultValueWithData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(['FOO'], $e->getDefaults());
    }

    /**
     * @return void
     */
    public function testGetterDefaultValueWithMultipleData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(['FOO', 'BAR', 'BAZ'], $e->getMultipleDefaults());
    }

    /**
     * @return void
     */
    public function testAdderAddsNewValueToExistingData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(['FOO'], $e->getDefaults());
        $e->addDefault('bar');
        $this->assertEquals(['FOO', 'bar'], $e->getDefaults());
    }

    /**
     * @return void
     */
    public function testAdderAddsNewValueToMultipleExistingData()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertEquals(['FOO', 'BAR', 'BAZ'], $e->getMultipleDefaults());
        $e->addMultipleDefault('bar');
        $this->assertEquals(['FOO', 'BAR', 'BAZ', 'bar'], $e->getMultipleDefaults());
    }

    /**
     * @return void
     */
    public function testDefaultValuesAreWellPersisted()
    {
        $e = new ComplexColumnTypeEntity2();
        $e->save();

        ComplexColumnTypeEntity2TableMap::clearInstancePool();
        $e = ComplexColumnTypeEntity2Query::create()->findOne();

        $this->assertEquals(['FOO'], $e->getDefaults());
    }

    /**
     * @return void
     */
    public function testMultipleDefaultValuesAreWellPersisted()
    {
        $e = new ComplexColumnTypeEntity2();
        $e->save();

        ComplexColumnTypeEntity2TableMap::clearInstancePool();
        $e = ComplexColumnTypeEntity2Query::create()->findOne();

        $this->assertEquals(['FOO', 'BAR', 'BAZ'], $e->getMultipleDefaults());
    }

    /**
     * @return void
     */
    public function testSetterArrayValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $value = ['foo', 1234];
        $e->setTags($value);
        $this->assertEquals($value, $e->getTags(), 'array columns can store arrays');
    }

    /**
     * @return void
     */
    public function testGetterForArrayWithOnlyOneZeroValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $value = [0];
        $e->setTags($value);
        $this->assertEquals($value, $e->getTags());
    }

    /**
     * @return void
     */
    public function testSetterResetValue()
    {
        $e = new ComplexColumnTypeEntity2();
        $value = ['foo', 1234];
        $e->setTags($value);
        $e->setTags([]);
        $this->assertEquals([], $e->getTags(), 'object columns can be reset');
    }

    /**
     * @return void
     */
    public function testTester()
    {
        $e = new ComplexColumnTypeEntity2();
        $this->assertFalse($e->hasTag('foo'));
        $this->assertFalse($e->hasTag(1234));
        $value = ['foo', 1234];
        $e->setTags($value);
        $this->assertTrue($e->hasTag('foo'));
        $this->assertTrue($e->hasTag(1234));
        $this->assertFalse($e->hasTag('bar'));
        $this->assertFalse($e->hasTag(12));
    }

    /**
     * @return void
     */
    public function testAdder()
    {
        $e = new ComplexColumnTypeEntity2();
        $e->addTag('foo');
        $this->assertEquals(['foo'], $e->getTags());
        $e->addTag(1234);
        $this->assertEquals(['foo', 1234], $e->getTags());
        $e->addTag('foo');
        $this->assertEquals(['foo', 1234, 'foo'], $e->getTags());
        $e->setTags([12, 34]);
        $e->addTag('foo');
        $this->assertEquals([12, 34, 'foo'], $e->getTags());
    }

    /**
     * @return void
     */
    public function testRemover()
    {
        $e = new ComplexColumnTypeEntity2();
        $e->removeTag('foo');
        $this->assertEquals([], $e->getTags());
        $e->setTags(['foo', 1234]);
        $e->removeTag('foo');
        $this->assertEquals([1234], $e->getTags());
        $e->removeTag(1234);
        $this->assertEquals([], $e->getTags());
        $e->setTags([12, 34, 1234]);
        $e->removeTag('foo');
        $this->assertEquals([12, 34, 1234], $e->getTags());
        $e->removeTag('1234');
        $this->assertEquals([12, 34], $e->getTags());
    }

    /**
     * @return void
     */
    public function testValueIsPersisted()
    {
        $e = new ComplexColumnTypeEntity2();
        $value = ['foo', 1234];
        $e->setTags($value);
        $e->save();
        ComplexColumnTypeEntity2TableMap::clearInstancePool();
        $e = ComplexColumnTypeEntity2Query::create()->findOne();
        $this->assertEquals($value, $e->getTags(), 'array columns are persisted');
    }

    /**
     * @return void
     */
    public function testGetterDoesNotKeepValueBetweenTwoHydrationsWhenUsingOnDemandFormatter()
    {
        ComplexColumnTypeEntity2Query::create()->deleteAll();

        $e = new ComplexColumnTypeEntity2();
        $e->setTags([1, 2]);
        $e->save();

        $e = new ComplexColumnTypeEntity2();
        $e->setTags([3, 4]);
        $e->save();

        $q = ComplexColumnTypeEntity2Query::create()
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find();

        $tags = [];
        foreach ($q as $e) {
            $tags[] = $e->getTags();
        }
        $this->assertNotEquals($tags[0], $tags[1]);
    }

    /**
     * @return void
     */
    public function testHydrateOverwritePreviousValues()
    {
        $schema = <<<EOF
<database name="generated_object_complex_type_test_with_constructor" namespace="MyNameSpace">
  <table name="complex_column_type_entity_with_constructor">
    <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
    <column name="tags" type="ARRAY"/>
  </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema);

        Propel::disableInstancePooling(); // need to be disabled to test the hydrate() method

        $obj = new ComplexColumnTypeEntityWithConstructor();
        $this->assertEquals(['foo', 'bar'], $obj->getTags());

        $obj->setTags(['baz']);
        $this->assertEquals(['baz'], $obj->getTags());

        $obj->save();

        $obj = ComplexColumnTypeEntityWithConstructorQuery::create()
            ->findOne();
        $this->assertEquals(['baz'], $obj->getTags());

        Propel::enableInstancePooling();
    }
}
