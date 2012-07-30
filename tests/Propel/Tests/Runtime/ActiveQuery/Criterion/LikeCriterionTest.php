<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\LikeCriterion;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;

/**
 * Test class for LikeCriterion.
 *
 * @author François Zaninotto
 */
class LikeCriterionTest extends BaseTestCase
{
    public function testAppendPsToCreatesALikeConditionByDefault()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL LIKE :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesANotLikeConditionIfSpecified()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::NOT_LIKE);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT LIKE :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesACaseInsensitiveLikeConditionIfSpecified()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);
        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('UPPER(A.COL) LIKE UPPER(:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesACaseInsensitiveNotLikeConditionIfSpecified()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::NOT_LIKE);
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);
        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('UPPER(A.COL) NOT LIKE UPPER(:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToBehavesTheSameOnPostgreSQLByDefault()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);
        $cton->setAdapter(new PgsqlAdapter());
        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL LIKE :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithCaseInsensitiveAndPostgreSQLUsesILIKE()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::LIKE);
        $cton->setAdapter(new PgsqlAdapter());
        $cton->setIgnoreCase(true);
        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL ILIKE :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithCaseInsensitiveAndPostgreSQLUsesNOTILIKE()
    {
        $cton = new LikeCriterion(new Criteria(), 'A.COL', 'foo%', Criteria::NOT_LIKE);
        $cton->setAdapter(new PgsqlAdapter());
        $cton->setIgnoreCase(true);
        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT ILIKE :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

}
