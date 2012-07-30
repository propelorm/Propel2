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
use Propel\Runtime\ActiveQuery\Criterion\LikeModelCriterion;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;

/**
 * Test class for LikeModelCriterion.
 *
 * @author François Zaninotto
 */
class LikeModelCriterionTest extends BaseTestCase
{
    public function testAppendPsToCreatesALikeConditionByDefault()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL LIKE ?', 'A.COL', 'foo%');

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
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL NOT LIKE ?', 'A.COL', 'foo%');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT LIKE :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo%')
        );
        $this->assertEquals($expected, $params);
    }

    /**
     * @expectedException Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException
     */
    public function testAppendPsToWithACaseInsensitiveLikeConditionThrowsAnException()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL LIKE ?', 'A.COL', 'foo%');
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);
        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    public function testAppendPsToCreatesACaseInsensitiveLikeConditionIfSpecifiedOnPgSQL()
    {
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL LIKE ?', 'A.COL', 'foo%');
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
        $cton = new LikeModelCriterion(new Criteria(), 'A.COL NOT LIKE ?', 'A.COL', 'foo%');
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
