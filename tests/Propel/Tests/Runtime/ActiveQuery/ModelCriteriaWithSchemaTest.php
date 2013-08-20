<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\Propel;
use Propel\Tests\Helpers\Schemas\SchemasTestBase;
use Propel\Tests\BookstoreSchemas\Map\BookstoreContestTableMap;

use Propel\Runtime\Map\TableMap;
use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * Test class for ModelCriteria withs schemas.
 *
 * @author Francois Zaninotto
 * @version    $Id: ModelCriteriaTest.php 2090 2010-12-13 22:37:03Z francois $
 */
class ModelCriteriaWithSchemaTest extends SchemasTestBase
{

    protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
    {
        $params = array();
        $result = $criteria->createSelectSql($params);

        $this->assertEquals($expectedSql, $result, $message);
        $this->assertEquals($expectedParams, $params, $message);
    }

    public static function conditionsForTestReplaceNamesWithSchemas()
    {
        return array(
            array('BookstoreContest.PrizeBookId = ?', 'PrizeBookId', 'contest.bookstore_contest.PRIZE_BOOK_ID = ?'), // basic case
            array('BookstoreContest.PrizeBookId=?', 'PrizeBookId', 'contest.bookstore_contest.PRIZE_BOOK_ID=?'), // without spaces
            array('BookstoreContest.Id<= ?', 'Id', 'contest.bookstore_contest.ID<= ?'), // with non-equal comparator
            array('BookstoreContest.BookstoreId LIKE ?', 'BookstoreId', 'contest.bookstore_contest.BOOKSTORE_ID LIKE ?'), // with SQL keyword separator
            array('(BookstoreContest.BookstoreId) LIKE ?', 'BookstoreId', '(contest.bookstore_contest.BOOKSTORE_ID) LIKE ?'), // with parenthesis
            array('(BookstoreContest.Id*1.5)=1', 'Id', '(contest.bookstore_contest.ID*1.5)=1') // ignore numbers
        );
    }

    /**
     * @dataProvider conditionsForTestReplaceNamesWithSchemas
     */
    public function testReplaceNamesWithSchemas($origClause, $columnPhpName = false, $modifiedClause)
    {
        $c = new TestableModelCriteriaWithSchema('bookstore-schemas', '\Propel\Tests\BookstoreSchemas\BookstoreContest');
        $this->doTestReplaceNames($c, BookstoreContestTableMap::getTableMap(),  $origClause, $columnPhpName = false, $modifiedClause);
    }

    public function doTestReplaceNames($c, $tableMap, $origClause, $columnPhpName = false, $modifiedClause)
    {
        $this->adapterClass = Propel::getServiceContainer()->getAdapterClass(BookstoreContestTableMap::DATABASE_NAME);
        $c->replaceNames($origClause);
        $columns = $c->replacedColumns;
        if ($columnPhpName) {
            $this->assertEquals(array($tableMap->getColumnByPhpName($columnPhpName)), $columns);
        }
        $modifiedClause = preg_replace('/^(\(?)contest\./', '$1contest' . $this->getPlatform()->getSchemaDelimiter(), $modifiedClause);
        $this->assertEquals($modifiedClause, $origClause);
    }

}

class TestableModelCriteriaWithSchema extends ModelCriteria
{
    public $joins = array();

    public function replaceNames(&$clause)
    {
        return parent::replaceNames($clause);
    }

}
