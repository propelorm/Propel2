<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use LazyLoadActiveRecord2;
use Map\LazyLoadActiveRecord2TableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests the generated TableMap classes for lazy load columns.
 */
class GeneratedTableMapLazyLoadTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\LazyLoadActiveRecord2')) {
            $schema = <<<EOF
<database name="lazy_load_active_record_2">
    <table name="lazy_load_active_record_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="VARCHAR" size="100"/>
        <column name="bar" type="VARCHAR" size="100" lazyLoad="true"/>
        <column name="baz" type="VARCHAR" size="100"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testNumHydrateColumns()
    {
        $this->assertEquals(3, LazyLoadActiveRecord2TableMap::NUM_HYDRATE_COLUMNS);
    }

    /**
     * @return void
     */
    public function testPopulateObjectNotInPool()
    {
        LazyLoadActiveRecord2TableMap::clearInstancePool();
        $values = [123, 'fooValue', 'bazValue'];
        $col = 0;
        [$obj, $col] = LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(3, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

    /**
     * @return void
     */
    public function testPopulateObjectInPool()
    {
        LazyLoadActiveRecord2TableMap::clearInstancePool();
        $ar = new LazyLoadActiveRecord2();
        $ar->setId(123);
        $ar->setFoo('fooValue');
        $ar->setBaz('bazValue');
        $ar->setNew(false);
        LazyLoadActiveRecord2TableMap::addInstanceToPool($ar, 123);
        $values = [123, 'fooValue', 'bazValue'];
        $col = 0;
        [$obj, $col] = LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(3, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

    /**
     * @return void
     */
    public function testPopulateObjectNotInPoolStartColGreaterThanOne()
    {
        LazyLoadActiveRecord2TableMap::clearInstancePool();
        $values = ['dummy', 'dummy', 123, 'fooValue', 'bazValue', 'dummy'];
        $col = 2;
        [$obj, $col] = LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(5, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

    /**
     * @return void
     */
    public function testPopulateObjectInPoolStartColGreaterThanOne()
    {
        LazyLoadActiveRecord2TableMap::clearInstancePool();
        $ar = new LazyLoadActiveRecord2();
        $ar->setId(123);
        $ar->setFoo('fooValue');
        $ar->setBaz('bazValue');
        $ar->setNew(false);
        LazyLoadActiveRecord2TableMap::addInstanceToPool($ar, 123);
        $values = ['dummy', 'dummy', 123, 'fooValue', 'bazValue', 'dummy'];
        $col = 2;
        [$obj, $col] = LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(5, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }
}
