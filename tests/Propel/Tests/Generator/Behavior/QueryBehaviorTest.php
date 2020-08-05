<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior;

use Propel\Tests\Bookstore\Behavior\Base\testQueryFilter;
use Propel\Tests\Bookstore\Behavior\Table3Query;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests the generated query behavior hooks.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class QueryBehaviorTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testStaticAttributes()
    {
        $this->assertEquals(Table3Query::$customStaticAttribute, 1, 'staticAttributes hook is called when adding attributes');
        $this->assertEquals(
            'Propel\Generator\Builder\Om\QueryBuilder',
            Table3Query::$staticAttributeBuilder,
            'staticAttributes hook is called with the query builder as parameter'
        );
    }

    /**
     * @return void
     */
    public function testStaticMethods()
    {
        $this->assertTrue(
            method_exists('\Propel\Tests\Bookstore\Behavior\Table3Query', 'hello'),
            'staticMethods hook is called when adding methods'
        );
        $this->assertEquals(
            'Propel\Generator\Builder\Om\QueryBuilder',
            Table3Query::hello(),
            'staticMethods hook is called with the query builder as parameter'
        );
    }

    /**
     * @return void
     */
    public function testQueryFilter()
    {
        class_exists('Propel\Tests\Bookstore\Behavior\Table3Query'); //trigger autoload
        $this->assertTrue(
            class_exists('Propel\Tests\Bookstore\Behavior\Base\testQueryFilter'),
            'queryFilter hook allows complete manipulation of the generated script'
        );
        $this->assertEquals(
            'Propel\Generator\Builder\Om\QueryBuilder',
            testQueryFilter::FOO,
            'queryFilter hook is called with the query builder as parameter'
        );
    }
}
