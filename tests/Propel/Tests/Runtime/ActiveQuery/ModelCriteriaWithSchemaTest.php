<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\BookstoreSchemas\Map\BookstoreContestTableMap;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Test class for ModelCriteria withs schemas.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ModelCriteriaWithSchemaTest extends TestCaseFixturesDatabase
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
            array('BookstoreContest.PrizeBookId = ?', 'PrizeBookId', 'contest.bookstore_contest.prize_book_id = ?'), // basic case
            array('BookstoreContest.PrizeBookId=?', 'PrizeBookId', 'contest.bookstore_contest.prize_book_id=?'), // without spaces
            array('BookstoreContest.Id<= ?', 'Id', 'contest.bookstore_contest.id<= ?'), // with non-equal comparator
            array('BookstoreContest.BookstoreId LIKE ?', 'BookstoreId', 'contest.bookstore_contest.bookstore_id LIKE ?'), // with SQL keyword separator
            array('(BookstoreContest.BookstoreId) LIKE ?', 'BookstoreId', '(contest.bookstore_contest.bookstore_id) LIKE ?'), // with parenthesis
            array('(BookstoreContest.Id*1.5)=1', 'Id', '(contest.bookstore_contest.id*1.5)=1') // ignore numbers
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

    public function replaceNames(&$sql)
    {
        return parent::replaceNames($sql);
    }

}
