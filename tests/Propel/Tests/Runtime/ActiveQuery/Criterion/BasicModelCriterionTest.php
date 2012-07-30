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
use Propel\Runtime\ActiveQuery\Criterion\BasicModelCriterion;

/**
 * Test class for BasicModelCriterion.
 *
 * @author François Zaninotto
 */
class BasicModelCriterionTest extends BaseTestCase
{
    public function testAppendPsToAddsBindingInfoForNotNullValues()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL = ?', 'A.COL', 'foo');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    /**
     * @expectedException Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException
     */
    public function testAppendPsToThrowsExceptionWhenBindingAValueToAClauseWithNoQuestionMark()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL = B.COL', 'A.COL', 'foo');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    public function testAppendPsToAddsClauseWithoutBindingForNullValues()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL IS NULL', 'A.COL', null);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IS NULL', $ps);
        $this->assertEquals(array(), $params);
    }

}
