<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException;
use Propel\Runtime\ActiveQuery\Criterion\SeveralModelCriterion;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for SeveralModelCriterion.
 *
 * @author François Zaninotto
 */
class SeveralModelCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToAddsBindingInfoForNotNullValues()
    {
        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL BETWEEN ? AND ?', 'A.COL', ['foo', 'bar']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL BETWEEN :p1 AND :p2', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
            ['table' => 'A', 'column' => 'COL', 'value' => 'bar'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToThrowsExceptionWhenOneOfTheValuesIsNull()
    {
        $this->expectException(InvalidValueException::class);

        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL BETWEEN ? AND ?', 'A.COL', ['foo', null]);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToThrowsExceptionWhenTheValueIsNull()
    {
        $this->expectException(InvalidValueException::class);

        $cton = new SeveralModelCriterion(new Criteria(), 'A.COL BETWEEN ? AND ?', 'A.COL', null);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }
}
