<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use Propel\Tests\Bookstore\Map\BookEntityMap;

use Propel\Runtime\Map\EntityMap;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Map\ReviewEntityMap;
use Propel\Tests\Bookstore\Review;

/**
 * Tests some of the methods of generated Object classes. These are:
 *
 * - Base[Object]EntityMap::getFieldNames()
 * - Base[Object]EntityMap::translateFieldName()
 * - EntityMap::getFieldNames()
 * - EntityMap::translateFieldName()
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
     * Tests the Base[Object]EntityMap::getFieldNames() method
     */
    public function testGetFieldNames ()
    {
        $types = array(
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        );
        $expecteds = array (
            EntityMap::TYPE_PHPNAME => array(
                0 => 'Id',
                1 => 'Title',
                2 => 'ISBN',
                3 => 'Price',
                4 => 'PublisherId',
                5 => 'AuthorId'
            ),
            EntityMap::TYPE_COLNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'isbn',
                3 => 'price',
                4 => 'publisher_id',
                5 => 'author_id'
            ),
            EntityMap::TYPE_FULLCOLNAME => array(
                0 => 'book.id',
                1 => 'book.title',
                2 => 'book.isbn',
                3 => 'book.price',
                4 => 'book.publisher_id',
                5 => 'book.author_id'
            ),
            EntityMap::TYPE_FIELDNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'ISBN',
                3 => 'price',
                4 => 'publisherId',
                5 => 'authorId'
            ),
            EntityMap::TYPE_NUM => array(
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5
            )
        );

        foreach ($types as $type) {
            $entityMap = $this->configuration->getEntityMap(BookEntityMap::ENTITY_CLASS);
            $results[$type] = $entityMap->getFieldnames($type);
            $this->assertEquals(
                $expecteds[$type],
                $results[$type],
                'expected was: ' . print_r($expecteds[$type], 1) .
                'but getFieldnames() returned ' . print_r($results[$type], 1)
            );
        }
    }

    /**
     * Tests the Base[Object]EntityMap::translateFieldName() method
     */
    public function testTranslateFieldName ()
    {
        $types = array(
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        );
        $expecteds = array (
            EntityMap::TYPE_PHPNAME => 'AuthorId',
            EntityMap::TYPE_COLNAME => 'author_id',
            EntityMap::TYPE_FULLCOLNAME => 'book.author_id',
            EntityMap::TYPE_FIELDNAME => 'authorId',
            EntityMap::TYPE_NUM => 5,
        );
        foreach ($types as $fromType) {
            foreach ($types as $toType) {
                $name = $expecteds[$fromType];
                $expected = $expecteds[$toType];
                $entityMap = $this->configuration->getEntityMap(BookEntityMap::ENTITY_CLASS);
                $result = $entityMap->translateFieldName($name, $fromType, $toType);
                $this->assertEquals($expected, $result);
            }
        }
    }

    /**
     * Tests the BaseEntityMap::getFieldNames() method
     */
    public function testGetFieldNamesStatic ()
    {
        $types = array(
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        );
        $expecteds = array (
            EntityMap::TYPE_PHPNAME => array(
                0 => 'Id',
                1 => 'Title',
                2 => 'ISBN',
                3 => 'Price',
                4 => 'PublisherId',
                5 => 'AuthorId'
            ),
            EntityMap::TYPE_COLNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'isbn',
                3 => 'price',
                4 => 'publisher_id',
                5 => 'author_id'
            ),
            EntityMap::TYPE_FULLCOLNAME => array(
                0 => 'book.id',
                1 => 'book.title',
                2 => 'book.isbn',
                3 => 'book.price',
                4 => 'book.publisher_id',
                5 => 'book.author_id'
            ),
            EntityMap::TYPE_FIELDNAME => array(
                0 => 'id',
                1 => 'title',
                2 => 'ISBN',
                3 => 'price',
                4 => 'publisherId',
                5 => 'authorId'
            ),
            EntityMap::TYPE_NUM => array(
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5
            )
        );

        foreach ($types as $type) {
            $entityMap = $this->configuration->getEntityMap('\Propel\Tests\Bookstore\Book');
            $results[$type] = $entityMap->getFieldnames($type);
            $this->assertEquals(
                $expecteds[$type],
                $results[$type],
                'expected was: ' . print_r($expecteds[$type], 1) .
                'but getFieldnames() returned ' . print_r($results[$type], 1)
            );
        }
    }

    /**
     * Tests the BaseEntityMap::translateFieldName() method
     */
    public function testTranslateFieldNameStatic ()
    {
        $types = array(
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        );
        $expecteds = array (
            EntityMap::TYPE_PHPNAME => 'AuthorId',
            EntityMap::TYPE_COLNAME => 'author_id',
            EntityMap::TYPE_FULLCOLNAME => 'book.author_id',
            EntityMap::TYPE_FIELDNAME => 'authorId',
            EntityMap::TYPE_NUM => 5,
        );
        foreach ($types as $fromType) {
            foreach ($types as $toType) {
                $name = $expecteds[$fromType];
                $expected = $expecteds[$toType];
                $result = $this->configuration->getEntityMap('\Propel\Tests\Bookstore\Book')->translateFieldName($name, $fromType, $toType);
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
            EntityMap::TYPE_PHPNAME => 'Title',
            EntityMap::TYPE_COLNAME => 'title',
            EntityMap::TYPE_FULLCOLNAME => 'book.title',
            EntityMap::TYPE_FIELDNAME => 'title',
            EntityMap::TYPE_NUM => 1
        );

        $book = new Book();
        $expected = 'Harry Potter and the Order of the Phoenix';
        $book->setTitle($expected);

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
            EntityMap::TYPE_PHPNAME => 'Title',
            EntityMap::TYPE_COLNAME => 'title',
            EntityMap::TYPE_FULLCOLNAME => 'book.title',
            EntityMap::TYPE_FIELDNAME => 'title',
            EntityMap::TYPE_NUM => 1
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
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        );
        $expecteds = array (
            EntityMap::TYPE_PHPNAME => array (
                'Title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X'
            ),
            EntityMap::TYPE_COLNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'isbn' => '043935806X'
            ),
            EntityMap::TYPE_FULLCOLNAME => array (
                'book.title' => 'Harry Potter and the Order of the Phoenix',
                'book.isbn' => '043935806X'
            ),
            EntityMap::TYPE_FIELDNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X'
            ),
            EntityMap::TYPE_NUM => array (
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
                "type $type expected was: " . print_r($expected, 1) .
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
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        );

        $book = new Book();
        $book->fromArray(array (
            'title' => 'Harry Potter and the Order of the Phoenix',
            'ISBN' => '043935806X'
        ));

        $expecteds = array (
            EntityMap::TYPE_PHPNAME => array (
                'Title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X'
            ),
            EntityMap::TYPE_COLNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'isbn' => '043935806X'
            ),
            EntityMap::TYPE_FULLCOLNAME => array (
                'book.title' => 'Harry Potter and the Order of the Phoenix',
                'book.isbn' => '043935806X'
            ),
            EntityMap::TYPE_FIELDNAME => array (
                'title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X'
            ),
            EntityMap::TYPE_NUM => array (
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

        $array = $book->toArray(EntityMap::TYPE_FIELDNAME, false, true);

        $this->assertArrayHasKey('reviews', $array);
        $this->assertArrayNotHasKey('review_0', $array['reviews']);
        $this->assertArrayHasKey(0, $array['reviews']);
    }

    public function testToArrayWithForeignObjects()
    {
        $types = [
            EntityMap::TYPE_PHPNAME,
            EntityMap::TYPE_COLNAME,
            EntityMap::TYPE_FULLCOLNAME,
            EntityMap::TYPE_FIELDNAME,
            EntityMap::TYPE_NUM
        ];

        $review = new Review();
        $review->setRecommended(true)->setReviewedBy('Someone')->setReviewDate(null);

        $book = new Book();
        $book->setTitle('Harry Potter and the Order of the Phoenix')
            ->setISBN('043935806X')
            ->addReview($review)
            ->setPrice(10);

        $expecteds = array (
            EntityMap::TYPE_PHPNAME => [
                'Id' => null,
                'Title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X',
                'Price' => 10.0,
                'Reviews' => [
                    [
                        'Id' => null,
                        'ReviewedBy' => 'Someone',
                        'ReviewDate' => null,
                        'Recommended' => true,
                        'Status' => null,
                        'Book' => '*RECURSION*'
                    ]
                ]
            ],
            EntityMap::TYPE_COLNAME => array (
                'id' => null,
                'title' => 'Harry Potter and the Order of the Phoenix',
                'isbn' => '043935806X',
                'price' => 10.0,
                'reviews' => [
                    [
                        'id' => null,
                        'reviewed_by' => 'Someone',
                        'review_date' => null,
                        'recommended' => true,
                        'status' => null,
                        'book' => '*RECURSION*'
                    ]
                ]
            ),
            EntityMap::TYPE_FULLCOLNAME => array (
                'book.id' => null,
                'book.title' => 'Harry Potter and the Order of the Phoenix',
                'book.isbn' => '043935806X',
                'book.price' => 10.0,
                'reviews' => [
                    [
                        'review.id' => null,
                        'review.reviewed_by' => 'Someone',
                        'review.review_date' => null,
                        'review.recommended' => true,
                        'review.status' => null,
                        'book' => '*RECURSION*'
                    ]
                ]
            ),
            EntityMap::TYPE_FIELDNAME => array (
                'id' => null,
                'title' => 'Harry Potter and the Order of the Phoenix',
                'ISBN' => '043935806X',
                'price' => 10.0,
                'reviews' => [
                    [
                        'id' => null,
                        'reviewedBy' => 'Someone',
                        'reviewDate' => null,
                        'recommended' => true,
                        'status' => null,
                        'book' => '*RECURSION*'
                    ]
                ]
            ),
            EntityMap::TYPE_NUM => array (
                '0' => null,
                '1' => 'Harry Potter and the Order of the Phoenix',
                '2' => '043935806X',
                '3' => 10.0,
                'reviews' => [
                    [
                        '0' => null,
                        '1' => 'Someone',
                        '2' => null,
                        '3' => true,
                        '4' => null,
                        'book' => '*RECURSION*',
                    ]
                ]
            )
        );

        foreach ($types as $type) {
            $expected = $expecteds[$type];
            $result = $book->toArray($type, true, true);
            $this->assertEquals(
                $expected,
                $result,
                'expected was: ' . print_r($expected, 1) .
                'but toArray() with type '.$type.' returned ' . print_r($result, 1)
            );
        }
    }
}
