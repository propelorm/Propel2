<?php

/*
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\QueryCache;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Class QueryCacheTest
 *
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 *
 */
class QueryCacheTest extends TestCase
{
    protected function setUp()
    {
        if (!class_exists('\QuerycacheEntity1')) {
            $schema = <<<XML
<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>
<database name="QueryCacheTest" defaultIdMethod="native" activeRecord="true">

    <entity name="QuerycacheEntity1">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />

        <behavior name="query_cache">
            <parameter name="backend" value="array" />
        </behavior>
    </entity>

</database>            
XML;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testExistingKey()
    {
        $cacheTest = \QuerycacheEntity1Query::create()
            ->setQueryKey('test')
            ->filterByTitle('foo')
            ->find();

       $this->assertTrue(\QuerycacheEntity1Query::create()->cacheContains('test'), ' cache contains "test" key');
    }

    public function testPublicApiExists()
    {
        $this->assertTrue(method_exists('\QuerycacheEntity1Query', 'setQueryKey'), 'setQueryKey method exists');
        $this->assertTrue(method_exists('\QuerycacheEntity1Query', 'getQueryKey'), 'getQueryKey method exists');
        $this->assertTrue(method_exists('\QuerycacheEntity1Query', 'cacheContains'), 'cacheContains method exists');
        $this->assertTrue(method_exists('\QuerycacheEntity1Query', 'cacheFetch'), 'cacheFetch method exists');
        $this->assertTrue(method_exists('\QuerycacheEntity1Query', 'cacheStore'), 'cacheStore method exists');
    }

    public function testCacheGeneratedSql()
    {
        $q = \QuerycacheEntity1Query::create()
            ->setQueryKey('test2')
            ->filterByTitle('bar')
        ;
        $exec = $q->find();

        $expectedSql = $this->getSql("SELECT querycache_entity1.id, querycache_entity1.title FROM querycache_entity1 WHERE querycache_entity1.title=:p1");

        $this->assertTrue(\QuerycacheEntity1Query::create()->cacheContains('test2'), ' cache contains "test2" key');
        $this->assertEquals($expectedSql, $q->cacheFetch('test2'));
    }

    public function testDoCount()
    {
        $q = \QuerycacheEntity1Query::create()
            ->setQueryKey('test3')
            ->filterByTitle('bar')
        ;
        $exec = $q->count();

        $expectedSql = $this->getSql("SELECT COUNT(*) FROM querycache_entity1 WHERE querycache_entity1.title=:p1");

        $this->assertTrue(\QuerycacheEntity1Query::create()->cacheContains('test3'), ' cache contains "test3" key');
        $this->assertEquals($expectedSql, $q->cacheFetch('test3'));
    }

    public function testQueryIsNotCachedIfExceptionIsThrown()
    {
        $q = \QuerycacheEntity1Query::create()->setQueryKey('test4')->filterByTitle('bar');

        try {
            $q->withField('wrongField')->find();
        } catch (\Exception $e) {
            $this->assertTrue(true, 'The exception is correctly thrown');
        }

        $this->assertNull($q->cacheFetch('test4'), 'The query is not cached,  if it has thrown exception');
    }
}
