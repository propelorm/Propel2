<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Test class for PHP5TableMapBuilder.
 *
 * @author     François Zaninotto
 * @version    $Id: PHP5TableMapBuilderTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    generator.engine.builder.om.php5
 */
class PHP5TableMapBuilderTest extends BookstoreTestBase 
{ 
  protected $databaseMap;

  protected function setUp()
  {
  	parent::setUp();
    $this->databaseMap = Propel::getDatabaseMap('bookstore');
  }
  
  public function testColumnDefaultValue()
  {
    $table = $this->databaseMap->getTableByPhpName('BookstoreEmployeeAccount');
    $this->assertNull($table->getColumn('login')->getDefaultValue(), 'null default values are correctly mapped');
    $this->assertEquals('\'@\'\'34"', $table->getColumn('password')->getDefaultValue(), 'string default values are correctly escaped and mapped');
    $this->assertTrue($table->getColumn('enabled')->getDefaultValue(), 'boolean default values are correctly mapped');
    $this->assertFalse($table->getColumn('not_enabled')->getDefaultValue(), 'boolean default values are correctly mapped');
    $this->assertEquals('CURRENT_TIMESTAMP', $table->getColumn('created')->getDefaultValue(), 'expression default values are correctly mapped');
    $this->assertNull($table->getColumn('role_id')->getDefaultValue(), 'explicit null default values are correctly mapped');    
  }

  public function testRelationCount()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $this->assertEquals(8, count($bookTable->getRelations()), 'The map builder creates relations for both incoming and outgoing keys');
  }
  
  public function testRelationName()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $this->assertTrue($bookTable->hasRelation('Publisher'), 'The map builder creates relations based on the foreign table name, calemized');
    $this->assertTrue($bookTable->hasRelation('BookListRel'), 'The map builder creates relations based on the foreign table phpName, if provided');
    $bookEmpTable = $this->databaseMap->getTableByPhpName('BookstoreEmployee');
    $this->assertTrue($bookEmpTable->hasRelation('Supervisor'), 'The map builder creates relations based on the foreign key phpName');
    $this->assertTrue($bookEmpTable->hasRelation('Subordinate'), 'The map builder creates relations based on the foreign key refPhpName');
  }
  
  public function testRelationDirection()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $this->assertEquals(RelationMap::MANY_TO_ONE, $bookTable->getRelation('Publisher')->getType(), 'The map builder creates MANY_TO_ONE relations for every foreign key');
    $this->assertEquals(RelationMap::MANY_TO_ONE, $bookTable->getRelation('Author')->getType(), 'The map builder creates MANY_TO_ONE relations for every foreign key');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $bookTable->getRelation('Review')->getType(), 'The map builder creates ONE_TO_MANY relations for every incoming foreign key');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $bookTable->getRelation('Media')->getType(), 'The map builder creates ONE_TO_MANY relations for every incoming foreign key');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $bookTable->getRelation('BookListRel')->getType(), 'The map builder creates ONE_TO_MANY relations for every incoming foreign key');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $bookTable->getRelation('BookOpinion')->getType(), 'The map builder creates ONE_TO_MANY relations for every incoming foreign key');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $bookTable->getRelation('ReaderFavorite')->getType(), 'The map builder creates ONE_TO_MANY relations for every incoming foreign key');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $bookTable->getRelation('BookstoreContest')->getType(), 'The map builder creates ONE_TO_MANY relations for every incoming foreign key');
    $bookEmpTable = $this->databaseMap->getTableByPhpName('BookstoreEmployee');
    $this->assertEquals(RelationMap::ONE_TO_ONE, $bookEmpTable->getRelation('BookstoreEmployeeAccount')->getType(), 'The map builder creates ONE_TO_ONE relations for every incoming foreign key to a primary key');
  }
  
  public function testRelationsColumns()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $expectedMapping = array('book.PUBLISHER_ID' => 'publisher.ID');
    $this->assertEquals($expectedMapping, $bookTable->getRelation('Publisher')->getColumnMappings(), 'The map builder adds columns in the correct order for foreign keys');
    $expectedMapping = array('review.BOOK_ID' => 'book.ID');
    $this->assertEquals($expectedMapping, $bookTable->getRelation('Review')->getColumnMappings(), 'The map builder adds columns in the correct order for incoming foreign keys');
    $publisherTable = $this->databaseMap->getTableByPhpName('Publisher');
    $expectedMapping = array('book.PUBLISHER_ID' => 'publisher.ID');
    $this->assertEquals($expectedMapping, $publisherTable->getRelation('Book')->getColumnMappings(), 'The map builder adds local columns where the foreign key lies');
    $rfTable = $this->databaseMap->getTableByPhpName('ReaderFavorite');
    $expectedMapping = array(
      'reader_favorite.BOOK_ID'    => 'book_opinion.BOOK_ID',
      'reader_favorite.READER_ID' => 'book_opinion.READER_ID'
    );
    $this->assertEquals($expectedMapping, $rfTable->getRelation('BookOpinion')->getColumnMappings(), 'The map builder adds all columns for composite foreign keys');
  }
  
  public function testRelationOnDelete()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $this->assertEquals('SET NULL', $bookTable->getRelation('Publisher')->getOnDelete(), 'The map builder adds columns with the correct onDelete');
  }
  
  public function testRelationOnUpdate()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $this->assertNull($bookTable->getRelation('Publisher')->getOnUpdate(), 'The map builder adds columns with onDelete null by default');
    $this->assertEquals('CASCADE', $bookTable->getRelation('Author')->getOnUpdate(), 'The map builder adds columns with the correct onUpdate');
  }

  public function testBehaviors()
  {
    $bookTable = $this->databaseMap->getTableByPhpName('Book');
    $this->assertEquals($bookTable->getBehaviors(), array(), 'getBehaviors() returns an empty array when no behaviors are registered');
    $tmap = Propel::getDatabaseMap(Table1Peer::DATABASE_NAME)->getTable(Table1Peer::TABLE_NAME);
    $expectedBehaviorParams = array('timestampable' => array('add_columns' => 'false', 'create_column' => 'created_on', 'update_column' => 'updated_on'), 'do_nothing' => array('foo' => 'bar'));
    $this->assertEquals($tmap->getBehaviors(), $expectedBehaviorParams, 'The map builder creates a getBehaviors() method to retrieve behaviors parameters when behaviors are registered');
  }  
}
