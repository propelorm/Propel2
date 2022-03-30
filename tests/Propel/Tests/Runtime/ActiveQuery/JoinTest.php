<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Propel;
use Propel\Tests\Helpers\BaseTestCase;
use Propel\Runtime\Adapter\AdapterInterface;

/**
 * Test class for Join.
 *
 * @author François Zaninotto
 * @version $Id$
 */
class JoinTest extends BaseTestCase
{
    /**
     * DB adapter saved for later.
     *
     * @var AdapterInterface
     */
    private $savedAdapter;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php';
        parent::setUp();

        $this->savedAdapter = Propel::getServiceContainer()->getAdapter(null);
        Propel::getServiceContainer()->setAdapter('', new SqliteAdapter());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Propel::getServiceContainer()->setAdapter('', $this->savedAdapter);
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testEmptyConditions()
    {
        $j = new Join();
        $this->assertEquals([], $j->getConditions());
    }

    /**
     * @return void
     */
    public function testAddCondition()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar');
        $this->assertEquals('=', $j->getOperator());
        $this->assertEquals('foo', $j->getLeftColumn());
        $this->assertEquals('bar', $j->getRightColumn());
    }

    /**
     * @return void
     */
    public function testGetConditions()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar');
        $expect = [['left' => 'foo', 'operator' => '=', 'right' => 'bar']];
        $this->assertEquals($expect, $j->getConditions());
    }

    /**
     * @return void
     */
    public function testAddConditionWithOperator()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar', '>=');
        $expect = [['left' => 'foo', 'operator' => '>=', 'right' => 'bar']];
        $this->assertEquals($expect, $j->getConditions());
    }

    /**
     * @return void
     */
    public function testAddConditions()
    {
        $j = new Join();
        $j->addCondition('foo', 'bar');
        $j->addCondition('baz', 'bal');
        $expect = [
            ['left' => 'foo', 'operator' => '=', 'right' => 'bar'],
            ['left' => 'baz', 'operator' => '=', 'right' => 'bal'],
        ];
        $this->assertEquals(['=', '='], $j->getOperators());
        $this->assertEquals(['foo', 'baz'], $j->getLeftColumns());
        $this->assertEquals(['bar', 'bal'], $j->getRightColumns());
        $this->assertEquals($expect, $j->getConditions());
    }

    /**
     * @return void
     */
    public function testAddExplicitConditionWithoutAlias()
    {
        $j = new Join();
        $j->addExplicitCondition('a', 'foo', null, 'b', 'bar', null);
        $this->assertEquals('=', $j->getOperator());
        $this->assertEquals('a.foo', $j->getLeftColumn());
        $this->assertEquals('b.bar', $j->getRightColumn());
        $this->assertEquals('a', $j->getLeftTableName());
        $this->assertEquals('b', $j->getRightTableName());
        $this->assertNull($j->getLeftTableAlias());
        $this->assertNull($j->getRightTableAlias());
        $this->assertEquals(1, $j->countConditions());
    }

    /**
     * @return void
     */
    public function testAddExplicitconditionWithOneAlias()
    {
        $j = new Join();
        $j->setJoinType(Criteria::LEFT_JOIN);
        $j->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID', 'a', Join::EQUAL);
        $params = [];
        $this->assertEquals($j->getClause($params), 'LEFT JOIN author a ON (book.AUTHOR_ID=a.ID)');
    }

    //used in polymorphic relation

    /**
     * @return void
     */
    public function testConditionalJoin()
    {
        $j = new Join();
        $j->setJoinType(Criteria::LEFT_JOIN);
        $j->addExplicitCondition('log', 'AUTHOR_ID', null, 'author', 'ID', 'a', Join::EQUAL);
        $j->addLocalValueCondition('log', 'target_type', null, 'author', Join::EQUAL);
        $params = [];
        $this->assertEquals('LEFT JOIN author a ON (log.AUTHOR_ID=a.ID AND log.target_type=\'author\')', $j->getClause($params));
    }

    /**
     * @return void
     */
    public function testAddExplicitConditionWithAlias()
    {
        $j = new Join();
        $j->addExplicitCondition('a', 'foo', 'Alias', 'b', 'bar', 'Blias');
        $this->assertEquals('=', $j->getOperator());
        $this->assertEquals('Alias.foo', $j->getLeftColumn());
        $this->assertEquals('Blias.bar', $j->getRightColumn());
        $this->assertEquals('a', $j->getLeftTableName());
        $this->assertEquals('b', $j->getRightTableName());
        $this->assertEquals('Alias', $j->getLeftTableAlias());
        $this->assertEquals('Blias', $j->getRightTableAlias());
    }

    /**
     * @return void
     */
    public function testAddExplicitConditionWithOperator()
    {
        $j = new Join();
        $j->addExplicitCondition('a', 'foo', null, 'b', 'bar', null, '>=');
        $this->assertEquals('>=', $j->getOperator());
        $this->assertEquals('a.foo', $j->getLeftColumn());
        $this->assertEquals('b.bar', $j->getRightColumn());
    }

    /**
     * @return void
     */
    public function testJoinDefaultsToInnerJoin()
    {
        $j = new Join();
        $this->assertEquals(Join::INNER_JOIN, $j->getJoinType());
    }

    /**
     * @return void
     */
    public function testSetJoinTypeThroughSetter()
    {
        $j = new Join();
        $j->setJoinType('foo');
        $this->assertEquals('foo', $j->getJoinType());
    }

    /**
     * @return void
     */
    public function testSetJoinTypeThroughConstructor()
    {
        $j = new Join('aCol', 'bCol', 'fooJoin');
        $this->assertEquals('fooJoin', $j->getJoinType());
    }

    /**
     * @return void
     */
    public function testSimpleConstructor()
    {
        $j = new Join('foo', 'bar', 'LEFT JOIN');
        $expect = [['left' => 'foo', 'operator' => '=', 'right' => 'bar']];
        $this->assertEquals($expect, $j->getConditions());
        $this->assertEquals('LEFT JOIN', $j->getJoinType());
    }

    /**
     * @return void
     */
    public function testCompositeConstructor()
    {
        $j = new Join(['foo1', 'foo2'], ['bar1', 'bar2'], 'LEFT JOIN');
        $expect = [
            ['left' => 'foo1', 'operator' => '=', 'right' => 'bar1'],
            ['left' => 'foo2', 'operator' => '=', 'right' => 'bar2'],
        ];
        $this->assertEquals($expect, $j->getConditions());
        $this->assertEquals('LEFT JOIN', $j->getJoinType());
    }

    /**
     * @return void
     */
    public function testCountConditions()
    {
        $j = new Join();
        $this->assertEquals(0, $j->countConditions());
        $j->addCondition('foo', 'bar');
        $this->assertEquals(1, $j->countConditions());
        $j->addCondition('foo1', 'bar1');
        $this->assertEquals(2, $j->countConditions());
    }

    /**
     * @return void
     */
    public function testEquality()
    {
        $j1 = new Join('foo', 'bar', 'INNER JOIN');
        $j2 = new Join('foo', 'bar', 'LEFT JOIN');
        $this->assertFalse($j1->equals($j2), 'INNER JOIN and LEFT JOIN are not equal');

        $j3 = new Join('foo', 'bar', 'INNER JOIN');
        $j3->addCondition('baz.foo', 'baz.bar');
        $this->assertFalse($j1->equals($j3), 'Joins with different conditions are not equal');

        $j4 = new Join('foo', 'bar', 'INNER JOIN');
        $j4->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID', 'a', Join::EQUAL);
        $this->assertFalse($j1->equals($j4), 'Joins with different clauses not equal');

        $j5 = new Join('foo', 'bar');
        $j6 = new Join('foo', 'bar');
        $this->assertTrue($j5->equals($j6), 'Joins without specified join type should be equal as they fallback to default join type');
    }

    /**
     * @return void
     */
    public function testJoinResovesQualifiedColumnNames()
    {
        $j = new Join('a.colA', 'b.colB');

        $this->assertEquals('a.colA', $j->getLeftColumn());
        $this->assertEquals('a', $j->getLeftTableName());
        $this->assertEquals('colA', $j->getLeftColumnName());

        $this->assertEquals('b.colB', $j->getRightColumn());
        $this->assertEquals('b', $j->getRightTableName());
        $this->assertEquals('colB', $j->getRightColumnName());
    }

    /**
     * @return void
     */
    public function testJoinObject()
    {
        $j = new Join(['a.colA0', 'a.colA1'], ['b.colB0', 'b.colB1'], Criteria::INNER_JOIN);
        $this->assertEquals('a.colA0', $j->getLeftColumn(0));
        $this->assertEquals('a.colA1', $j->getLeftColumn(1));
        $this->assertEquals('b.colB0', $j->getRightColumn(0));
        $this->assertEquals('b.colB1', $j->getRightColumn(1));
    }
}
