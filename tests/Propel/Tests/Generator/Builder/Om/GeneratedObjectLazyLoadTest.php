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
 * Tests the generated Object classes for lazy load columns.
 *
 */
class GeneratedObjectLazyLoadTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('LazyLoadActiveRecord')) {
            $schema = <<<EOF
<database name="lazy_load_active_record_1">
    <table name="lazy_load_active_record">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="foo" type="VARCHAR" size="100" />
        <column name="bar" type="VARCHAR" size="100" lazyLoad="true" />
        <column name="baz" type="VARCHAR" size="100" defaultValue="world" lazyLoad="true" />
    </table>
</database>
EOF;
            //QuickBuilder::debugClassesForTable($schema, 'lazy_load_active_record');
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testNormalColumnsRequireNoQueryOnGetter()
    {
        $con = Propel::getServiceContainer()->getConnection(\Map\LazyLoadActiveRecordTableMap::DATABASE_NAME);
        $con->useDebug(true);
        $obj = new \LazyLoadActiveRecord();
        $obj->setFoo('hello');
        $obj->save($con);
        \Map\LazyLoadActiveRecordTableMap::clearInstancePool();
        $obj2 = \LazyLoadActiveRecordQuery::create()->findPk($obj->getId(), $con);
        $count = $con->getQueryCount();
        $this->assertEquals('hello', $obj2->getFoo());
        $this->assertEquals($count, $con->getQueryCount());
    }

    public function testLazyLoadedColumnsRequireAnAdditionalQueryOnGetter()
    {
        $con = Propel::getServiceContainer()->getConnection(\Map\LazyLoadActiveRecordTableMap::DATABASE_NAME);
        $con->useDebug(true);
        $obj = new \LazyLoadActiveRecord();
        $obj->setBar('hello');
        $obj->save($con);
        \Map\LazyLoadActiveRecordTableMap::clearInstancePool();
        $obj2 = \LazyLoadActiveRecordQuery::create()->findPk($obj->getId(), $con);
        $count = $con->getQueryCount();
        $this->assertEquals('hello', $obj2->getBar($con));
        $this->assertEquals($count + 1, $con->getQueryCount());
    }

    public function testLazyLoadedColumnsWithDefaultRequireAnAdditionalQueryOnGetter()
    {
        $con = Propel::getServiceContainer()->getConnection(\Map\LazyLoadActiveRecordTableMap::DATABASE_NAME);
        $con->useDebug(true);
        $obj = new \LazyLoadActiveRecord();
        $obj->setBaz('hello');
        $obj->save($con);
        \Map\LazyLoadActiveRecordTableMap::clearInstancePool();
        $obj2 = \LazyLoadActiveRecordQuery::create()->findPk($obj->getId(), $con);
        $count = $con->getQueryCount();
        $this->assertEquals('hello', $obj2->getBaz($con));
        $this->assertEquals($count + 1, $con->getQueryCount());
    }
}
