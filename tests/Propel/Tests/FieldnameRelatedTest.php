<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use Propel\Tests\Bookstore\Map\BookTableMap;

use Propel\Runtime\Map\TableMap;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Bookstore\Review;

/**
 * Tests some of the methods of generated Object classes. These are:
 *
 * - Base[Object]TableMap::getFieldNames()
 * - Base[Object]TableMap::translateFieldName()
 * - TableMap::getFieldNames()
 * - TableMap::translateFieldName()
 * - Base[Object]::getByName()
 * - Base[Object]::setByName()
 * - Base[Object]::fromArray()
 * - Base[Object]::toArray()
 *
 * I've pulled these tests from the GeneratedObjectTest because the don't
 * need the BookstoreTestBase's setUp and tearDown (database de/population)
 * behaviour. The tests will run faster this way.
 *
 * @author Sven Fuchs <svenfuchs@artweb-design.de>
 */
class FieldnameRelatedTest extends TestCaseFixtures
{
    /**
     * Tests if fieldname type constants are defined
     */
    public function testFieldNameTypeConstants ()
    {
        $result = defined('\Propel\Runtime\Map\TableMap::TYPE_PHPNAME');
        $this->assertTrue($result);
    }

    /**
     * Tests the Base[Object]TableMap::getFieldNames() method
     */
    public function testGetFieldNames ()
    {
        $types = array(
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        );
        $expecteds = array (
            TableMap::TYPE_PHPNAME => array(
                0 => 'Id',
                1 => 'Title',
                2 => 'ISBN',
                3 => 'Price',
                4 => 'PublisherId',
                5 => 'AuthorId'
            ),
            TableMap::TYPE_CAMELNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'iSBN',
                3 => 'price',
                4 => 'publisherId',
                5 => 'authorId'
            ),
            TableMap::TYPE_COLNAME => array(
                0 => 'book.ID',
                1 => 'book.TITLE',
                2 => 'book.ISBN',
                3 => 'book.PRICE',
                4 => 'book.PUBLISHER_ID',
                5 => 'book.AUTHOR_ID'
            ),
            TableMap::TYPE_FIELDNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'isbn',
                3 => 'price',
                4 => 'publisher_id',
                5 => 'author_id'
            ),
            TableMap::TYPE_NUM => array(
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5
            )
        );

        foreach ($types as $type) {
            $results[$type] = BookTableMap::getFieldnames($type);
            $this->assertEquals(
                $expecteds[$type],
                $results[$type],
                'expected was: ' . print_r($expecteds[$type], 1) .
                'but getFieldnames() returned ' . print_r($results[$type], 1)
            );
        }
    }

    /**
     * Tests the Base[Object]TableMap::translateFieldName() method
     */
    public function testTranslateFieldName ()
    {
        $types = array(
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_CAMELNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        );
        $expecteds = array (
            TableMap::TYPE_PHPNAME => 'AuthorId',
            TableMap::TYPE_CAMELNAME => 'authorId',
            TableMap::TYPE_COLNAME => 'book.AUTHOR_ID',
            TableMap::TYPE_FIELDNAME => 'author_id',
            TableMap::TYPE_NUM => 5,
        );
        foreach ($types as $fromType) {
            foreach ($types as $toType) {
                $name = $expecteds[$fromType];
                $expected = $expecteds[$toType];
                $result = BookTableMap::translateFieldName($name, $fromType, $toType);
                $this->assertEquals($expected, $result);
            }
        }
    }

    /**
     * Tests the BaseTableMap::getFieldNames() method
     */
    public function testGetFieldNamesStatic ()
    {
        $types = array(
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_CAMELNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        );
        $expecteds = array (
            TableMap::TYPE_PHPNAME => array(
                0 => 'Id',
                1 => 'Title',
                2 => 'ISBN',
                3 => 'Price',
                4 => 'PublisherId',
                5 => 'AuthorId'
            ),
            TableMap::TYPE_CAMELNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'iSBN',
                3 => 'price',
                4 => 'publisherId',
                5 => 'authorId'
            ),
            TableMap::TYPE_COLNAME => array(
                0 => 'book.ID',
                1 => 'book.TITLE',
                2 => 'book.ISBN',
                3 => 'book.PRICE',
                4 => 'book.PUBLISHER_ID',
                5 => 'book.AUTHOR_ID'
            ),
            TableMap::TYPE_FIELDNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'isbn',
                3 => 'price',
                4 => 'publisher_id',
                5 => 'author_id'
            ),
            TableMap::TYPE_NUM => array(
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5
            )
        );

        foreach ($types as $type) {
            $results[$type] = TableMap::getFieldnamesForClass('\Propel\Tests\Bookstore\Book', $type);
            $this->assertEquals(
                $expecteds[$type],
                $results[$type],
                'expected was: ' . print_r($expecteds[$type], 1) .
                'but getFieldnames() returned ' . print_r($results[$type], 1)
            );
        }
    }

    /**
     * Tests the BaseTableMap::translateFieldName() method
     */
    public function testTranslateFieldNameStatic ()
    {
        $types = array(
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_CAMELNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        );
        $expecteds = array (
            TableMap::TYPE_PHPNAME => 'AuthorId',
            TableMap::TYPE_CAMELNAME => 'authorId',
            TableMap::TYPE_COLNAME => 'book.AUTHOR_ID',
            TableMap::TYPE_FIELDNAME => 'author_id',
            TableMap::TYPE_NUM => 5,
        );
        foreach ($types as $fromType) {
            foreach ($types as $toType) {
                $name = $expecteds[$fromType];
                $expected = $expecteds[$toType];
                $result = TableMap::translateFieldNameForClass('\Propel\Tests\Bookstore\Book', $name, $fromType, $toType);
                $this->assertEquals($expected, $result);
            }
        }
    }

    /**
     * Tests the Base[Object]::getByName() method
     */
    public function testGetByName()
    {
        $types = array(
            TableMap::TYPE_PHPNAME => 'Title',
            TableMap::TYPE_CAMELNAME => 'title',
            TableMap::TYPE_COLNAME => 'book.TITLE',
            TableMap::TYPE_FIELDNAME => 'title',
            TableMap::TYPE_NUM => 1
        );

        $book = new Book();
        $book->setTitle('Harry Potter and the Order of the Phoenix');

        $expected = 'Harry Potter and the Order of the Phoenix';
        foreach ($types as $type => $name) {
            $result = $book->getByName($name, $type);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Tests the Base[Object]::setByName() method
     */
    public function testSetByName()
    {
        $book = new Book();
        $types = array(
            TableMap::TYPE_PHPNAME => 'Title',
            TableMap::TYPE_CAMELNAME => 'title',
            TableMap::TYPE_COLNAME => 'book.TITLE',
            TableMap::TYPE_FIELDNAME => 'title',
            TableMap::TYPE_NUM => 1
        );

        $title = 'Harry Potter and the Order of the Phoenix';
        foreach ($types as $type => $name) {
            $book->setByName($name, $title, $type);
            $result = $book->getTitle();
            $this->assertEquals($title, $result);
        }
    }

    /**
     * Tests the Base[Object]::fromArray() method
     *
     * this also tests populateFromArray() because that's an alias
     */
    public function testFromArray()
    {
        $types = array(
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_CAMELNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        );
        $expecteds = array (
            TableMap::TYPE_PHPNAME => array (
                'Title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X'
            ),
            TableMap::TYPE_CAMELNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'iSBN' => '043935806X'
            ),
            TableMap::TYPE_COLNAME => array (
                'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
                'book.ISBN' => '043935806X'
            ),
            TableMap::TYPE_FIELDNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'isbn' => '043935806X'
            ),
            TableMap::TYPE_NUM => array (
                '1' => 'Harry Potter and the Order of the Phoenix',
                '2' => '043935806X'
            )
        );

        $book = new Book();

        foreach ($types as $type) {
            $expected = $expecteds[$type];
            $book->fromArray($expected, $type);
            $result = array();
            foreach (array_keys($expected) as $key) {
                $result[$key] = $book->getByName($key, $type);
            }
            $this->assertEquals(
                $expected,
                $result,
                'expected was: ' . print_r($expected, 1) .
                'but fromArray() returned ' . print_r($result, 1)
            );
        }
    }

    /**
     * Tests the Base[Object]::toArray() method
     */
    public function testToArray()
    {
        $types = array(
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_CAMELNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        );

        $book = new Book();
        $book->fromArray(array (
            'Title' => 'Harry Potter and the Order of the Phoenix',
            'ISBN' => '043935806X'
        ));

        $expecteds = array (
            TableMap::TYPE_PHPNAME => array (
                'Title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X'
            ),
            TableMap::TYPE_CAMELNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'iSBN' => '043935806X'
            ),
            TableMap::TYPE_COLNAME => array (
                'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
                'book.ISBN' => '043935806X'
            ),
            TableMap::TYPE_FIELDNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'isbn' => '043935806X'
            ),
            TableMap::TYPE_NUM => array (
                '1' => 'Harry Potter and the Order of the Phoenix',
                '2' => '043935806X'
            )
        );

        foreach ($types as $type) {
            $expected = $expecteds[$type];
            $result = $book->toArray($type);
            // remove ID since its autoincremented at each test iteration
            $result = array_slice($result, 1, 2, true);
            $this->assertEquals(
                $expected,
                $result,
                'expected was: ' . print_r($expected, 1) .
                'but toArray() returned ' . print_r($result, 1)
            );
        }
    }

    /**
     * @see https://github.com/propelorm/Propel2/issues/648
     */
    public function testToArrayWithForeignObjectsDoesNotHavePrefix()
    {
        $book = new Book();
        $book->addReview(new Review());

        $array = $book->toArray(TableMap::TYPE_PHPNAME, false, [], true);

        $this->assertArrayHasKey('Reviews', $array);
        $this->assertArrayNotHasKey('Review_0', $array['Reviews']);
        $this->assertArrayHasKey(0, $array['Reviews']);
    }

    public function testToArrayWithForeignObjects()
    {
        $types = [
            TableMap::TYPE_PHPNAME,
            TableMap::TYPE_CAMELNAME,
            TableMap::TYPE_COLNAME,
            TableMap::TYPE_FIELDNAME,
            TableMap::TYPE_NUM
        ];

        $review = new Review();
        $review->setRecommended(true)->setReviewedBy('Someone')->setReviewDate(null);

        $book = new Book();
        $book->setTitle('Harry Potter and the Order of the Phoenix')
            ->setISBN('043935806X')
            ->addReview($review)
            ->setPrice(10);

        $expecteds = array (
            TableMap::TYPE_PHPNAME => [
                'Id' => null,
                'Title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X',
                'Price' => 10.0,
                'PublisherId' => null,
                'AuthorId' => null,
                'Reviews' => [
                    [
                        'Id' => null,
                        'ReviewedBy' => 'Someone',
                        'ReviewDate' => null,
                        'Recommended' => true,
                        'Status' => null,
                        'BookId' => null,
                        'Book' => '*RECURSION*'
                    ]
                ]
            ],
            TableMap::TYPE_CAMELNAME => array (
                'id' => null,
                'title' => 'Harry Potter and the Order of the Phoenix',
                'iSBN' => '043935806X',
                'price' => 10.0,
                'publisherId' => null,
                'authorId' => null,
                'reviews' => [
                    [
                        'id' => null,
                        'reviewedBy' => 'Someone',
                        'reviewDate' => null,
                        'recommended' => true,
                        'status' => null,
                        'bookId' => null,
                        'book' => '*RECURSION*'
                    ]
                ]
            ),
            TableMap::TYPE_COLNAME => array (
                'book.ID' => null,
                'book.TITLE' => 'Harry Potter and the Order of the Phoenix',
                'book.ISBN' => '043935806X',
                'book.PRICE' => 10.0,
                'book.PUBLISHER_ID' => null,
                'book.AUTHOR_ID' => null,
                'Reviews' => [
                    [
                        'review.ID' => null,
                        'review.REVIEWED_BY' => 'Someone',
                        'review.REVIEW_DATE' => null,
                        'review.RECOMMENDED' => true,
                        'review.STATUS' => null,
                        'review.BOOK_ID' => null,
                        'Book' => '*RECURSION*'
                    ]
                ]
            ),
            TableMap::TYPE_FIELDNAME => array (
                'id' => null,
                'title' => 'Harry Potter and the Order of the Phoenix',
                'isbn' => '043935806X',
                'price' => 10.0,
                'publisher_id' => null,
                'author_id' => null,
                'reviews' => [
                    [
                        'id' => null,
                        'reviewed_by' => 'Someone',
                        'review_date' => null,
                        'recommended' => true,
                        'status' => null,
                        'book_id' => null,
                        'book' => '*RECURSION*'
                    ]
                ]
            ),
            TableMap::TYPE_NUM => array (
                '0' => null,
                '1' => 'Harry Potter and the Order of the Phoenix',
                '2' => '043935806X',
                '3' => 10.0,
                '4' => null,
                '5' => null,
                'Reviews' => [
                    [
                        '0' => null,
                        '1' => 'Someone',
                        '2' => null,
                        '3' => 1,
                        '4' => null,
                        '5' => null,
                        'Book' => '*RECURSION*',
                    ]
                ]
            )
        );

        foreach ($types as $type) {
            $expected = $expecteds[$type];
            $result = $book->toArray($type, true, [], true);
            $this->assertEquals(
                $expected,
                $result,
                'expected was: ' . print_r($expected, 1) .
                'but toArray() returned ' . print_r($result, 1)
            );
        }
    }
}
