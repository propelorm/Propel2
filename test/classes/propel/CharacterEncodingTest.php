<?php
/*
 *  $Id: GeneratedPeerTest.php 842 2007-12-02 16:28:20Z heltem $
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
 * Tests the character encoding support of the adapter.
 *
 * This test assumes that the created database supports UTF-8.  For this to work,
 * this file also has to be UTF-8.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class CharacterEncodingTest extends BookstoreTestBase {

	/**
	 * Database adapter.
	 * @var        DBAdapter
	 */
	private $adapter;

	public function setUp()
	{
		parent::setUp();
		if (!extension_loaded('iconv')) {
			throw new Exception("Character-encoding tests require iconv extension to be loaded.");
		}
	}

	public function testUtf8()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);

		$title = "Смерть на брудершафт. Младенец и черт";
		//        1234567890123456789012345678901234567
		//                 1         2         3

		$a = new Author();
		$a->setFirstName("Б.");
		$a->setLastName("АКУНИН");

		$p = new Publisher();
		$p->setName("Детектив российский, остросюжетная проза");

		$b = new Book();
		$b->setTitle($title);
		$b->setISBN("B-59246");
		$b->setAuthor($a);
		$b->setPublisher($p);
		$b->save();

		$b->reload();

		$this->assertEquals(37, iconv_strlen($b->getTitle(), 'utf-8'), "Expected 37 characters (not bytes) in title.");
		$this->assertTrue(strlen($b->getTitle()) > iconv_strlen($b->getTitle(), 'utf-8'), "Expected more bytes than characters in title.");

	}

	public function testInvalidCharset()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		if ($db instanceof DBSQLite) {
			$this->markTestSkipped();
		}

		$a = new Author();
		$a->setFirstName("Б.");
		$a->setLastName("АКУНИН");
		$a->save();

		$authorNameWindows1251 = iconv("utf-8", "windows-1251", $a->getLastName());
		$a->setLastName($authorNameWindows1251);

		// Different databases seem to handle invalid data differently (no surprise, I guess...)
		if ($db instanceof DBPostgres) {
			try {
				$a->save();
				$this->fail("Expected an exception when saving non-UTF8 data to database.");
			} catch (Exception $x) {
				print $x;
			}

		} else {

			// No exception is thrown by MySQL ... (others need to be tested still)
			$a->save();
			$a->reload();

			$this->assertEquals("",$a->getLastName(), "Expected last_name to be empty (after inserting invalid charset data)");
		}

	}

}
