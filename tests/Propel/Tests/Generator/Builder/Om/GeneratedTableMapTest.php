<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\EssayTableMap;
use Propel\Tests\Bookstore\Map\MediaTableMap;
use Propel\Tests\Bookstore\Map\PublisherTableMap;

use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Tests the generated TableMap classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * TableMap operations.
 *
 * The database is reloaded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratedTableMapTest extends BookstoreTestBase
{
    public function testAlias()
    {
        $this->assertEquals('foo.ID', BookTableMap::alias('foo', BookTableMap::ID), 'alias() returns a column name using the table alias');
        $this->assertEquals('book.ID', BookTableMap::alias('book', BookTableMap::ID), 'alias() returns a column name using the table alias');
        $this->assertEquals('foo.COVER_IMAGE', MediaTableMap::alias('foo', MediaTableMap::COVER_IMAGE), 'alias() also works for lazy-loaded columns');
        $this->assertEquals('foo.SUBTITLE', EssayTableMap::alias('foo', EssayTableMap::SUBTITLE), 'alias() also works for columns with custom phpName');
    }

    public function testAddSelectColumns()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);
        $expected = array(
            BookTableMap::ID,
            BookTableMap::TITLE,
            BookTableMap::ISBN,
            BookTableMap::PRICE,
            BookTableMap::PUBLISHER_ID,
            BookTableMap::AUTHOR_ID
        );
        $this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() adds the columns of the model to the criteria');
    }

    public function testAddSelectColumnsLazyLoad()
    {
        $c = new Criteria();
        MediaTableMap::addSelectColumns($c);
        $expected = array(
            MediaTableMap::ID,
            MediaTableMap::BOOK_ID
        );
        $this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() does not add lazy loaded columns');
    }

    public function testAddSelectColumnsAlias()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c, 'foo');
        $expected = array(
            'foo.ID',
            'foo.TITLE',
            'foo.ISBN',
            'foo.PRICE',
            'foo.PUBLISHER_ID',
            'foo.AUTHOR_ID'
        );
        $this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() uses the second parameter as a table alias');
    }

    public function testAddSelectColumnsAliasLazyLoad()
    {
        $c = new Criteria();
        MediaTableMap::addSelectColumns($c, 'bar');
        $expected = array(
            'bar.ID',
            'bar.BOOK_ID'
        );
        $this->assertEquals($expected, $c->getSelectColumns(), 'addSelectColumns() does not add lazy loaded columns but uses the second parameter as an alias');
    }

    public function testDefaultStringFormatConstant()
    {
        $this->assertTrue(defined('Propel\Tests\Bookstore\Map\BookTableMap::DEFAULT_STRING_FORMAT'), 'every TableMap class has the DEFAULT_STRING_FORMAT constant');
        $this->assertEquals('YAML', AuthorTableMap::DEFAULT_STRING_FORMAT, 'default string format is YAML by default');
        $this->assertEquals('XML', PublisherTableMap::DEFAULT_STRING_FORMAT, 'default string format can be customized using the defaultStringFormat attribute in the schema');
    }

}
