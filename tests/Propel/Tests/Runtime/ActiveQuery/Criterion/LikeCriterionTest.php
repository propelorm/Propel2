<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\LikeCriterion;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for LikeCriterion.
 *
 * @author François Zaninotto
 */
class LikeCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToCreatesALikeConditionByDefault()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);

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
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::NOT_LIKE);

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
    public function testAppendPsToCreatesACaseInsensitiveLikeConditionIfSpecified()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);
        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('UPPER(A.COL) LIKE UPPER(:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo%'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesACaseInsensitiveNotLikeConditionIfSpecified()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::NOT_LIKE);
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);
        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('UPPER(A.COL) NOT LIKE UPPER(:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo%'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToBehavesTheSameOnPostgreSQLByDefault()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);
        $cton->setAdapter(new PgsqlAdapter());
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
    public function testAppendPsToWithCaseInsensitiveAndPostgreSQLUsesILIKE()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);
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
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::NOT_LIKE);
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
