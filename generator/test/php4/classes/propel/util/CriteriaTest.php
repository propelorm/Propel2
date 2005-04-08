<?php
/*
 * $Id: CriteriaTest.php,v 1.2 2004/11/29 16:03:17 micha Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */ 

require_once 'propel/BaseTestCase.php';
include_once 'propel/util/Criteria.php';
include_once 'propel/util/BasePeer.php';

/**
 * Test class for Criteria.
 *
 * @author <a href="mailto:celkins@scardini.com">Christopher Elkins</a>
 * @author <a href="mailto:sam@neurogrid.com">Sam Joseph</a>
 * @version $Revision: 1.2 $
 */
class CriteriaTest extends BaseTestCase
{

  /**
  * The criteria to use in the test.
  * @var Criteria
  */
  var $c;

  /**
  * CriteriaTest constructor.
  */
  function CriteriaTest($name)
  {
    parent::BaseTestCase($name);
  }

  /**
  * Initializes the criteria.
  */
  function setUp()
  {
    parent::setUp();
    $this->c = new Criteria();
  }

  /**
  * Test basic adding of strings.
  */
  function testAddString()
  {

    $table = "myTable";
    $column = "myColumn";
    $value = "myValue";

    // Add the string
    $this->c->add($table . '.' . $column, $value);

    // Verify that the key exists
    $this->assertTrue($this->c->containsKey($table . '.' . $column));

    // Verify that what we get out is what we put in
    $this->assertTrue($this->c->getValue($table . '.' . $column) === $value);
  }

  /**
  * test various properties of Criterion and nested criterion
  */
  function testNestedCriterion()
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

      $crit2 = $this->c->getNewCriterion($key2, $value2, Criteria::EQUAL());
      $crit3 = $this->c->getNewCriterion($key3, $value3, Criteria::EQUAL());
      $crit4 = $this->c->getNewCriterion($key4, $value4, Criteria::EQUAL());
      $crit5 = $this->c->getNewCriterion($key5, $value5, Criteria::EQUAL());

      $crit2 =& $crit2->addAnd($crit3);
      $crit2 =& $crit2->addOr($crit4->addAnd($crit5));

      $expect =
          "((myTable2.myColumn2=? "
              . "AND myTable3.myColumn3=?) "
          . "OR (myTable4.myColumn4=? "
              . "AND myTable5.myColumn5=?))";

      $crit2->appendPsTo($sb="", $params=array());

      $expect_params = array(
                                  array('table' => 'myTable2', 'column' => 'myColumn2', 'value' => 'myValue2'),
                                  array('table' => 'myTable3', 'column' => 'myColumn3', 'value' => 'myValue3'),
                                  array('table' => 'myTable4', 'column' => 'myColumn4', 'value' => 'myValue4'),
                                  array('table' => 'myTable5', 'column' => 'myColumn5', 'value' => 'myValue5'),
                              );

      $this->assertEquals($expect, $sb);
      $this->assertEquals($expect_params, $params);

      $crit6 = $this->c->getNewCriterion($key2, $value2, Criteria::EQUAL());
      $crit7 = $this->c->getNewCriterion($key3, $value3, Criteria::EQUAL());
      $crit8 = $this->c->getNewCriterion($key4, $value4, Criteria::EQUAL());
      $crit9 = $this->c->getNewCriterion($key5, $value5, Criteria::EQUAL());

      $crit6 =& $crit6->addAnd($crit7);
      $crit6 =& $crit6->addOr($crit8);
      $crit6 =& $crit6->addAnd($crit9);

      $expect =
          "(((myTable2.myColumn2=? "
                  . "AND myTable3.myColumn3=?) "
              . "OR myTable4.myColumn4=?) "
                  . "AND myTable5.myColumn5=?)";

      $crit6->appendPsTo($sb="", $params=array());

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
  * Tests &lt;= and =&gt;.
  */
  function testBetweenCriterion()
  {
      $cn1 = $this->c->getNewCriterion(
              "INVOICE.COST",
              1000,
              Criteria::GREATER_EQUAL());

      $cn2 = $this->c->getNewCriterion(
              "INVOICE.COST",
              5000,
              Criteria::LESS_EQUAL());
      $this->c->add($cn1->addAnd($cn2));
      $expect =
          "SELECT  FROM INVOICE WHERE "
          . "(INVOICE.COST>=? AND INVOICE.COST<=?)";

      $expect_params = array( array('table' => 'INVOICE', 'column' => 'COST', 'value' => 1000),
                              array('table' => 'INVOICE', 'column' => 'COST', 'value' => 5000),
                             );

      $result = BasePeer::createSelectSql($this->c, $params=array());

      if (Propel::isError($result)) {
        $this->fail("PropelException thrown in BasePeer.createSelectSql(): ".$e->getMessage());
      }

      $this->assertEquals($expect, $result);
      $this->assertEquals($expect_params, $params);
  }

  /**
  * Verify that AND and OR criterion are nested correctly.
  */
  function testPrecedence()
  {
      $cn1 = $this->c->getNewCriterion("INVOICE.COST", "1000", Criteria::GREATER_EQUAL());
      $cn2 = $this->c->getNewCriterion("INVOICE.COST", "2000", Criteria::LESS_EQUAL());
      $cn3 = $this->c->getNewCriterion("INVOICE.COST", "8000", Criteria::GREATER_EQUAL());
      $cn4 = $this->c->getNewCriterion("INVOICE.COST", "9000", Criteria::LESS_EQUAL());
      $this->c->add($cn1->addAnd($cn2));
      $this->c->addOr($cn3->addAnd($cn4));

      $expect =
          "SELECT  FROM INVOICE WHERE "
          . "((INVOICE.COST>=? AND INVOICE.COST<=?) "
          . "OR (INVOICE.COST>=? AND INVOICE.COST<=?))";

      $expect_params = array( array('table' => 'INVOICE', 'column' => 'COST', 'value' => '1000'),
                              array('table' => 'INVOICE', 'column' => 'COST', 'value' => '2000'),
                              array('table' => 'INVOICE', 'column' => 'COST', 'value' => '8000'),
                              array('table' => 'INVOICE', 'column' => 'COST', 'value' => '9000'),
                             );

      $result = BasePeer::createSelectSql($this->c, $params=array());

      if (Propel::isError($result)) {
        $this->fail("PropelException thrown in BasePeer::createSelectSql()");
      }

      $this->assertEquals($expect, $result);
      $this->assertEquals($expect_params, $params);
  }

  /**
  * Test Criterion.setIgnoreCase().
  * As the output is db specific the test just prints the result to
  * System.out
  */
  function testCriterionIgnoreCase()
  {
    $myCriteria = new Criteria();

    $myCriterion = $myCriteria->getNewCriterion("TABLE.COLUMN", "FoObAr", Criteria::LIKE());
    $myCriterion->appendPsTo($sb = "", $params=array());

    $this->assertEquals($sb, 'TABLE.COLUMN LIKE ?');

    $ignoreCriterion = $myCriterion->setIgnoreCase(true);
    $ignoreCriterion->appendPsTo($sb = "", $params=array());

    $this->assertEquals($sb, 'UPPER(TABLE.COLUMN) LIKE UPPER(?)');
  }

  /**
  * Test that true is evaluated correctly.
  */
  function testBoolean()
  {
    $this->c = new Criteria();
    $this->c->add("TABLE.COLUMN", true);

    $expect = "SELECT  FROM TABLE WHERE TABLE.COLUMN=?";
    $expect_params = array( array('table' => 'TABLE', 'column' => 'COLUMN', 'value' => true),
                           );
    $result = BasePeer::createSelectSql($this->c, $params=array());

    if (Propel::isError($result)) {
      $this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
    }

    $this->assertEquals($expect, $result, "Boolean test failed.");
    $this->assertEquals($expect_params, $params);
  }

  function testCurrentDate()
  {
    $this->c = new Criteria();
    $this->c->add("TABLE.TIME_COLUMN", Criteria::CURRENT_TIME());
    $this->c->add("TABLE.DATE_COLUMN", Criteria::CURRENT_DATE());

    $expect = "SELECT  FROM TABLE WHERE TABLE.TIME_COLUMN=CURRENT_TIME AND TABLE.DATE_COLUMN=CURRENT_DATE";

    $result = null;
    $result = BasePeer::createSelectSql($this->c, $params=array());

    if (Propel::isError($result)) {
      //        print $e->getTraceAsString();
      $this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
    }

    $this->assertEquals($expect, $result, "Current date test failed!");
  }

  function testCountAster()
  {
    $this->c = new Criteria();
    $this->c->addSelectColumn("COUNT(*)");
    $this->c->add("TABLE.TIME_COLUMN", Criteria::CURRENT_TIME());
    $this->c->add("TABLE.DATE_COLUMN", Criteria::CURRENT_DATE());

    $expect = "SELECT COUNT(*) FROM TABLE WHERE TABLE.TIME_COLUMN=CURRENT_TIME AND TABLE.DATE_COLUMN=CURRENT_DATE";

    $result = null;
    $result = BasePeer::createSelectSql($this->c, $params=array());

    if (Propel::isError($result)) {
      //        print $e->getTraceAsString();
      $this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
    }

    $this->assertEquals($expect, $result);
  }

}
