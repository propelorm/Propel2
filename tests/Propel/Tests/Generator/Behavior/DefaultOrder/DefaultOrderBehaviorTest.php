<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\DefaultOrder;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\Bookstore\Behavior\DefaultOrder1;
use Propel\Tests\Bookstore\Behavior\DefaultOrder1Query;
use Propel\Tests\Bookstore\Behavior\DefaultOrder2;
use Propel\Tests\Bookstore\Behavior\DefaultOrder2Query;
use Propel\Tests\Bookstore\Behavior\Map\DefaultOrder1TableMap;
use Propel\Tests\Bookstore\Behavior\Map\DefaultOrder2TableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for DefaultOrderBehavior class
 *
 * @author Gregor Harlan
 */
class DefaultOrderBehaviorTest extends BookstoreTestBase
{
    public function testSingleColumn()
    {
        DefaultOrder1TableMap::doDeleteAll();

        $obj1 = new DefaultOrder1();
        $obj1->setTitle('b')->save();
        $obj2 = new DefaultOrder1();
        $obj2->setTitle('ab')->save();
        $obj3 = new DefaultOrder1();
        $obj3->setTitle('ac')->save();

        $expected = new ObjectCollection();
        $expected->append($obj2);
        $expected->append($obj3);
        $expected->append($obj1);

        $objects = DefaultOrder1Query::create()->find();

        $this->assertEquals($expected->toArray(), $objects->toArray());
    }

    public function testMultipleColumn()
    {
        DefaultOrder2TableMap::doDeleteAll();

        $obj1 = new DefaultOrder2();
        $obj1->setTitle('b')->save();
        $obj2 = new DefaultOrder2();
        $obj2->setTitle('ac')->save();
        $obj3 = new DefaultOrder2();
        $obj3->setTitle('ab')->save();
        $obj4 = new DefaultOrder2();
        $obj4->setTitle('ac')->save();

        $expected = new ObjectCollection();
        $expected->append($obj3);
        $expected->append($obj4);
        $expected->append($obj2);
        $expected->append($obj1);

        $objects = DefaultOrder2Query::create()->find();

        $this->assertEquals($expected->toArray(), $objects->toArray());
    }
}
