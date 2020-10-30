<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\BookstoreSchemas\Map\BookstoreContestTableMap;
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
    /**
     * @return void
     */
    protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
    {
        $params = [];
        $result = $criteria->createSelectSql($params);

        $this->assertEquals($expectedSql, $result, $message);
        $this->assertEquals($expectedParams, $params, $message);
    }

    public static function conditionsForTestReplaceNamesWithSchemas()
    {
        return [
            ['BookstoreContest.PrizeBookId = ?', 'PrizeBookId', 'contest.bookstore_contest.prize_book_id = ?'], // basic case
            ['BookstoreContest.PrizeBookId=?', 'PrizeBookId', 'contest.bookstore_contest.prize_book_id=?'], // without spaces
            ['BookstoreContest.Id<= ?', 'Id', 'contest.bookstore_contest.id<= ?'], // with non-equal comparator
            ['BookstoreContest.BookstoreId LIKE ?', 'BookstoreId', 'contest.bookstore_contest.bookstore_id LIKE ?'], // with SQL keyword separator
            ['(BookstoreContest.BookstoreId) LIKE ?', 'BookstoreId', '(contest.bookstore_contest.bookstore_id) LIKE ?'], // with parenthesis
            ['(BookstoreContest.Id*1.5)=1', 'Id', '(contest.bookstore_contest.id*1.5)=1'], // ignore numbers
        ];
    }

    /**
     * @dataProvider conditionsForTestReplaceNamesWithSchemas
     *
     * @return void
     */
    public function testReplaceNamesWithSchemas($origClause, $columnPhpName, $modifiedClause)
    {
        $c = new TestableModelCriteriaWithSchema('bookstore-schemas', '\Propel\Tests\BookstoreSchemas\BookstoreContest');
        $this->doTestReplaceNames($c, BookstoreContestTableMap::getTableMap(), $origClause, $columnPhpName, $modifiedClause);
    }

    /**
     * @return void
     */
    public function doTestReplaceNames($c, $tableMap, $origClause, $columnPhpName, $modifiedClause)
    {
        $c->replaceNames($origClause);
        $columns = $c->replacedColumns;
        if ($columnPhpName) {
            $this->assertEquals([$tableMap->getColumnByPhpName($columnPhpName)], $columns);
        }
        $modifiedClause = preg_replace('/^(\(?)contest\./', '$1contest' . $this->getPlatform()->getSchemaDelimiter(), $modifiedClause);
        $this->assertEquals($modifiedClause, $origClause);
    }
}

class TestableModelCriteriaWithSchema extends ModelCriteria
{
    public $joins = [];

    public function replaceNames(&$sql)
    {
        return parent::replaceNames($sql);
    }
}
