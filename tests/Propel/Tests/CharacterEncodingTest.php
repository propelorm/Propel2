<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests;

use Exception;
use Propel\Runtime\Adapter\PgsqlAdapter;
use Propel\Runtime\Adapter\SqliteAdapter;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests the character encoding support of the adapter.
 *
 * This test assumes that the created database supports UTF-8. For this to work,
 * this file also has to be UTF-8.
 *
 * The database is reloaded before every test and flushed after every test. This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class. See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 *
 * @group database
 */
class CharacterEncodingTest extends BookstoreTestBase
{
    /**
     * @throws \Exception
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('iconv')) {
            throw new Exception('Character-encoding tests require iconv extension to be loaded.');
        }
    }

    /**
     * @return void
     */
    public function testUtf8()
    {
        $this->markTestSkipped('Skipped because of weird behavior on some platforms');

        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);

        $title = 'Смерть на брудершафт. Младенец и черт';
        //        1234567890123456789012345678901234567
        //                 1         2         3

        $a = new Author();
        $a->setFirstName('Б.');
        $a->setLastName('АКУНИН');

        $p = new Publisher();
        $p->setName('Детектив российский, остросюжетная проза');

        $b = new Book();
        $b->setTitle($title);
        $b->setISBN('B-59246');
        $b->setAuthor($a);
        $b->setPublisher($p);
        $b->save();

        $b->reload();

        $this->assertEquals(37, iconv_strlen($b->getTitle(), 'utf-8'), 'Expected 37 characters (not bytes) in title.');
        $this->assertTrue(strlen($b->getTitle()) > iconv_strlen($b->getTitle(), 'utf-8'), 'Expected more bytes than characters in title.');
    }

    /**
     * @return void
     */
    public function testInvalidCharset()
    {
        $this->markTestSkipped('Skipped because of weird behavior on some platforms');

        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        if ($db instanceof SqliteAdapter) {
            $this->markTestSkipped();
        }

        $a = new Author();
        $a->setFirstName('Б.');
        $a->setLastName('АКУНИН');
        $a->save();

        $authorNameWindows1251 = iconv('utf-8', 'windows-1251', $a->getLastName());
        $a->setLastName($authorNameWindows1251);

        // Different databases seem to handle invalid data differently (no surprise, I guess...)
        if ($db instanceof PgsqlAdapter) {
            try {
                $a->save();
                $this->fail('Expected an exception when saving non-UTF8 data to database.');
            } catch (Exception $x) {
                print $x;
            }
        } else {
            // No exception is thrown by MySQL ... (others need to be tested still)
            $a->save();
            $a->reload();

            $this->assertEquals('', $a->getLastName(), 'Expected last_name to be empty (after inserting invalid charset data)');
        }
    }
}
