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
use Propel\Runtime\Query\Criterion\SeveralModelCriterion;

/**
 * Test class for SeveralModelCriterion.
 *
 * @author François Zaninotto
 */
class SeveralModelCriterionTest extends BaseTestCase
{
    public function testAppendPsToAddsBindingInfoForNotNullValues()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL', array('foo', 'bar'), 'A.COL BETWEEN ? AND ?');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL BETWEEN :p1 AND :p2', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo'),
            array('table' => 'A', 'column' => 'COL', 'value' => 'bar')
        );
        $this->assertEquals($expected, $params);
    }

    /**
     * @expectedException Propel\Runtime\Query\Criterion\Exception\InvalidValueException
     */
    public function testAppendPsToThrowsExceptionWhenOneOfTheValuesIsNull()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL', array('foo', null), 'A.COL BETWEEN ? AND ?');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @expectedException Propel\Runtime\Query\Criterion\Exception\InvalidValueException
     */
    public function testAppendPsToThrowsExceptionWhenTheValueIsNull()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL', null, 'A.COL BETWEEN ? AND ?');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

}
