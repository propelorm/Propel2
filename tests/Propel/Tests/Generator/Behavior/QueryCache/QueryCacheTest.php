<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\QueryCache;

use Exception;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Propel;
use Propel\Runtime\Util\PropelModelPager;
use Propel\Tests\Bookstore\Behavior\QuerycacheTable1;
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
    /**
     * @return void
     */
    protected function setUp(): void
    {
        //prevent issue DSN not Found
        self::$isInitialized = false;
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testExistingKey()
    {
        $cacheTest = QuerycacheTable1Query::create()
            ->setQueryKey('test')
            ->filterByTitle('foo')
            ->find();

        $this->assertTrue(QuerycacheTable1Query::create()->cacheContains('test'), ' cache contains "test" key');
    }

    /**
     * @return void
     */
    public function testPublicApiExists()
    {
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'setQueryKey'), 'setQueryKey method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'getQueryKey'), 'getQueryKey method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'cacheContains'), 'cacheContains method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'cacheFetch'), 'cacheFetch method exists');
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\Behavior\QuerycacheTable1Query', 'cacheStore'), 'cacheStore method exists');
    }

    /**
     * @return void
     */
    public function testCacheGeneratedSql()
    {
        $q = QuerycacheTable1Query::create()
            ->setQueryKey('test2')
            ->filterByTitle('bar');
        $exec = $q->find();

        $expectedSql = $this->getSql('SELECT querycache_table1.id, querycache_table1.title FROM querycache_table1 WHERE querycache_table1.title=:p1');

        $params = [];
        $this->assertTrue(QuerycacheTable1Query::create()->cacheContains('test2'), ' cache contains "test2" key');
        $this->assertEquals($expectedSql, $q->cacheFetch('test2'));
    }

    /**
     * @return void
     */
    public function testSimpleCountSql()
    {
        $con = Propel::getConnection();
        $con->useDebug(true);

        $exec = QuerycacheTable1Query::create()
            ->count($con);

        $expectedSql = $this->getSql('SELECT COUNT(*) FROM querycache_table1');
        $renderedSql = Propel::getConnection()->getLastExecutedQuery();

        $this->assertEquals($expectedSql, $renderedSql);
    }

    /**
     * @return void
     */
    public function testWithPaginate()
    {
        QuerycacheTable1Query::create()->deleteAll();
        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Behavior\QuerycacheTable1');
        for ($i = 0; $i < 5; $i++) {
            $b = new QuerycacheTable1();
            $b->setTitle('Title' . $i);

            $coll[] = $b;
        }
        $coll->save();

        $pager = $this->getPager(2, 1);
        $this->assertEquals(5, $pager->getNbResults());

        $results = $pager->getResults();
        $this->assertEquals('query cache with paginate offset 0 limit 2', $pager->getQuery()->getQueryKey());
        $this->assertEquals(2, count($results));
        $this->assertEquals('Title1', $results[1]->getTitle());

        //jump to page 3
        $pager = $this->getPager(2, 3);
        $this->assertEquals(5, $pager->getNbResults());

        $results = $pager->getResults();
        $this->assertEquals('query cache with paginate offset 4 limit 2', $pager->getQuery()->getQueryKey());
        $this->assertEquals(1, count($results));
        $this->assertEquals('Title4', $results[0]->getTitle());
    }

    /**
     * @return void
     */
    public function testQueryIsNotCachedIfExceptionIsThrown()
    {
        $q = QuerycacheTable1Query::create()->setQueryKey('test4')->filterByTitle('bar');

        try {
            $q->withField('wrongField')->find();
        } catch (Exception $e) {
            $this->assertTrue(true, 'The exception is correctly thrown');
        }

        $this->assertNull($q->cacheFetch('test4'), 'The query is not cached,  if it has thrown exception');
    }

    protected function getPager($maxPerPage, $page = 1)
    {
        $query = QuerycacheTable1Query::create()
            ->setQueryKey('query cache with paginate')
            ->orderByTitle();

        $pager = new PropelModelPager($query, $maxPerPage);
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }
}
