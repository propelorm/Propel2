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

}
