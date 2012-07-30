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
use Propel\Runtime\ActiveQuery\Criterion\SeveralModelCriterion;

/**
 * Test class for SeveralModelCriterion.
 *
 * @author François Zaninotto
 */
class SeveralModelCriterionTest extends BaseTestCase
{
    public function testAppendPsToAddsBindingInfoForNotNullValues()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL BETWEEN ? AND ?', 'A.COL', array('foo', 'bar'));

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
     * @expectedException Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException
     */
    public function testAppendPsToThrowsExceptionWhenOneOfTheValuesIsNull()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL BETWEEN ? AND ?', 'A.COL', array('foo', null));

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @expectedException Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException
     */
    public function testAppendPsToThrowsExceptionWhenTheValueIsNull()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL BETWEEN ? AND ?', 'A.COL', null);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

}
