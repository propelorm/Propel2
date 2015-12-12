<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;

/**
 * @group database
 */
class ModelCriteriaGroupByArrayTest extends BookstoreEmptyTestBase
{
    /**
     * @dataProvider dataForTestException
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testGroupByArrayThrowException($groupBy)
    {
        $authors = AuthorQuery::create()
            ->leftJoinBook()
            ->select(array('FirstName', 'LastName'))
            ->withColumn('COUNT(Book.Id)', 'nbBooks')
            ->groupBy($groupBy)
            ->orderByLastName()
            ->find();
    }
    
    public function testGroupByArray()
    {
        $stephenson = new Author();
        $stephenson->setFirstName("Neal");
        $stephenson->setLastName("Stephenson");
        $stephenson->save();

        $byron = new Author();
        $byron->setFirstName("George");
        $byron->setLastName("Byron");
        $byron->save();
        
        $phoenix = new Book();
        $phoenix->setTitle("Harry Potter and the Order of the Phoenix");
        $phoenix->setISBN("043935806X");
        $phoenix->setAuthor($stephenson);
        $phoenix->save();
        
        $qs = new Book();
        $qs->setISBN("0380977427");
        $qs->setTitle("Quicksilver");
        $qs->setAuthor($stephenson);
        $qs->save();

        $dj = new Book();
        $dj->setISBN("0140422161");
        $dj->setTitle("Don Juan");
        $dj->setAuthor($stephenson);
        $dj->save();

        $td = new Book();
        $td->setISBN("067972575X");
        $td->setTitle("The Tin Drum");
        $td->setAuthor($byron);
        $td->save();
        
        $authors = AuthorQuery::create()
            ->leftJoinBook()
            ->select(array('FirstName', 'LastName'))
            ->withColumn('COUNT(Book.Id)', 'nbBooks')
            ->groupBy(array('FirstName', 'LastName'))
            ->orderByLastName()
            ->find();
        
        $expectedSql = 'SELECT COUNT(book.id) AS nbBooks, author.first_name AS "FirstName", author.last_name AS "LastName" FROM author LEFT JOIN book ON (author.id=book.author_id) GROUP BY author.first_name,author.last_name ORDER BY author.last_name ASC';
        
        $this->assertEquals($expectedSql, $this->con->getLastExecutedQuery());
        
        $this->assertEquals(2, count($authors));
        
        $this->assertEquals('George', $authors[0]['FirstName']);
        $this->assertEquals(1, $authors[0]['nbBooks']);
        
        $this->assertEquals('Neal', $authors[1]['FirstName']);
        $this->assertEquals(3, $authors[1]['nbBooks']);
    }
    
    public function dataForTestException()
    {
        return array(
            'empty string' => array(''),
            'null' => array(null),
            'array' => array(array())
        );
    }
}