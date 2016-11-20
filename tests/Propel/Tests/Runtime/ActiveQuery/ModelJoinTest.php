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

use Propel\Tests\Bookstore\Map\AuthorEntityMap;
use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\Bookstore\Map\BookOpinionEntityMap;
use Propel\Tests\Bookstore\Map\ReaderFavoriteEntityMap;

use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Map\EntityMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for ModelJoin.
 *
 * @author François Zaninotto
 */
class ModelJoinTest extends TestCaseFixtures
{
    public function testSetRelationMap()
    {
        $join = new ModelJoin();
        $this->assertNull($join->getRelationMap(), 'getRelationMap() returns null as long as no relation map is set');
        $bookTable = BookEntityMap::getEntityMap();
        $relationMap = $bookTable->getRelation('author');
        $join->setRelationMap($relationMap);
        $this->assertEquals($relationMap, $join->getRelationMap(), 'getRelationMap() returns the RelationMap previously set by setRelationMap()');
    }

    public function testSetRelationMapDefinesJoinFields()
    {
        $bookTable = BookEntityMap::getEntityMap();
        $join = new ModelJoin();
        $join->setEntityMap($bookTable);
        $join->setRelationMap($bookTable->getRelation('author'));
        $this->assertEquals(array(BookEntityMap::FIELD_AUTHOR_ID), $join->getLeftFields(), 'setRelationMap() automatically sets the left fields');
        $this->assertEquals(array(AuthorEntityMap::FIELD_ID), $join->getRightFields(), 'setRelationMap() automatically sets the right fields');
    }

    public function testSetRelationMapLeftAlias()
    {
        $bookTable = BookEntityMap::getEntityMap();
        $join = new ModelJoin();
        $join->setEntityMap($bookTable);
        $join->setRelationMap($bookTable->getRelation('author'), 'b');
        $this->assertEquals(array('b.authorId'), $join->getLeftFields(), 'setRelationMap() automatically sets the left fields using the left table alias');
        $this->assertEquals(array(AuthorEntityMap::FIELD_ID), $join->getRightFields(), 'setRelationMap() automatically sets the right fields');
    }

    public function testSetRelationMapRightAlias()
    {
        $bookTable = BookEntityMap::getEntityMap();
        $join = new ModelJoin();
        $join->setEntityMap($bookTable);
        $join->setRelationMap($bookTable->getRelation('author'), null, 'a');
        $this->assertEquals(array(BookEntityMap::FIELD_AUTHOR_ID), $join->getLeftFields(), 'setRelationMap() automatically sets the left fields');
        $this->assertEquals(array('a.id'), $join->getRightFields(), 'setRelationMap() automatically sets the right fields  using the right table alias');
    }

    public function testSetRelationMapComposite()
    {
        $table = ReaderFavoriteEntityMap::getEntityMap();
        $join = new ModelJoin();
        $join->setEntityMap($table);
        $join->setRelationMap($table->getRelation('bookOpinion'));
        $this->assertEquals(array(ReaderFavoriteEntityMap::FIELD_BOOK_ID, ReaderFavoriteEntityMap::FIELD_READER_ID), $join->getLeftFields(), 'setRelationMap() automatically sets the left fields for composite relationships');
        $this->assertEquals(array(BookOpinionEntityMap::FIELD_BOOK_ID, BookOpinionEntityMap::FIELD_READER_ID), $join->getRightFields(), 'setRelationMap() automatically sets the right fields for composite relationships');
    }

}
