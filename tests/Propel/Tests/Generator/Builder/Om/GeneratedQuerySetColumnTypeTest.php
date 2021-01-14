<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use ComplexColumnTypeEntitySet2;
use ComplexColumnTypeEntitySet2Query;
use PHPUnit\Framework\TestCase;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;

/**
 * Tests the generated queries for array column types filters
 *
 * @author Francois Zaninotto
 */
class GeneratedQuerySetColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\ComplexColumnTypeEntitySet2')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_set_2">
    <table name="complex_column_type_entity_set_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="tags" valueSet="foo, bar, baz, bar23" type="SET"/>
        <column name="value_set" valueSet="foo, bar, baz, kevin" type="SET"/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            $e0 = new ComplexColumnTypeEntitySet2();
            $e0->save();
            $e1 = new ComplexColumnTypeEntitySet2();
            $e1->setTags(['foo', 'bar', 'baz']);
            $e1->save();
            $e2 = new ComplexColumnTypeEntitySet2();
            $e2->setTags(['bar']);
            $e2->save();
            $e3 = new ComplexColumnTypeEntitySet2();
            $e3->setTags(['bar23']);
            $e3->save();
        }
    }

    /**
     * @return void
     */
    public function testActiveQueryMethods()
    {
        $this->assertTrue(method_exists('\ComplexColumnTypeEntitySet2Query', 'filterByTags'));
        $this->assertTrue(method_exists('\ComplexColumnTypeEntitySet2Query', 'filterByTag'));
        // only plural column names get a singular filter
        $this->assertTrue(method_exists('\ComplexColumnTypeEntitySet2Query', 'filterByValueSet'));
    }

    /**
     * @return void
     */
    public function testColumnHydration()
    {
        $e = ComplexColumnTypeEntitySet2Query::create()->orderById()->offset(1)->findOne();
        $this->assertEquals(['foo', 'bar', 'baz'], $e->getTags(), 'array columns are correctly hydrated');
    }

    /**
     * @return void
     */
    public function testWhere()
    {
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags LIKE ?', 'bar23')
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags(), 'set columns are searchable by single value using where()');

        $e = ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags LIKE ?', ['foo', 'bar', 'baz'])
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags(), 'set columns are searchable by multiple values using where()');

        $e = ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags IN ?', ['baz', 'bar23'])
            ->find();
        $this->assertEquals(2, $e->count(), 'set columns are searchable by multiple values using where()');

        $e = ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags NOT IN ?', ['baz', 'bar23'])
            ->find();
        $this->assertEquals(1, $e->count(), 'set columns are searchable by multiple values using where()');

        $e = ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags NOT IN ?', null)
            ->find();
        $this->assertEquals(4, $e->count(), 'set columns are searchable by multiple values using where()');

        $e = ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags IN ?', null)
            ->find();
        $this->assertEquals(0, $e->count(), 'set columns are searchable by multiple values using where()');
    }

    /**
     * @return void
     */
    public function testWhereInvalidValueThrowsException()
    {
        $this->expectException(PropelException::class);

        ComplexColumnTypeEntitySet2Query::create()
            ->where('ComplexColumnTypeEntitySet2.Tags LIKE ?', 'bar231')
            ->find();
    }

    /**
     * @return void
     */
    public function testFilterByColumn()
    {
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar'])
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count(), 'array columns do not return false positives');
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(['bar'], $e[1]->getTags(), 'array columns are searchable by element');
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar23'])
            ->findOne();
        $this->assertEquals(['bar23'], $e->getTags(), 'array columns are searchable by element');
    }

    /**
     * @return void
     */
    public function testFilterByColumnUsingContainsAll()
    {
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags([], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(4, $e->count());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar'], Criteria::CONTAINS_ALL)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar23'], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['foo', 'bar'], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['foo', 'bar23'], Criteria::CONTAINS_ALL)
            ->find();
        $this->assertEquals(0, $e->count());
    }

    /**
     * @return void
     */
    public function testFilterByColumnUsingContainsSome()
    {
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags([], Criteria::CONTAINS_SOME)
            ->find();
        $this->assertEquals(4, $e->count());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar'], Criteria::CONTAINS_SOME)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar23'], Criteria::CONTAINS_SOME)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals(['bar23'], $e[0]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['foo', 'bar'], Criteria::CONTAINS_SOME)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags());
        $this->assertEquals(['bar'], $e[1]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
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
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags([], Criteria::CONTAINS_NONE)
            ->find();
        $this->assertEquals(1, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar'], Criteria::CONTAINS_NONE)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['bar23'], $e[1]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['bar23'], Criteria::CONTAINS_NONE)
            ->find();
        $this->assertEquals(3, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['foo', 'bar', 'baz'], $e[1]->getTags());
        $this->assertEquals(['bar'], $e[2]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTags(['foo', 'bar'], Criteria::CONTAINS_NONE)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count());
        $this->assertEquals([], $e[0]->getTags());
        $this->assertEquals(['bar23'], $e[1]->getTags());
        $e = ComplexColumnTypeEntitySet2Query::create()
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
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTag('bar')
            ->orderById()
            ->find();
        $this->assertEquals(['foo', 'bar', 'baz'], $e[0]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(['bar'], $e[1]->getTags(), 'array columns are searchable by element');
        $this->assertEquals(2, $e->count(), 'array columns do not return false positives');
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTag('bar23')
            ->findOne();
        $this->assertEquals(['bar23'], $e->getTags(), 'array columns are searchable by element');
    }

    /**
     * @return void
     */
    public function testFilterBySingularColumnUsingContainsAll()
    {
        $e = ComplexColumnTypeEntitySet2Query::create()
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
        $e = ComplexColumnTypeEntitySet2Query::create()
            ->filterByTag('bar', Criteria::CONTAINS_NONE)
            ->orderById()
            ->find();
        $this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::CONTAINS_NONE');
        $this->assertEquals([], $e[0]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS_NONE');
        $this->assertEquals(['bar23'], $e[1]->getTags(), 'array columns are searchable by element using Criteria::CONTAINS_NONE');
    }
}
