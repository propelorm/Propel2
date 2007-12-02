<?php

require_once 'classes/propel/BaseTestCase.php';
include_once 'propel/util/Criteria.php';
include_once 'propel/util/BasePeer.php';

/**
 * Test class for Criteria.
 *
 * @author     <a href="mailto:celkins@scardini.com">Christopher Elkins</a>
 * @author     <a href="mailto:sam@neurogrid.com">Sam Joseph</a>
 * @version    $Id$
 */
class CriteriaTest extends BaseTestCase {

	/** The criteria to use in the test. */
	private $c;

	/**
	 * Initializes the criteria.
	 */
	public function setUp()
	{
		parent::setUp();
		$this->c = new Criteria();
		Propel::setDB(null, new DBSQLite());
	}

	/**
	 * Test basic adding of strings.
	 */
	public function testAddString() {

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
		$expect =
			"((myTable2.myColumn2=? "
				. "AND myTable3.myColumn3=?) "
			. "OR (myTable4.myColumn4=? "
				. "AND myTable5.myColumn5=?))";

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
		$expect =
			"(((myTable2.myColumn2=? "
					. "AND myTable3.myColumn3=?) "
				. "OR myTable4.myColumn4=?) "
					. "AND myTable5.myColumn5=?)";

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
	 * Tests &lt;= and =&gt;.
	 */
	public function testBetweenCriterion()
	{
		$cn1 = $this->c->getNewCriterion(
				"INVOICE.COST",
				1000,
				Criteria::GREATER_EQUAL);

		$cn2 = $this->c->getNewCriterion(
				"INVOICE.COST",
				5000,
				Criteria::LESS_EQUAL);
		$this->c->add($cn1->addAnd($cn2));
		$expect =
			"SELECT  FROM INVOICE WHERE "
			. "(INVOICE.COST>=? AND INVOICE.COST<=?)";

		$expect_params = array( array('table' => 'INVOICE', 'column' => 'COST', 'value' => 1000),
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
			"SELECT  FROM INVOICE WHERE "
			. "((INVOICE.COST>=? AND INVOICE.COST<=?) "
			. "OR (INVOICE.COST>=? AND INVOICE.COST<=?))";

		$expect_params = array( array('table' => 'INVOICE', 'column' => 'COST', 'value' => '1000'),
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

	/**
	 * Test Criterion.setIgnoreCase().
	 * As the output is db specific the test just prints the result to
	 * System.out
	 */
	public function testCriterionIgnoreCase()
	{
		$adapters = array(new DBMySQL(), new DBPostgres());
		$expectedIgnore = array("UPPER(TABLE.COLUMN) LIKE UPPER(?)", "TABLE.COLUMN ILIKE ?");

		$i =0;
		foreach ($adapters as $adapter) {

			Propel::setDB(null, $adapter);
			$myCriteria = new Criteria();

			$myCriterion = $myCriteria->getNewCriterion(
					"TABLE.COLUMN", "FoObAr", Criteria::LIKE);
			$sb = "";
			$params=array();
			$myCriterion->appendPsTo($sb, $params);
			$expected = "TABLE.COLUMN LIKE ?";

			$this->assertEquals($expected, $sb);

			$ignoreCriterion = $myCriterion->setIgnoreCase(true);

			$sb = "";
			$params=array();
			$ignoreCriterion->appendPsTo($sb, $params);
			// $expected = "UPPER(TABLE.COLUMN) LIKE UPPER(?)";
			$this->assertEquals($expectedIgnore[$i], $sb);
			$i++;
		}
	}

	/**
	 * Test that true is evaluated correctly.
	 */
	public function testBoolean()
	{
		$this->c = new Criteria();
		$this->c->add("TABLE.COLUMN", true);

		$expect = "SELECT  FROM TABLE WHERE TABLE.COLUMN=?";
		$expect_params = array( array('table' => 'TABLE', 'column' => 'COLUMN', 'value' => true),
							   );
		try {
			$params = array();
			$result = BasePeer::createSelectSql($this->c, $params);
		} catch (PropelException $e) {
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}

		$this->assertEquals($expect, $result, "Boolean test failed.");
		$this->assertEquals($expect_params, $params);

	}

	public function testCurrentDate()
	{
		$this->c = new Criteria();
		$this->c->add("TABLE.TIME_COLUMN", Criteria::CURRENT_TIME);
		$this->c->add("TABLE.DATE_COLUMN", Criteria::CURRENT_DATE);

		$expect = "SELECT  FROM TABLE WHERE TABLE.TIME_COLUMN=CURRENT_TIME AND TABLE.DATE_COLUMN=CURRENT_DATE";

		$result = null;
		try {
			$result = BasePeer::createSelectSql($this->c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}

		$this->assertEquals($expect, $result, "Current date test failed!");

	}

	public function testCountAster()
	{
		$this->c = new Criteria();
		$this->c->addSelectColumn("COUNT(*)");
		$this->c->add("TABLE.TIME_COLUMN", Criteria::CURRENT_TIME);
		$this->c->add("TABLE.DATE_COLUMN", Criteria::CURRENT_DATE);

		$expect = "SELECT COUNT(*) FROM TABLE WHERE TABLE.TIME_COLUMN=CURRENT_TIME AND TABLE.DATE_COLUMN=CURRENT_DATE";

		$result = null;
		try {
			$result = BasePeer::createSelectSql($this->c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}

		$this->assertEquals($expect, $result);

	}

	public function testIn()
	{
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->add("TABLE.SOME_COLUMN", array(), Criteria::IN);
		$c->add("TABLE.OTHER_COLUMN", array(1, 2, 3), Criteria::IN);

		$expect = "SELECT * FROM TABLE WHERE 1<>1 AND TABLE.OTHER_COLUMN IN (?,?,?)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);


		// ----------------------------------------------------------------------------------
		// now do a nested logic test, just for sanity (not that this should be any surprise)

		$c = new Criteria();
		$c->addSelectColumn("*");
		$myCriterion = $c->getNewCriterion("TABLE.COLUMN", array(), Criteria::IN);
		$myCriterion->addOr($c->getNewCriterion("TABLE.COLUMN2", array(1,2), Criteria::IN));
		$c->add($myCriterion);

		$expect = "SELECT * FROM TABLE WHERE (1<>1 OR TABLE.COLUMN2 IN (?,?))";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);

	}

	public function testJoinObject ()
	{
		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_2');
		$this->assertEquals(null, $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_A', $j->getLeftTableName());
		$this->assertEquals('COL_1', $j->getLeftColumnName());
		$this->assertEquals('TABLE_B.COL_2', $j->getRightColumn());
		$this->assertEquals('TABLE_B', $j->getRightTableName());
		$this->assertEquals('COL_2', $j->getRightColumnName());

		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::LEFT_JOIN);
		$this->assertEquals('LEFT JOIN', $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_B.COL_1', $j->getRightColumn());

		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::RIGHT_JOIN);
		$this->assertEquals('RIGHT JOIN', $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_B.COL_1', $j->getRightColumn());

		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::INNER_JOIN);
		$this->assertEquals('INNER JOIN', $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_B.COL_1', $j->getRightColumn());
	}

	public function testAddingJoin ()
	{
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1'); // straight join

		$expect = "SELECT * FROM TABLE_A, TABLE_B WHERE TABLE_A.COL_1=TABLE_B.COL_1";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingMultipleJoins ()
	{
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1');
		$c->addJoin('TABLE_B.COL_X', 'TABLE_D.COL_X');

		$expect = 'SELECT * FROM TABLE_A, TABLE_B, TABLE_D '
				 .'WHERE TABLE_A.COL_1=TABLE_B.COL_1 AND TABLE_B.COL_X=TABLE_D.COL_X';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingLeftJoin ()
	{
		$c = new Criteria();
		$c->addSelectColumn("TABLE_A.*");
		$c->addSelectColumn("TABLE_B.*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_2', Criteria::LEFT_JOIN);

		$expect = "SELECT TABLE_A.*, TABLE_B.* FROM TABLE_A LEFT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_2)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingMultipleLeftJoins ()
	{
		// Fails.. Suspect answer in the chunk starting at BasePeer:605
		$c = new Criteria();
		$c->addSelectColumn('*');
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::LEFT_JOIN);
		$c->addJoin('TABLE_A.COL_2', 'TABLE_C.COL_2', Criteria::LEFT_JOIN);

		$expect = 'SELECT * FROM TABLE_A '
				 .'LEFT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1) '
						 .'LEFT JOIN TABLE_C ON (TABLE_A.COL_2=TABLE_C.COL_2)';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingRightJoin ()
	{
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_2', Criteria::RIGHT_JOIN);

		$expect = "SELECT * FROM TABLE_A RIGHT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_2)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingMultipleRightJoins ()
	{
		// Fails.. Suspect answer in the chunk starting at BasePeer:605
		$c = new Criteria();
		$c->addSelectColumn('*');
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::RIGHT_JOIN);
		$c->addJoin('TABLE_A.COL_2', 'TABLE_C.COL_2', Criteria::RIGHT_JOIN);

		$expect = 'SELECT * FROM TABLE_A '
				 .'RIGHT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1) '
						 .'RIGHT JOIN TABLE_C ON (TABLE_A.COL_2=TABLE_C.COL_2)';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingInnerJoin ()
	{
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::INNER_JOIN);

		$expect = "SELECT * FROM TABLE_A INNER JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	public function testAddingMultipleInnerJoin ()
	{
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::INNER_JOIN);
		$c->addJoin('TABLE_B.COL_1', 'TABLE_C.COL_1', Criteria::INNER_JOIN);

		$expect = 'SELECT * FROM TABLE_A '
				 .'INNER JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1) '
						 .'INNER JOIN TABLE_C ON (TABLE_B.COL_1=TABLE_C.COL_1)';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
	}

	/**
	 * @link       http://propel.phpdb.org/trac/ticket/451
	 */
	public function testMultipleMixedJoinOrders()
	{
		$c = new Criteria();
		$c->clearSelectColumns()->
			addJoin("TABLE_A.FOO_ID", "TABLE_B.ID", Criteria::LEFT_JOIN)->
			addJoin("TABLE_A.BAR_ID", "TABLE_C.ID")->
			addSelectColumn("TABLE_A.ID");

		$db = Propel::getDB();
		if ($db instanceof DBMySQL) {
			$expect = 'SELECT TABLE_A.ID FROM (TABLE_A, TABLE_C)'
					.' LEFT JOIN TABLE_B ON (TABLE_A.FOO_ID=TABLE_B.ID) WHERE TABLE_A.BAR_ID=TABLE_C.ID';
		} else {
			$expect = 'SELECT TABLE_A.ID FROM TABLE_A, TABLE_C'
					.' LEFT JOIN TABLE_B ON (TABLE_A.FOO_ID=TABLE_B.ID) WHERE TABLE_A.BAR_ID=TABLE_C.ID';
		}

		$result = BasePeer::createSelectSql($c, $params=array());

		#print "Actual:   " . $result . "\n---\n";
		#print "Expected: " . $expect . "\n";

		$this->assertEquals($expect, $result);
	}
}
