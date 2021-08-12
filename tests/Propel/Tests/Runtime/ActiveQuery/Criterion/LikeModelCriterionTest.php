<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException;
use Propel\Runtime\ActiveQuery\Criterion\LikeModelCriterion;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for LikeModelCriterion.
 *
 * @author François Zaninotto
 */
class LikeModelCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToCreatesALikeConditionByDefault()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL LIKE ?', 'A.COL', 'foo%');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL LIKE :p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo%'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesANotLikeConditionIfSpecified()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL NOT LIKE ?', 'A.COL', 'foo%');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT LIKE :p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo%'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToWithACaseInsensitiveLikeConditionThrowsAnException()
    {
        $this->expectException(InvalidClauseException::class);

        $cton = new LikeModelCriterion(new Criteria(), 'A.COL LIKE ?', 'A.COL', 'foo%');
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);
        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesACaseInsensitiveLikeConditionIfSpecifiedOnPgSQL()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL LIKE ?', 'A.COL', 'foo%');
        $cton->setAdapter(new PgsqlAdapter());
        $cton->setIgnoreCase(true);
        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL ILIKE :p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo%'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToWithCaseInsensitiveAndPostgreSQLUsesNOTILIKE()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL NOT LIKE ?', 'A.COL', 'foo%');
        $cton->setAdapter(new PgsqlAdapter());
        $cton->setIgnoreCase(true);
        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT ILIKE :p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo%'],
        ];
        $this->assertEquals($expected, $params);
    }
}
