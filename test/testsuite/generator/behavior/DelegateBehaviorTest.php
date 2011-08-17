<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests for DelegateBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior
 */
class TimestampableBehaviorTest extends BookstoreTestBase
{
	public function testModifyTableRelatesDelegate()
	{
		$delegateTable = DelegateDelegatePeer::getTableMap();
		$this->assertEquals(2, count($delegateTable->getColumns()));
		$this->assertEquals(1, count($delegateTable->getRelations()));
		$this->assertTrue(method_exists('DelegateMain', 'getDelegateDelegate'));
		$this->assertTrue(method_exists('DelegateDelegate', 'getDelegateMain'));
	}
	
	public function testDelegationCreatesANewDelegateIfNoneExists()
	{
		$main = new DelegateMain();
		$main->setSubtitle('foo');
		$delegate = $main->getDelegateDelegate();
		$this->assertInstanceOf('DelegateDelegate', $delegate);
		$this->assertTrue($delegate->isNew());
		$this->assertEquals('foo', $delegate->getSubtitle());
		$this->assertEquals('foo', $main->getSubtitle());
	}

	public function testDelegationUsesExistingDelegateIfExists()
	{
		$main = new DelegateMain();
		$delegate = new DelegateDelegate();
		$delegate->setSubtitle('bar');
		$main->setDelegateDelegate($delegate);
		$this->assertEquals('bar', $main->getSubtitle());
	}
	
}
