<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Propel;
use Propel\Runtime\Map\RelationMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for PHP5TableMapBuilder.
 *
 * @author François Zaninotto
 */
class GeneratedRelationMapTest extends TestCaseFixtures
{
    protected $databaseMap;

    protected function setUp()
    {
        parent::setUp();
        $this->databaseMap = Propel::getServiceContainer()->getDatabaseMap('bookstore');
    }

    public function testGetRightTable()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $authorTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Author');
        $this->assertEquals($authorTable, $bookTable->getRelation('Author')->getRightTable(), 'getRightTable() returns correct table when called on a many to one relationship');
        $this->assertEquals($bookTable, $authorTable->getRelation('Book')->getRightTable(), 'getRightTable() returns correct table when called on a one to many relationship');
        $bookEmpTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployee');
        $bookEmpAccTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $this->assertEquals($bookEmpAccTable, $bookEmpTable->getRelation('BookstoreEmployeeAccount')->getRightTable(), 'getRightTable() returns correct table when called on a one to one relationship');
        $this->assertEquals($bookEmpTable, $bookEmpAccTable->getRelation('BookstoreEmployee')->getRightTable(), 'getRightTable() returns correct table when called on a one to one relationship');
    }

    public function testColumnMappings()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(array('book.author_id' => 'author.id'), $bookTable->getRelation('Author')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
        $this->assertEquals(array('book.author_id' => 'author.id'), $bookTable->getRelation('Author')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns local to foreign when asked left to right for a many to one relationship');

        $authorTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Author');
        $this->assertEquals(array('book.author_id' => 'author.id'), $authorTable->getRelation('Book')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
        $this->assertEquals(array('author.id' => 'book.author_id'), $authorTable->getRelation('Book')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to many relationship');

        $bookEmpTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployee');
        $this->assertEquals(array('bookstore_employee_account.employee_id' => 'bookstore_employee.id'), $bookEmpTable->getRelation('BookstoreEmployeeAccount')->getColumnMappings(), 'getColumnMappings returns local to foreign by default');
        $this->assertEquals(array('bookstore_employee.id' => 'bookstore_employee_account.employee_id'), $bookEmpTable->getRelation('BookstoreEmployeeAccount')->getColumnMappings(RelationMap::LEFT_TO_RIGHT), 'getColumnMappings returns foreign to local when asked left to right for a one to one relationship');
    }

    public function testCountColumnMappings()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(1, $bookTable->getRelation('Author')->countColumnMappings());

        $rfTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\ReaderFavorite');
        $this->assertEquals(2, $rfTable->getRelation('BookOpinion')->countColumnMappings());
    }

    public function testIsComposite()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertFalse($bookTable->getRelation('Author')->isComposite());

        $rfTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\ReaderFavorite');
        $this->assertTrue($rfTable->getRelation('BookOpinion')->isComposite());
    }

}
