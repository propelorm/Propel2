<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/behavior/TurboBehavior.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';

/**
 * Tests for TurboBehavior class
 *
 * @author     François Zaninotto
 * @package    generator.behavior
 */
class TurboBehaviorTest extends PHPUnit_Framework_TestCase
{
	protected $con;

	public function setUp()
	{
		if (!class_exists('TurboMain')) {
			$schema = <<<EOF
<database name="turbo_behavior_test_1">

	<table name="turbo_main">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="title" type="VARCHAR" size="100" primaryString="true" />
		<behavior name="turbo" />
	</table>

	<table name="turbo_off_main">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="title" type="VARCHAR" size="100" primaryString="true" />
	</table>

</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
			$testableClass = <<<EOF
class TestableTurboMainQuery extends TurboMainQuery
{
	public static \$preSelectWasCalled = false;

  public function __construct(\$dbName = 'turbo_behavior_test_1', \$modelName = 'TurboMain', \$modelAlias = null)
  {
		self::\$preSelectWasCalled = false;
    parent::__construct(\$dbName, \$modelName, \$modelAlias);
  }

	public function preSelect(PropelPDO \$con)
	{
		self::\$preSelectWasCalled = true;
	}
}
EOF;
			eval($testableClass);
		}
	}

	public function testActiveRecordAsDoInsertUsingBasePeerMethod()
	{
		$this->assertTrue(method_exists('TurboMain', 'doInsertUsingBasePeer'));
	}
	
	public function testActiveRecordDoInsertTurboInsertsARecord()
	{
		$t = new TurboMain();
		$t->setTitle('foo');
		$t->save();
		$this->assertFalse($t->isNew());
		$this->assertNotNull($t->getPrimaryKey());
		$t2 = TurboMainQuery::create()->findPk($t->getPrimaryKey());
		$this->assertSame($t, $t2);
	}

	public function testActiveRecordDoInsertTurboMakesFastQuery()
	{
		$con = Propel::getConnection(TurboMainPeer::DATABASE_NAME);
		$con->useDebug(true);
		$t = new TurboMain();
		$t->setTitle('foo');
		$t->save($con);
		$expected = 'INSERT INTO [turbo_main] ([TITLE]) VALUES (\'foo\')';
		$this->assertEquals($expected, $con->getLastExecutedQuery());
		$con->useDebug(false);
	}
	
	public function testQueryHasFindPkComplexAndFindPkSimpleMethods()
	{
		$this->assertTrue(method_exists('TurboMainQuery', 'findPkComplex'));
		$this->assertTrue(method_exists('TurboMainQuery', 'findPkSimple'));
		$this->assertFalse(method_exists('TurboOffMainQuery', 'findPkComplex'));
		$this->assertFalse(method_exists('TurboOffMainQuery', 'findPkSimple'));
	}

	public function testQueryFindPkSimpleDoesNotSkipPreSelect()
	{
		$q = new TestableTurboMainQuery();
		$q->findPkSimple(123);
		$this->assertTrue($q::$preSelectWasCalled);
	}

	public function testQueryFindPkSimpleMakesFastQuery()
	{
		$con = Propel::getConnection(TurboMainPeer::DATABASE_NAME);
		$con->useDebug(true);
		TurboMainQuery::create()->findPkSimple(123, $con);
		$expected = 'SELECT [ID], [TITLE] FROM [turbo_main] WHERE [ID] = 123';
		$this->assertEquals($expected, $con->getLastExecutedQuery());
		$con->useDebug(false);
	}

	public function testQueryFindPkSimpleGetsFromInstancePool()
	{
		$obj1 = new TurboMain();
		$obj1->setTitle('foo');
		$obj1->save();
		$obj = TurboMainQuery::create()->findPkSimple($obj1->getPrimaryKey());
		$this->assertSame($obj1, $obj);
	}

	public function testQueryFindPkSimplePutsIntoInstancePool()
	{
		$obj1 = new TurboMain();
		$obj1->setTitle('foo');
		$obj1->save();
		TurboMainPeer::clearInstancePool();
		$obj = TurboMainQuery::create()->findPkSimple($obj1->getPrimaryKey());
		$expected = array($obj->getPrimaryKey() => $obj);
		$this->assertEquals($expected, TurboMainPeer::$instances);
	}

	public function testQueryFindPkSimpleQueriesCorrectObject()
	{
		$obj1 = new TurboMain();
		$obj1->setTitle('foo');
		$obj1->save();
		$obj2 = new TurboMain();
		$obj2->setTitle('bar');
		$obj2->save();
		TurboMainPeer::clearInstancePool();
		$obj = TurboMainQuery::create()->findPkSimple($obj2->getPrimaryKey());
		$this->assertEquals($obj2, $obj);
	}

	public function testQueryFindPkComplexCallsPreSelect()
	{
		$q = new TestableTurboMainQuery();
		$q->findPkComplex(123);
		$this->assertTrue($q::$preSelectWasCalled);
	}

	public function testQueryFindPkComplexDoesNotMakeFastQuery()
	{
		$con = Propel::getConnection(TurboMainPeer::DATABASE_NAME);
		$con->useDebug(true);
		TurboMainQuery::create()->findPkComplex(123, $con);
		$expected = 'SELECT turbo_main.ID, turbo_main.TITLE FROM turbo_main WHERE turbo_main.ID=123';
		$this->assertEquals($expected, $con->getLastExecutedQuery());
		$con->useDebug(false);
	}

}
