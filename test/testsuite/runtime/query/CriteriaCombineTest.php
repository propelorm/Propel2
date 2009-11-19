<?php

require_once 'tools/helpers/BaseTestCase.php';
require_once 'query/Criteria.php';
require_once 'util/BasePeer.php';

set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');

/**
 * Test class for Criteria combinations.
 *
 * @author     Francois Zaninotto
 * @version    $Id: CriteriaTest.php 1311 2009-11-11 20:47:33Z francois $
 * @package    runtime.query
 */
class CriteriaCombineTest extends BaseTestCase
{

  /**
   * The criteria to use in the test.
   * @var        Criteria
   */
  private $c;

  /**
   * DB adapter saved for later.
   *
   * @var        DBAdapter
   */
  private $savedAdapter;

  protected function setUp()
  {
    parent::setUp();
    $this->c = new Criteria();
    $this->savedAdapter = Propel::getDB(null);
    Propel::setDB(null, new DBSQLite());
  }

  protected function tearDown()
  {
    Propel::setDB(null, $this->savedAdapter);
    parent::tearDown();
  }

  /**
   * test various properties of Criterion and nested criterion
   */
  public function testNestedCriterion()
  {
    $table2 = "myTable2";
    $column2 = "myColumn2";
    $value2 = "myValue2";
    $key2 = "$table2.$column2";

    $table3 = "myTable3";
    $column3 = "myColumn3";
    $value3 = "myValue3";
    $key3 = "$table3.$column3";

    $table4 = "myTable4";
    $column4 = "myColumn4";
    $value4 = "myValue4";
    $key4 = "$table4.$column4";

    $table5 = "myTable5";
    $column5 = "myColumn5";
    $value5 = "myValue5";
    $key5 = "$table5.$column5";

    $crit2 = $this->c->getNewCriterion($key2, $value2, Criteria::EQUAL);
    $crit3 = $this->c->getNewCriterion($key3, $value3, Criteria::EQUAL);
    $crit4 = $this->c->getNewCriterion($key4, $value4, Criteria::EQUAL);
    $crit5 = $this->c->getNewCriterion($key5, $value5, Criteria::EQUAL);

    $crit2->addAnd($crit3)->addOr($crit4->addAnd($crit5));
    $expect = "((myTable2.myColumn2=:p1 AND myTable3.myColumn3=:p2) "
          . "OR (myTable4.myColumn4=:p3 AND myTable5.myColumn5=:p4))";

    $sb = "";
    $params = array();
    $crit2->appendPsTo($sb, $params);

    $expect_params = array(
      array('table' => 'myTable2', 'column' => 'myColumn2', 'value' => 'myValue2'),
      array('table' => 'myTable3', 'column' => 'myColumn3', 'value' => 'myValue3'),
      array('table' => 'myTable4', 'column' => 'myColumn4', 'value' => 'myValue4'),
      array('table' => 'myTable5', 'column' => 'myColumn5', 'value' => 'myValue5'),
    );

    $this->assertEquals($expect, $sb);
    $this->assertEquals($expect_params, $params);

    $crit6 = $this->c->getNewCriterion($key2, $value2, Criteria::EQUAL);
    $crit7 = $this->c->getNewCriterion($key3, $value3, Criteria::EQUAL);
    $crit8 = $this->c->getNewCriterion($key4, $value4, Criteria::EQUAL);
    $crit9 = $this->c->getNewCriterion($key5, $value5, Criteria::EQUAL);

    $crit6->addAnd($crit7)->addOr($crit8)->addAnd($crit9);
    $expect = "(((myTable2.myColumn2=:p1 AND myTable3.myColumn3=:p2) "
           . "OR myTable4.myColumn4=:p3) AND myTable5.myColumn5=:p4)";

    $sb = "";
    $params = array();
    $crit6->appendPsTo($sb, $params);

    $expect_params = array(
                    array('table' => 'myTable2', 'column' => 'myColumn2', 'value' => 'myValue2'),
                    array('table' => 'myTable3', 'column' => 'myColumn3', 'value' => 'myValue3'),
                    array('table' => 'myTable4', 'column' => 'myColumn4', 'value' => 'myValue4'),
                    array('table' => 'myTable5', 'column' => 'myColumn5', 'value' => 'myValue5'),
                );

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

    // simple confirmations that equality operations work
    $this->assertTrue($crit2->hashCode() === $crit2->hashCode());
  }

  /**
   * Tests <= and >=.
   */
  public function testBetweenCriterion()
  {
    $cn1 = $this->c->getNewCriterion("INVOICE.COST", 1000, Criteria::GREATER_EQUAL);
    $cn2 = $this->c->getNewCriterion("INVOICE.COST", 5000, Criteria::LESS_EQUAL);
    $this->c->add($cn1->addAnd($cn2));
    
    $expect = "SELECT  FROM INVOICE WHERE (INVOICE.COST>=:p1 AND INVOICE.COST<=:p2)";
    $expect_params = array(
    	array('table' => 'INVOICE', 'column' => 'COST', 'value' => 1000),
      array('table' => 'INVOICE', 'column' => 'COST', 'value' => 5000),
    );

    try {
      $params = array();
      $result = BasePeer::createSelectSql($this->c, $params);
    } catch (PropelException $e) {
      $this->fail("PropelException thrown in BasePeer.createSelectSql(): ".$e->getMessage());
    }

    $this->assertEquals($expect, $result);
    $this->assertEquals($expect_params, $params);
  }

  /**
   * Verify that AND and OR criterion are nested correctly.
   */
  public function testPrecedence()
  {
    $cn1 = $this->c->getNewCriterion("INVOICE.COST", "1000", Criteria::GREATER_EQUAL);
    $cn2 = $this->c->getNewCriterion("INVOICE.COST", "2000", Criteria::LESS_EQUAL);
    $cn3 = $this->c->getNewCriterion("INVOICE.COST", "8000", Criteria::GREATER_EQUAL);
    $cn4 = $this->c->getNewCriterion("INVOICE.COST", "9000", Criteria::LESS_EQUAL);
    $this->c->add($cn1->addAnd($cn2));
    $this->c->addOr($cn3->addAnd($cn4));

    $expect =
      "SELECT  FROM INVOICE WHERE ((INVOICE.COST>=:p1 AND INVOICE.COST<=:p2) OR (INVOICE.COST>=:p3 AND INVOICE.COST<=:p4))";

    $expect_params = array(
			array('table' => 'INVOICE', 'column' => 'COST', 'value' => '1000'),
			array('table' => 'INVOICE', 'column' => 'COST', 'value' => '2000'),
			array('table' => 'INVOICE', 'column' => 'COST', 'value' => '8000'),
			array('table' => 'INVOICE', 'column' => 'COST', 'value' => '9000'),
    );

    try {
      $params=array();
      $result = BasePeer::createSelectSql($this->c, $params);
    } catch (PropelException $e) {
      $this->fail("PropelException thrown in BasePeer::createSelectSql()");
    }

    $this->assertEquals($expect, $result);
    $this->assertEquals($expect_params, $params);
  }

}
