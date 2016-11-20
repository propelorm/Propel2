<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\Configuration;
use Propel\Runtime\Propel;
use Propel\Runtime\Adapter\Pdo\OracleAdapter;
use Propel\Runtime\ActiveQuery\Criteria;

use Propel\Tests\Bookstore\Map\AuthorEntityMap;
use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author Francois EZaninotto
 */
class OracleAdapterTest extends TestCaseFixtures
{
    public function testApplyLimitSimple()
    {
        Configuration::getCurrentConfiguration()->setAdapter('bookstore', new OracleAdapter());
        $c = new BookQuery();
        $c->addSelfSelectFields();
        $c->setLimit(1);
        $params = array();
        $sql = $c->createSelectSql($params);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with the original column names by default');
    }

    public function testCreateSelectSqlPart()
    {
        Configuration::getCurrentConfiguration()->setAdapter('oracle', new OracleAdapter());
        $db = Configuration::getCurrentConfiguration()->getAdapter('oracle');
        $c = new BookQuery();
        $c->addSelectField(BookEntityMap::FIELD_ID);
        $c->addAsField('book_ID', BookEntityMap::FIELD_ID);
        $selectSql = $db->createSelectSqlPart($c);
        $this->assertEquals('SELECT Propel\Tests\Bookstore\Book.id, Propel\Tests\Bookstore\Book.id AS book_ID', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
    }

}
