<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for Criteria combinations.
 *
 * @author Francois Zaninotto
 * @version $Id$
 */
class CriteriaCombineTest extends BaseTestCase
{
    /**
     * The criteria to use in the test.
     *
     * @var \Propel\Runtime\ActiveQuery\Criteria
     */
    private $c;

    /**
     * DB adapter saved for later.
     *
     * @var \Propel\Runtime\Adapter\AdapterInterface
     */
    private $savedAdapter;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        Propel::init(__DIR__ . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
        parent::setUp();
        $this->c = new ModelCriteria();
        $defaultDatasource = Propel::getServiceContainer()->getDefaultDatasource();
        $this->savedAdapter = Propel::getServiceContainer()->getAdapter($defaultDatasource);
        Propel::getServiceContainer()->setAdapter($defaultDatasource, new SqliteAdapter());
    }

    /**
     * @return string
     */
    protected function getDriver()
    {
        return 'sqlite';
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Propel::getServiceContainer()->setAdapter(Propel::getServiceContainer()->getDefaultDatasource(), $this->savedAdapter);
        parent::tearDown();
    }

    /**
     * test various properties of Criterion and nested criterion
     *
     * @return void
     */
    public function testNestedCriterion()
    {
        $table2 = 'myTable2';
        $column2 = 'myColumn2';
        $value2 = 'myValue2';
        $key2 = "$table2.$column2";

        $table3 = 'myTable3';
        $column3 = 'myColumn3';
        $value3 = 'myValue3';
        $key3 = "$table3.$column3";

        $table4 = 'myTable4';
        $column4 = 'myColumn4';
        $value4 = 'myValue4';
        $key4 = "$table4.$column4";

        $table5 = 'myTable5';
        $column5 = 'myColumn5';
        $value5 = 'myValue5';
        $key5 = "$table5.$column5";

        $crit2 = $this->c->getNewCriterion($key2, $value2, Criteria::EQUAL);
        $crit3 = $this->c->getNewCriterion($key3, $value3, Criteria::EQUAL);
        $crit4 = $this->c->getNewCriterion($key4, $value4, Criteria::EQUAL);
        $crit5 = $this->c->getNewCriterion($key5, $value5, Criteria::EQUAL);

        $crit2->addAnd($crit3)->addOr($crit4->addAnd($crit5));
        $expect = '((myTable2.myColumn2=:p1 AND myTable3.myColumn3=:p2) '
            . 'OR (myTable4.myColumn4=:p3 AND myTable5.myColumn5=:p4))';

        $sb = '';
        $params = [];
        $crit2->appendPsTo($sb, $params);

        $expect_params = [
            ['table' => 'myTable2', 'column' => 'myColumn2', 'value' => 'myValue2'],
            ['table' => 'myTable3', 'column' => 'myColumn3', 'value' => 'myValue3'],
            ['table' => 'myTable4', 'column' => 'myColumn4', 'value' => 'myValue4'],
            ['table' => 'myTable5', 'column' => 'myColumn5', 'value' => 'myValue5'],
        ];

        $this->assertEquals($expect, $sb);
        $this->assertEquals($expect_params, $params);

        $crit6 = $this->c->getNewCriterion($key2, $value2, Criteria::EQUAL);
        $crit7 = $this->c->getNewCriterion($key3, $value3, Criteria::EQUAL);
        $crit8 = $this->c->getNewCriterion($key4, $value4, Criteria::EQUAL);
        $crit9 = $this->c->getNewCriterion($key5, $value5, Criteria::EQUAL);

        $crit6->addAnd($crit7)->addOr($crit8)->addAnd($crit9);
        $expect = '(((myTable2.myColumn2=:p1 AND myTable3.myColumn3=:p2) '
            . 'OR myTable4.myColumn4=:p3) AND myTable5.myColumn5=:p4)';

        $sb = '';
        $params = [];
        $crit6->appendPsTo($sb, $params);

        $expect_params = [
            ['table' => 'myTable2', 'column' => 'myColumn2', 'value' => 'myValue2'],
            ['table' => 'myTable3', 'column' => 'myColumn3', 'value' => 'myValue3'],
            ['table' => 'myTable4', 'column' => 'myColumn4', 'value' => 'myValue4'],
            ['table' => 'myTable5', 'column' => 'myColumn5', 'value' => 'myValue5'],
        ];

        $this->assertEquals($expect, $sb);
        $this->assertEquals($expect_params, $params);

        // should make sure we have tests for all possibilities

        $crita = $crit2->getAttachedCriterion();

        $this->assertEquals($crit2, $crita[0]);
        $this->assertEquals($crit3, $crita[1]);
        $this->assertEquals($crit4, $crita[2]);
        $this->assertEquals($crit5, $crita[3]);

        $tables = $crit2->getAllTables();

        $this->assertEquals($crit2->getTable(), $tables[0]);
        $this->assertEquals($crit3->getTable(), $tables[1]);
        $this->assertEquals($crit4->getTable(), $tables[2]);
        $this->assertEquals($crit5->getTable(), $tables[3]);
    }

    /**
     * Tests <= and >=.
     *
     * @return void
     */
    public function testBetweenCriterion()
    {
        $cn1 = $this->c->getNewCriterion('INVOICE.COST', 1000, Criteria::GREATER_EQUAL);
        $cn2 = $this->c->getNewCriterion('INVOICE.COST', 5000, Criteria::LESS_EQUAL);
        $this->c->add($cn1->addAnd($cn2));

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE (INVOICE.COST>=:p1 AND INVOICE.COST<=:p2)');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => 1000],
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => 5000],
        ];

        try {
            $params = [];
            $result = $this->c->createSelectSql($params);
        } catch (PropelException $e) {
            $this->fail('PropelException thrown in Criteria->createSelectSql(): ' . $e->getMessage());
        }

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * Verify that AND and OR criterion are nested correctly.
     *
     * @return void
     */
    public function testPrecedence()
    {
        $cn1 = $this->c->getNewCriterion('INVOICE.COST', '1000', Criteria::GREATER_EQUAL);
        $cn2 = $this->c->getNewCriterion('INVOICE.COST', '2000', Criteria::LESS_EQUAL);
        $cn3 = $this->c->getNewCriterion('INVOICE.COST', '8000', Criteria::GREATER_EQUAL);
        $cn4 = $this->c->getNewCriterion('INVOICE.COST', '9000', Criteria::LESS_EQUAL);
        $this->c->add($cn1->addAnd($cn2));
        $this->c->addOr($cn3->addAnd($cn4));

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE ((INVOICE.COST>=:p1 AND INVOICE.COST<=:p2) OR (INVOICE.COST>=:p3 AND INVOICE.COST<=:p4))');

        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '9000'],
        ];

        try {
            $params = [];
            $result = $this->c->createSelectSql($params);
        } catch (PropelException $e) {
            $this->fail('PropelException thrown in Criteria::createSelectSql()');
        }

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineCriterionAndSimple()
    {
        $this->c->addCond('cond1', 'INVOICE.COST', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST', '2000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_AND);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE (INVOICE.COST>=:p1 AND INVOICE.COST<=:p2)');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '2000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineCriterionAndLessSimple()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->add('INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_AND);
        $this->c->add('INVOICE.COST4', '9000', Criteria::LESS_EQUAL);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE INVOICE.COST3>=:p1 AND (INVOICE.COST1>=:p2 AND INVOICE.COST2<=:p3) AND INVOICE.COST4<=:p4');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineCriterionAndMultiple()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->addCond('cond3', 'INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond4', 'INVOICE.COST4', '9000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2', 'cond3', 'cond4'], Criteria::LOGICAL_AND);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE (((INVOICE.COST1>=:p1 AND INVOICE.COST2<=:p2) AND INVOICE.COST3>=:p3) AND INVOICE.COST4<=:p4)');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineCriterionOrSimple()
    {
        $this->c->addCond('cond1', 'INVOICE.COST', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST', '2000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_OR);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE (INVOICE.COST>=:p1 OR INVOICE.COST<=:p2)');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST', 'value' => '2000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineCriterionOrLessSimple()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->add('INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_OR);
        $this->c->addOr('INVOICE.COST4', '9000', Criteria::LESS_EQUAL);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE INVOICE.COST3>=:p1 AND ((INVOICE.COST1>=:p2 OR INVOICE.COST2<=:p3) OR INVOICE.COST4<=:p4)');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineCriterionOrMultiple()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->addCond('cond3', 'INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond4', 'INVOICE.COST4', '9000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2', 'cond3', 'cond4'], Criteria::LOGICAL_OR);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE (((INVOICE.COST1>=:p1 OR INVOICE.COST2<=:p2) OR INVOICE.COST3>=:p3) OR INVOICE.COST4<=:p4)');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineNamedCriterions()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_AND, 'cond12');
        $this->c->addCond('cond3', 'INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond4', 'INVOICE.COST4', '9000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond3', 'cond4'], Criteria::LOGICAL_AND, 'cond34');
        $this->c->combine(['cond12', 'cond34'], Criteria::LOGICAL_OR);

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE ((INVOICE.COST1>=:p1 AND INVOICE.COST2<=:p2) OR (INVOICE.COST3>=:p3 AND INVOICE.COST4<=:p4))');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testCombineDirtyOperators()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2'], 'AnD', 'cond12');
        $this->c->addCond('cond3', 'INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond4', 'INVOICE.COST4', '9000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond3', 'cond4'], 'aNd', 'cond34');
        $this->c->combine(['cond12', 'cond34'], 'oR');

        $expect = $this->getSql('SELECT  FROM INVOICE WHERE ((INVOICE.COST1>=:p1 AND INVOICE.COST2<=:p2) OR (INVOICE.COST3>=:p3 AND INVOICE.COST4<=:p4))');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params = [];
        $result = $this->c->createSelectSql($params);

        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $params);
    }

    /**
     * @return void
     */
    public function testClonedCriteriaNotAffected()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_AND, 'cond12');
        $this->c->addCond('cond3', 'INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond4', 'INVOICE.COST4', '9000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond3', 'cond4'], Criteria::LOGICAL_AND, 'cond34');

        $clonedCriteria = clone $this->c;

        $expect1 = $this->getSql('SELECT  FROM ');

        $params1 = [];
        $result1 = $this->c->createSelectSql($params1);

        $this->assertEquals($expect1, $result1);

        $this->c->combine(['cond12', 'cond34'], Criteria::LOGICAL_OR);

        $expect2 = $this->getSql('SELECT  FROM INVOICE WHERE ((INVOICE.COST1>=:p1 AND INVOICE.COST2<=:p2) OR (INVOICE.COST3>=:p3 AND INVOICE.COST4<=:p4))');
        $expect_params = [
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params2 = [];
        $result2 = $this->c->createSelectSql($params2);

        $this->assertEquals($expect2, $result2);
        $this->assertEquals($expect_params, $params2);

        // Cloned criteria should not be affected

        $params3 = [];
        $result3 = $clonedCriteria->createSelectSql($params3);

        $this->assertEquals($expect1, $result3);
    }

    /**
     * @return void
     */
    public function testClonedCriteriaNotAffected2()
    {
        $this->c->addCond('cond1', 'INVOICE.COST1', '1000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond2', 'INVOICE.COST2', '2000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond1', 'cond2'], Criteria::LOGICAL_AND, 'cond12');
        $this->c->addCond('cond3', 'INVOICE.COST3', '8000', Criteria::GREATER_EQUAL);
        $this->c->addCond('cond4', 'INVOICE.COST4', '9000', Criteria::LESS_EQUAL);
        $this->c->combine(['cond3', 'cond4'], Criteria::LOGICAL_AND, 'cond34');

        $clonedCriteria = clone $this->c;

        $expect1 = $this->getSql('SELECT  FROM ');

        $params1 = [];
        $result1 = $this->c->createSelectSql($params1);

        $this->assertEquals($expect1, $result1);

        $this->c->addCond('cond5', 'INVOICE.COST5', '5000', Criteria::GREATER_EQUAL);
        $this->c->combine(['cond34', 'cond5'], Criteria::LOGICAL_AND);

        $expect2 = $this->getSql('SELECT  FROM INVOICE WHERE ((INVOICE.COST3>=:p1 AND INVOICE.COST4<=:p2) AND INVOICE.COST5>=:p3)');
        $expect_params2 = [
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
            ['table' => 'INVOICE', 'column' => 'COST5', 'value' => '5000'],
        ];

        $params2 = [];
        $result2 = $this->c->createSelectSql($params2);

        $this->assertEquals($expect2, $result2);
        $this->assertEquals($expect_params2, $params2);

        // Cloned criteria should not be affected by the combine of cond34 above
        // we should still be able to use it in the clone for another combine

        $clonedCriteria->combine(['cond12', 'cond34'], Criteria::LOGICAL_OR);

        $expect3 = $this->getSql('SELECT  FROM INVOICE WHERE ((INVOICE.COST1>=:p1 AND INVOICE.COST2<=:p2) OR (INVOICE.COST3>=:p3 AND INVOICE.COST4<=:p4))');
        $expect_params3 = [
            ['table' => 'INVOICE', 'column' => 'COST1', 'value' => '1000'],
            ['table' => 'INVOICE', 'column' => 'COST2', 'value' => '2000'],
            ['table' => 'INVOICE', 'column' => 'COST3', 'value' => '8000'],
            ['table' => 'INVOICE', 'column' => 'COST4', 'value' => '9000'],
        ];

        $params3 = [];
        $result3 = $clonedCriteria->createSelectSql($params3);

        $this->assertEquals($expect3, $result3);
        $this->assertEquals($expect_params3, $params3);
    }
}
