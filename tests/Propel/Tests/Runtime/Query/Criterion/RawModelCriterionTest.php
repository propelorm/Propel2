<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Query\Criterion;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Query\Criterion\RawModelCriterion;

use \PDO;

/**
 * Test class for RawModelCriterion.
 *
 * @author François Zaninotto
 */
class RawModelCriterionTest extends BaseTestCase
{
    /**
     * @expectedException Propel\Runtime\Query\Criterion\Exception\InvalidClauseException
     */
    public function testAppendPsToThrowsExceptionWhenClauseHasNoQuestionMark()
    {
        $cton = new RawModelCriterion(new Criteria(), 'A.COL', 1, 'A.COL = BAR', PDO::PARAM_INT);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    public function testAppendPsToCreatesAPDOClauseyDefault()
    {
        $cton = new RawModelCriterion(new Criteria(), 'A.COL', 1, 'A.COL = ?', PDO::PARAM_INT);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = array(
            array('table' => null, 'value' => 1, 'type' => PDO::PARAM_INT)
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToUsesParamStrByDefault()
    {
        $cton = new RawModelCriterion(new Criteria(), 'A.COL', 1, 'A.COL = ?');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = array(
            array('table' => null, 'value' => 1, 'type' => PDO::PARAM_STR)
        );
        $this->assertEquals($expected, $params);
    }

}
