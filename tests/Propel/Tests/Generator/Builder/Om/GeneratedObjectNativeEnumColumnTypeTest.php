<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for enum column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectNativeEnumColumnTypeTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('ComplexColumnNativeTypeEntity3')) {
            $schema = <<<EOF
<database name="generated_object_complex_native_type_test_3">
    <table name="complex_column_native_type_entity_3">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="NENUM" valueSet="foo, bar, baz, 1, 4,(, foo bar " />
        <column name="bar2" type="NENUM" valueSet="foo, bar" defaultValue="bar" />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            // ok this is hackish but it makes testing of getter and setter independent of each other
            $publicAccessorCode = <<<EOF
class PublicComplexColumnNativeTypeEntity3 extends ComplexColumnNativeTypeEntity3
{
    public \$bar;
}
EOF;
            eval($publicAccessorCode);
        }
    }

    public function testGetter()
    {
        $this->assertTrue(method_exists('ComplexColumnNativeTypeEntity3', 'getBar'));
        $e = new \ComplexColumnNativeTypeEntity3();
        $this->assertNull($e->getBar());
        $e = new \PublicComplexColumnNativeTypeEntity3();
        $e->bar = 'foo';
        $this->assertEquals('foo', $e->getBar());
        $e->bar = '1';
        $this->assertEquals('1', $e->getBar());
        $e->bar = 'foo bar';
        $this->assertEquals('foo bar', $e->getBar());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testGetterThrowsExceptionOnUnknownKey()
    {
        $e = new \PublicComplexColumnNativeTypeEntity3();
        $e->bar = 156;
        $e->getBar();
    }

    public function testGetterDefaultValue()
    {
        $e = new \PublicComplexColumnNativeTypeEntity3();
        $this->assertEquals('bar', $e->getBar2());
    }

    public function testSetter()
    {
        $this->assertTrue(method_exists('\ComplexColumnNativeTypeEntity3', 'setBar'));
        $e = new \PublicComplexColumnNativeTypeEntity3();
        $e->setBar('foo');
        $this->assertEquals('foo', $e->bar);
        $e->setBar(1);
        $this->assertEquals('1', $e->bar);
        $e->setBar('1');
        $this->assertEquals('1', $e->bar);
        $e->setBar('foo bar');
        $this->assertEquals('foo bar', $e->bar);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSetterThrowsExceptionOnUnknownValue()
    {
        $e = new \ComplexColumnNativeTypeEntity3();
        $e->setBar('bazz');
    }

    public function testValueIsPersisted()
    {
        $e = new \ComplexColumnNativeTypeEntity3();
        $e->setBar('baz');
        $e->save();
        \Map\ComplexColumnNativeTypeEntity3TableMap::clearInstancePool();
        $e = \ComplexColumnNativeTypeEntity3Query::create()->findOne();
        $this->assertEquals('baz', $e->getBar());
    }

    public function testValueIsCopied()
    {
        $e1 = new \ComplexColumnNativeTypeEntity3();
        $e1->setBar('baz');
        $e2 = new \ComplexColumnNativeTypeEntity3();
        $e1->copyInto($e2);
        $this->assertEquals('baz', $e2->getBar());
    }

    /**
     * @see https://github.com/propelorm/Propel/issues/139
     */
    public function testSetterWithSameValueDoesNotUpdateObject()
    {
        $e = new \ComplexColumnNativeTypeEntity3();
        $e->setBar('baz');
        $e->resetModified();
        $e->setBar('baz');
        $this->assertFalse($e->isModified());
    }

    /**
     * @see https://github.com/propelorm/Propel/issues/139
     */
    public function testSetterWithSameValueDoesNotUpdateHydratedObject()
    {
        $e = new \ComplexColumnNativeTypeEntity3();
        $e->setBar('baz');
        $e->save();
        // force hydration
        \Map\ComplexColumnNativeTypeEntity3TableMap::clearInstancePool();
        $e = \ComplexColumnNativeTypeEntity3Query::create()->findPk($e->getPrimaryKey());
        $e->setBar('baz');
        $this->assertFalse($e->isModified());
    }
}
