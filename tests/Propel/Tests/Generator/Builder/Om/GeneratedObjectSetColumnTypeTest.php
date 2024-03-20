<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use MyNameSpace\ComplexColumnTypeEntitySet;
use MyNameSpace\ComplexColumnTypeEntitySetQuery;
use MyNameSpace\Map\ComplexColumnTypeEntitySetTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\TestCase;
use PublicComplexColumnTypeEntitySet;

/**
 * Tests the generated objects for SET columns types accessor & mutator
 *
 * @author Francois Zaninotto, Moritz Schröder
 */
class GeneratedObjectSetColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('MyNameSpace\\ComplexColumnTypeEntitySet')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_set" namespace="MyNameSpace">
    <table name="complex_column_type_entity_set">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="tags" type="SET" valueSet="foo, bar, baz, 1, 4,(, foo bar "/>
        <column name="bar" type="SET" valueSet="foo, bar"/>
        <column name="defaults" type="SET" valueSet="foo, bar, foo baz" defaultValue="bar"/>
        <column name="bears" type="SET" valueSet="foo, bar, baz, kevin" defaultValue="bar, baz"/>

    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            // ok this is hackish but it makes testing of getter and setter independent of each other
            $publicAccessorCode = <<<EOF
class PublicComplexColumnTypeEntitySet extends MyNameSpace\ComplexColumnTypeEntitySet
{
    public \$bar;
    public \$tags;
}
EOF;
            eval($publicAccessorCode);
        }

        ComplexColumnTypeEntitySetTableMap::doDeleteAll();
    }

    /**
     * @return void
     */
    public function testActiveRecordMethods()
    {
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'getTags'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'hasTag'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'setTags'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'addTag'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'removeTag'));
        // only plural column names get a tester, an adder, and a remover method
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'getBar'));
        $this->assertFalse(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'hasBar'));
        $this->assertTrue(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'setBar'));
        $this->assertFalse(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'addBar'));
        $this->assertFalse(method_exists('MyNameSpace\ComplexColumnTypeEntitySet', 'removeBar'));
    }

    /**
     * @return void
     */
    public function testGetterDefaultValue()
    {
        $e = new ComplexColumnTypeEntitySet();
        $this->assertEquals([], $e->getTags(), 'array columns return an empty array by default');
    }

    /**
     * @return void
     */
    public function testGetterDefaultValueWithData()
    {
        $e = new ComplexColumnTypeEntitySet();
        $this->assertSame(['bar'], $e->getDefaults());
    }

    /**
     * @return void
     */
    public function testGetterDefaultValueWithMultipleData()
    {
        $e = new ComplexColumnTypeEntitySet();
        $this->assertEquals(['bar', 'baz'], $e->getBears());
    }

    /**
     * @return void
     */
    public function testGetterValidValue()
    {
        $e = new PublicComplexColumnTypeEntitySet();
        $e->tags = 5;
        $this->assertEquals(['foo', 'baz'], $e->getTags());
    }

    /**
     * @return void
     */
    public function testGetterThrowsExceptionOnUnknownKey()
    {
        $this->expectException(PropelException::class);

        $e = new PublicComplexColumnTypeEntitySet();
        $e->bar = 156;
        $e->getBar();
    }

    /**
     * @return void
     */
    public function testAdderAddsNewValueToExistingData()
    {
        $e = new ComplexColumnTypeEntitySet();
        $this->assertEquals(['bar'], $e->getDefaults());
        $e->addDefault('foo baz');
        $this->assertEquals(['bar', 'foo baz'], $e->getDefaults());
    }

    /**
     * @return void
     */
    public function testAdderAddsNewValueToMultipleExistingData()
    {
        $e = new ComplexColumnTypeEntitySet();
        $this->assertEquals(['bar', 'baz'], $e->getBears());
        $e->addBear('kevin');
        $this->assertEquals(['bar', 'baz', 'kevin'], $e->getBears());
    }

    /**
     * @return void
     */
    public function testDefaultValuesAreWellPersisted()
    {
        $e = new ComplexColumnTypeEntitySet();
        $e->save();

        ComplexColumnTypeEntitySetTableMap::clearInstancePool();
        $e = ComplexColumnTypeEntitySetQuery::create()->findOne();

        $this->assertEquals(['bar'], $e->getDefaults());
    }

    /**
     * @return void
     */
    public function testMultipleDefaultValuesAreWellPersisted()
    {
        $e = new ComplexColumnTypeEntitySet();
        $e->save();

        ComplexColumnTypeEntitySetTableMap::clearInstancePool();
        $e = ComplexColumnTypeEntitySetQuery::create()->findOne();

        $this->assertEquals(['bar', 'baz'], $e->getBears());
    }

    /**
     * @return void
     */
    public function testSetterArrayValue()
    {
        $e = new PublicComplexColumnTypeEntitySet();
        $value = ['foo', '1'];
        $e->setTags($value);
        $this->assertEquals($value, $e->getTags(), 'array columns can store arrays');

        $this->assertEquals(9, $e->tags);
    }

    /**
     * @return void
     */
    public function testSetterResetValue()
    {
        $e = new ComplexColumnTypeEntitySet();
        $value = ['foo', '1'];
        $e->setTags($value);
        $e->setTags([]);
        $this->assertEquals([], $e->getTags(), 'object columns can be reset');
    }

    /**
     * @return void
     */
    public function testSetterThrowsExceptionOnUnknownValue()
    {
        $this->expectException(PropelException::class);

        $e = new ComplexColumnTypeEntitySet();
        $e->setBar(['bazz']);
    }

    /**
     * @return void
     */
    public function testTester()
    {
        $e = new ComplexColumnTypeEntitySet();
        $this->assertFalse($e->hasTag('foo'));
        $this->assertFalse($e->hasTag('1'));
        $value = ['foo', '1'];
        $e->setTags($value);
        $this->assertTrue($e->hasTag('foo'));
        $this->assertTrue($e->hasTag('1'));
        $this->assertFalse($e->hasTag('bar'));
        $this->assertFalse($e->hasTag('4'));
    }

    /**
     * @return void
     */
    public function testAdder()
    {
        $e = new ComplexColumnTypeEntitySet();
        $e->addTag('foo');
        $this->assertEquals(['foo'], $e->getTags());
        $e->addTag('1');
        $this->assertEquals(['foo', '1'], $e->getTags());
        $e->addTag('foo');
        $this->assertEquals(['foo', '1'], $e->getTags());
        $e->setTags(['foo bar', '4']);
        $e->addTag('foo');
        $this->assertEquals(['foo', '4', 'foo bar'], $e->getTags());
    }

    /**
     * @return void
     */
    public function testRemover()
    {
        $e = new ComplexColumnTypeEntitySet();
        $e->removeTag('foo');
        $this->assertEquals([], $e->getTags());
        $e->setTags(['foo', '1']);
        $e->removeTag('foo');
        $this->assertEquals(['1'], $e->getTags());
        $e->removeTag('1');
        $this->assertEquals([], $e->getTags());
        $e->setTags(['1', 'bar', 'baz']);
        $e->removeTag('foo');
        $this->assertEquals(['bar', 'baz', '1'], $e->getTags());
        $e->removeTag('bar');
        $this->assertEquals(['baz', '1'], $e->getTags());
    }

    /**
     * @return void
     */
    public function testValueIsPersisted()
    {
        $e = new ComplexColumnTypeEntitySet();
        $value = ['foo', '1'];
        $e->setTags($value);
        $e->save();
        ComplexColumnTypeEntitySetTableMap::clearInstancePool();
        $e = ComplexColumnTypeEntitySetQuery::create()->findOne();
        $this->assertEquals($value, $e->getTags(), 'array columns are persisted');
    }

    /**
     * @return void
     */
    public function testGetterDoesNotKeepValueBetweenTwoHydrationsWhenUsingOnDemandFormatter()
    {
        ComplexColumnTypeEntitySetQuery::create()->deleteAll();

        $e = new ComplexColumnTypeEntitySet();
        $e->setTags(['foo', 'bar']);
        $e->save();

        $e = new ComplexColumnTypeEntitySet();
        $e->setTags(['baz', '1']);
        $e->save();

        $q = ComplexColumnTypeEntitySetQuery::create()
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find();

        $tags = [];
        foreach ($q as $e) {
            $tags[] = $e->getTags();
        }
        $this->assertNotEquals($tags[0], $tags[1]);
    }
}
