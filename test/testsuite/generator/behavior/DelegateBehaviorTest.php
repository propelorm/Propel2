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
require_once dirname(__FILE__) . '/../../../../generator/lib/behavior/DelegateBehavior.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';

/**
 * Tests for DelegateBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior
 */
class DelegateBehaviorTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		if (!class_exists('DelegateDelegate')) {
			$schema = <<<EOF
<database name="delegate_behavior_test_1">

	<table name="delegate_main">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="title" type="VARCHAR" size="100" primaryString="true" />
		<column name="delegate_id" type="INTEGER" />
		<foreign-key foreignTable="second_delegate_delegate">
			<reference local="delegate_id" foreign="id" />
		</foreign-key>
		<behavior name="delegate">
			<parameter name="to" value="delegate_delegate, second_delegate_delegate" />
		</behavior>
	</table>

	<table name="delegate_delegate">
		<column name="subtitle" type="VARCHAR" size="100" primaryString="true" />
	</table>

	<table name="second_delegate_delegate">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="summary" type="VARCHAR" size="100" primaryString="true" />
		<behavior name="delegate">
			<parameter name="to" value="third_delegate_delegate" />
		</behavior>
	</table>

	<table name="third_delegate_delegate">
		<column name="body" type="VARCHAR" size="100" primaryString="true" />
	</table>

</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
		}
	}

	public function testModifyTableRelatesOneToOneDelegate()
	{
		$delegateTable = DelegateDelegatePeer::getTableMap();
		$this->assertEquals(2, count($delegateTable->getColumns()));
		$this->assertEquals(1, count($delegateTable->getRelations()));
		$this->assertTrue(method_exists('DelegateMain', 'getDelegateDelegate'));
		$this->assertTrue(method_exists('DelegateDelegate', 'getDelegateMain'));
	}
	
	public function testOneToOneDelegationCreatesANewDelegateIfNoneExists()
	{
		$main = new DelegateMain();
		$main->setSubtitle('foo');
		$delegate = $main->getDelegateDelegate();
		$this->assertInstanceOf('DelegateDelegate', $delegate);
		$this->assertTrue($delegate->isNew());
		$this->assertEquals('foo', $delegate->getSubtitle());
		$this->assertEquals('foo', $main->getSubtitle());
	}

	public function testManyToOneDelegationCreatesANewDelegateIfNoneExists()
	{
		$main = new DelegateMain();
		$main->setSummary('foo');
		$delegate = $main->getSecondDelegateDelegate();
		$this->assertInstanceOf('SecondDelegateDelegate', $delegate);
		$this->assertTrue($delegate->isNew());
		$this->assertEquals('foo', $delegate->getSummary());
		$this->assertEquals('foo', $main->getSummary());
	}

	public function testOneToOneDelegationUsesExistingDelegateIfExists()
	{
		$main = new DelegateMain();
		$delegate = new DelegateDelegate();
		$delegate->setSubtitle('bar');
		$main->setDelegateDelegate($delegate);
		$this->assertEquals('bar', $main->getSubtitle());
	}

	public function testManyToOneDelegationUsesExistingDelegateIfExists()
	{
		$main = new DelegateMain();
		$delegate = new SecondDelegateDelegate();
		$delegate->setSummary('bar');
		$main->setSecondDelegateDelegate($delegate);
		$this->assertEquals('bar', $main->getSummary());
	}

	public function testAModelCanHaveSeveralDelegates()
	{
		$main = new DelegateMain();
		$main->setSubtitle('foo');
		$main->setSummary('bar');
		$delegate = $main->getDelegateDelegate();
		$this->assertInstanceOf('DelegateDelegate', $delegate);
		$this->assertTrue($delegate->isNew());
		$this->assertEquals('foo', $delegate->getSubtitle());
		$this->assertEquals('foo', $main->getSubtitle());
		$delegate = $main->getSecondDelegateDelegate();
		$this->assertInstanceOf('SecondDelegateDelegate', $delegate);
		$this->assertTrue($delegate->isNew());
		$this->assertEquals('bar', $delegate->getSummary());
		$this->assertEquals('bar', $main->getSummary());
	}

	/**
	 * @expectedException PropelException
	 */
	public function testAModelCannotHaveCascadingDelegates()
	{
		$main = new DelegateMain();
		$main->setSummary('bar');
		$main->setBody('baz');
	}

	public function testOneToOneDelegatesCanBePersisted()
	{
		$main = new DelegateMain();
		$main->setSubtitle('foo');
		$main->save();
		$this->assertFalse($main->isNew());
		$this->assertFalse($main->getDelegateDelegate()->isNew());
		$this->assertNull($main->getSecondDelegateDelegate());
	}

	public function testManyToOneDelegatesCanBePersisted()
	{
		$main = new DelegateMain();
		$main->setSummary('foo');
		$main->save();
		$this->assertFalse($main->isNew());
		$this->assertFalse($main->getSecondDelegateDelegate()->isNew());
		$this->assertNull($main->getDelegateDelegate());
	}
	
}
