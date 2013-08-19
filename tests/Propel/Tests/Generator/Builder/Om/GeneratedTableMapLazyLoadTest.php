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
 * Tests the generated TableMap classes for lazy load columns.
 *
 */
class GeneratedTableMapLazyLoadTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('\LazyLoadActiveRecord2')) {
            $schema = <<<EOF
<database name="lazy_load_active_record_2">
    <table name="lazy_load_active_record_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="foo" type="VARCHAR" size="100" />
        <column name="bar" type="VARCHAR" size="100" lazyLoad="true" />
        <column name="baz" type="VARCHAR" size="100" />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testNumHydrateColumns()
    {
        $this->assertEquals(3, \Map\LazyLoadActiveRecord2TableMap::NUM_HYDRATE_COLUMNS);
    }

    public function testPopulateObjectNotInPool()
    {
        \Map\LazyLoadActiveRecord2TableMap::clearInstancePool();
        $values = array(123, 'fooValue', 'bazValue');
        $col = 0;
        list($obj, $col) = \Map\LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(3, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

    public function testPopulateObjectInPool()
    {
        \Map\LazyLoadActiveRecord2TableMap::clearInstancePool();
        $ar = new \LazyLoadActiveRecord2();
        $ar->setId(123);
        $ar->setFoo('fooValue');
        $ar->setBaz('bazValue');
        $ar->setNew(false);
        \Map\LazyLoadActiveRecord2TableMap::addInstanceToPool($ar, 123);
        $values = array(123, 'fooValue', 'bazValue');
        $col = 0;
        list($obj, $col) = \Map\LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(3, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

    public function testPopulateObjectNotInPoolStartColGreaterThanOne()
    {
        \Map\LazyLoadActiveRecord2TableMap::clearInstancePool();
        $values = array('dummy', 'dummy', 123, 'fooValue', 'bazValue', 'dummy');
        $col = 2;
        list($obj, $col) = \Map\LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(5, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

    public function testPopulateObjectInPoolStartColGreaterThanOne()
    {
        \Map\LazyLoadActiveRecord2TableMap::clearInstancePool();
        $ar = new \LazyLoadActiveRecord2();
        $ar->setId(123);
        $ar->setFoo('fooValue');
        $ar->setBaz('bazValue');
        $ar->setNew(false);
        \Map\LazyLoadActiveRecord2TableMap::addInstanceToPool($ar, 123);
        $values = array('dummy', 'dummy', 123, 'fooValue', 'bazValue', 'dummy');
        $col = 2;
        list($obj, $col) = \Map\LazyLoadActiveRecord2TableMap::populateObject($values, $col);
        $this->assertEquals(5, $col);
        $this->assertEquals(123, $obj->getId());
        $this->assertEquals('fooValue', $obj->getFoo());
        $this->assertNull($obj->getBar());
        $this->assertEquals('bazValue', $obj->getBaz());
    }

}
