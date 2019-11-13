<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Base\BookQuery;
use Propel\Tests\Bookstore\AuthorQuery;

/**
 * Test class for ComplexCountTest.
 *
 * @author Fredrik Wollsén
 *
 * @group database
 */
class ComplexCountTest extends BookstoreTestBase
{
    public function testCountQueryWhenUsingHavingAndDuplicateColumnNamesInTheSelectPart()
    {
        $c = new AuthorQuery();
        $c->leftJoinWithBook();
        $c->having('COUNT(book.id) > 1');

        $this->assertTrue($c->needsComplexCount(), 'query needs complex count');

        $this->assertTrue($c->needsSelectAliases(), 'query needs select aliases');

        $this->assertTrue((bool) $c->getHaving(), 'query has a having clause');

        $params = [];
        $countSql = $c->createCountSql($params);
        $expectedCountSql = $this->getSql("SELECT COUNT(*) FROM (SELECT author.id AS author_id FROM author LEFT JOIN book ON (author.id=book.author_id) HAVING COUNT(book.id) > 1) propelmatch4cnt");

        $this->assertEquals($expectedCountSql, $countSql, 'count sql is defined as expected');

        $nbBooks = BookQuery::create()->count();
        $this->assertEquals(4, $nbBooks, 'expected book count in test dataset');

        $nbAuthors = AuthorQuery::create()->count();
        $this->assertEquals(4, $nbAuthors, 'expected author count in test dataset');

        $nbAuthorsWithAtLeastOneBook = $c->count();
        $this->assertEquals(1, $nbAuthorsWithAtLeastOneBook, 'query returns expected count in test dataset');
    }
}
