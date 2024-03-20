<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookOpinionTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\ReaderFavoriteTableMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for ModelJoin.
 *
 * @author François Zaninotto
 */
class ModelJoinTest extends TestCaseFixtures
{
    /**
     * @return void
     */
    public function testTableMap()
    {
        $join = new ModelJoin();
        $this->assertNull($join->getTableMap(), 'getTableMap() returns null as long as no table map is set');

        $tmap = new TableMap();
        $tmap->foo = 'bar';

        $join->setTableMap($tmap);
        $this->assertEquals($tmap, $join->getTableMap(), 'getTableMap() returns the TableMap previously set by setTableMap()');
    }

    /**
     * @return void
     */
    public function testSetRelationMap()
    {
        $join = new ModelJoin();
        $this->assertNull($join->getRelationMap(), 'getRelationMap() returns null as long as no relation map is set');
        $bookTable = BookTableMap::getTableMap();
        $relationMap = $bookTable->getRelation('Author');
        $join->setRelationMap($relationMap);
        $this->assertEquals($relationMap, $join->getRelationMap(), 'getRelationMap() returns the RelationMap previously set by setRelationMap()');
    }

    /**
     * @return void
     */
    public function testSetRelationMapDefinesJoinColumns()
    {
        $bookTable = BookTableMap::getTableMap();
        $join = new ModelJoin();
        $join->setTableMap($bookTable);
        $join->setRelationMap($bookTable->getRelation('Author'));
        $this->assertEquals([BookTableMap::COL_AUTHOR_ID], $join->getLeftColumns(), 'setRelationMap() automatically sets the left columns');
        $this->assertEquals([AuthorTableMap::COL_ID], $join->getRightColumns(), 'setRelationMap() automatically sets the right columns');
    }

    /**
     * @return void
     */
    public function testSetRelationMapLeftAlias()
    {
        $bookTable = BookTableMap::getTableMap();
        $join = new ModelJoin();
        $join->setTableMap($bookTable);
        $join->setRelationMap($bookTable->getRelation('Author'), 'b');
        $this->assertEquals(['b.author_id'], $join->getLeftColumns(), 'setRelationMap() automatically sets the left columns using the left table alias');
        $this->assertEquals([AuthorTableMap::COL_ID], $join->getRightColumns(), 'setRelationMap() automatically sets the right columns');
    }

    /**
     * @return void
     */
    public function testSetRelationMapRightAlias()
    {
        $bookTable = BookTableMap::getTableMap();
        $join = new ModelJoin();
        $join->setTableMap($bookTable);
        $join->setRelationMap($bookTable->getRelation('Author'), null, 'a');
        $this->assertEquals([BookTableMap::COL_AUTHOR_ID], $join->getLeftColumns(), 'setRelationMap() automatically sets the left columns');
        $this->assertEquals(['a.id'], $join->getRightColumns(), 'setRelationMap() automatically sets the right columns  using the right table alias');
    }

    /**
     * @return void
     */
    public function testSetRelationMapComposite()
    {
        $table = ReaderFavoriteTableMap::getTableMap();
        $join = new ModelJoin();
        $join->setTableMap($table);
        $join->setRelationMap($table->getRelation('BookOpinion'));
        $this->assertEquals([ReaderFavoriteTableMap::COL_BOOK_ID, ReaderFavoriteTableMap::COL_READER_ID], $join->getLeftColumns(), 'setRelationMap() automatically sets the left columns for composite relationships');
        $this->assertEquals([BookOpinionTableMap::COL_BOOK_ID, BookOpinionTableMap::COL_READER_ID], $join->getRightColumns(), 'setRelationMap() automatically sets the right columns for composite relationships');
    }
}
