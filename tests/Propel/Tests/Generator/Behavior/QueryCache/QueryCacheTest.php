<?php

/*
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\QueryCache;

use Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Class QueryCacheTest
 *
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 *
 * @group database
 */
class QueryCacheTest extends BookstoreTestBase
{
    protected function setUp()
    {
        //prevent issue DSN not Found
        self::$isInitialized = false;
        parent::setUp();
    }

    public function testExistingKey()
    {
        $cacheTest = QuerycacheTable1Query::create()
            ->setQueryKey('test')
            ->filterByTitle('foo')
            ->find();

        $this->assertTrue(QuerycacheTable1Query::create()->cacheContains('test'), ' cache contains "test" key');
    }

    public function testPublicApiExists()
    {
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'setQueryKey'), 'setQueryKey method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'getQueryKey'), 'getQueryKey method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'cacheContains'), 'cacheContains method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'cacheFetch'), 'cacheFetch method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'cacheStore'), 'cacheStore method exists');
    }

    public function testCacheGeneratedSql()
    {
        $q = QuerycacheTable1Query::create()
            ->setQueryKey('test2')
            ->filterByTitle('bar')
        ;
        $exec = $q->find();

        $expectedSql = $this->getSql("SELECT querycache_table1.id, querycache_table1.title FROM querycache_table1 WHERE querycache_table1.title=:p1");

        $params = array();
        $this->assertTrue(QuerycacheTable1Query::create()->cacheContains('test2'), ' cache contains "test2" key');
        $this->assertEquals($expectedSql, $q->cacheFetch('test2'));
    }
}
