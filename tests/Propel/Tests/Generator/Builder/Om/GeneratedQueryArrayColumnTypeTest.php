<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use ComplexColumnTypeEntity11;
use ComplexColumnTypeEntity11Query;
use PHPUnit\Framework\TestCase;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Tests the generated queries for array column types filters
 *
 * @author Francois Zaninotto
 */
class GeneratedQueryArrayColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\ComplexColumnTypeEntity11')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_11">
    <table name="complex_column_type_entity_11">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="tags" type="ARRAY"/>
        <column name="value_set" type="ARRAY"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            $e0 = new ComplexColumnTypeEntity11();
            $e0->save();
            $e1 = new ComplexColumnTypeEntity11();
            $e1->setTags(['foo', 'bar', 'baz']);
            $e1->save();
            $e2 = new ComplexColumnTypeEntity11();
            $e2->setTags(['bar']);
            $e2->save();
            $e3 = new ComplexColumnTypeEntity11();
            $e3->setTags(['bar23']);
            $e3->save();
        }
    }

    /**
     * @return void
     */
    public function testActiveQueryMethods()
    {
        $this->assertTrue(method_exists('\ComplexColumnTypeEntity11Query', 'filterByTags'));
        $this->assertTrue(method_exists('\ComplexColumnTypeEntity11Query', 'filterByTag'));
        // only plural column names get a singular filter
        $this->assertTrue(method_exists('\ComplexColumnTypeEntity11Query', 'filterByValueSet'));
    }

    /**
     * @return void
     */
    public function testColumnHydration()
    {
        $e = ComplexColumnTypeEntity11Query::create()->orderById()->offset(1)->findOne();
        $this->assertEquals(['foo', 'bar', 'baz'], $e->getTags(), 'array columns are correctly hydrated');
    }

    /**
     * @return void
     */
    public function testWhere()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->where('ComplexColumnTypeEntity11.Tags LIKE ?', '%| bar23 |%')
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags(), 'array columns are searchable by serialized object using where()');
        $e = ComplexColumnTypeEntity11Query::create()
            ->where('ComplexColumnTypeEntity11.Tags = ?', ['bar23'])
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags(), 'array columns are searchable by object using where()');
    }

    /**
     * @return void
     */
    public function testFilterByColumn()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar'])
            ->orderById()
            ->find();
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(['bar'], $e[1]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(2, $e->count(), 'array columns do not return false positives');
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar23'])
            ->findOne();
        $this->assertEquals(['bar23'], $e->getTags(), 'array columns are searchable by element');
    }

    /**
     * @return void
     */
    public function testFilterByColumnUsingContainsAll()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags([], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(4, $e->count());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar'], Criteria::CONTAINS_ALL)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar23'], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['foo', 'bar'], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['foo', 'bar23'], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(0, $e->count());
    }

    /**
     * @return void
     */
    public function testFilterByColumnUsingContainsSome()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags([], Criteria::CONTAINS_SOME)
            ->find();
        $this->assertEquals(4, $e->count());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar'], Criteria::CONTAINS_SOME)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar23'], Criteria::CONTAINS_SOME)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['foo', 'bar'], Criteria::CONTAINS_SOME)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['foo', 'bar23'], Criteria::CONTAINS_SOME)
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar23'], $e[1]->getTags());
    }

    /**
     * @return void
     */
    public function testFilterByColumnUsingContainsNone()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags([], Criteria::CONTAINS_NONE)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar'], Criteria::CONTAINS_NONE)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['bar23'], $e[1]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['bar23'], Criteria::CONTAINS_NONE)
            ->find();
        $this->assertEquals(3, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[1]->getTags());
        $this->assertEquals(['bar'], $e[2]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['foo', 'bar'], Criteria::CONTAINS_NONE)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['bar23'], $e[1]->getTags());
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTags(['foo', 'bar23'], Criteria::CONTAINS_NONE)
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
    }

    /**
     * @return void
     */
    public function testFilterBySingularColumn()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTag('bar')
            ->orderById()
            ->find();
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(['bar'], $e[1]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(2, $e->count(), 'array columns do not return false positives');
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTag('bar23')
            ->findOne();
        $this->assertEquals(['bar23'], $e->getTags(), 'array columns are searchable by element');
    }

    /**
     * @return void
     */
    public function testFilterBySingularColumnUsingContainsAll()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTag('bar', Criteria::CONTAINS_ALL)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::CONTAINS_ALL');
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS_ALL');
        $this->assertEquals(['bar'], $e[1]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS_ALL');
    }

    /**
     * @return void
     */
    public function testFilterBySingularColumnUsingContainsNone()
    {
        $e = ComplexColumnTypeEntity11Query::create()
            ->filterByTag('bar', Criteria::CONTAINS_NONE)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::CONTAINS_NONE');
        $this->assertEquals([], $e[0]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS_NONE');
        $this->assertEquals(['bar23'], $e[1]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS_NONE');
    }
}
