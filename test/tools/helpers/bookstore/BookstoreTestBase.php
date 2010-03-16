<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreTestBase extends PHPUnit_Framework_TestCase
{
	protected $con;
	
	/**
	 * This is run before each unit test; it populates the database.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$this->con->beginTransaction();
	}

	/**
	 * This is run after each unit test.  It empties the database.
	 */
	protected function tearDown()
	{
		parent::tearDown();
		$this->con->commit();
	}
}
