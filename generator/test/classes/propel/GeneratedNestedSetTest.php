<?php
/*
 *  $Id: GeneratedObjectTest.php 840 2007-12-02 15:53:17Z hans $
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

require_once 'bookstore/BookstoreTestBase.php';

/**
 * Tests the generated nested-set Object classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * object operations.  The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 */
class GeneratedNestedSetTest extends BookstoreTestBase {

	protected function setUp()
	{
		parent::setUp();
		
		// TODO - maybe use multiple trees instead of having
		// a meaningless top-level category 'Category'
		$root = new BookCategory();
		$root->makeRoot();
		$root->setLabel('Category');
		$root->save();
		
		$fiction = new BookCategory();
		$fiction->setLabel('Fiction');
		$fiction->insertAsLastChildOf($root);
		$fiction->save();
		
		$mystery = new BookCategory();
		$mystery->setLabel('Mystery');
		$mystery->insertAsLastChildOf($fiction);
		$mystery->save();
		
		$romance = new BookCategory();
		$romance->setLabel('Romance');
		$romance->insertAsLastChildOf($fiction);
		$romance->save();
		
		$nonfiction = new BookCategory();
		$nonfiction->setLabel("Non-fiction");
		$nonfiction->insertAsNextSiblingOf($fiction);
		$nonfiction->save();
		
		$biography = new BookCategory();
		$biography->setLabel('Biography');
		$biography->insertAsLastChildOf($nonfiction);
		$biography->save();
	
	}

	protected function tearDown()
	{
		parent::tearDown();
	}
	
	public function testInsert()
	{
		$this->markTestIncomplete();
	}
}
