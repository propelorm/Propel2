<?php
/*
 *  $Id$
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

require_once 'PHPUnit/Framework/TestCase.php';
set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');
include_once 'tools/helpers/cms/CmsDataPopulator.php';

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class CmsTestBase extends PHPUnit_Framework_TestCase
{
	protected $con;
	
	/**
	 * This is run before each unit test; it populates the database.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->con = Propel::getConnection(PagePeer::DATABASE_NAME);
		$this->con->beginTransaction();
		CmsDataPopulator::depopulate($this->con);
		CmsDataPopulator::populate($this->con);
	}

	/**
	 * This is run after each unit test.  It empties the database.
	 */
	protected function tearDown()
	{
		CmsDataPopulator::depopulate($this->con);
		$this->con->commit();
		parent::tearDown();
	}

}
