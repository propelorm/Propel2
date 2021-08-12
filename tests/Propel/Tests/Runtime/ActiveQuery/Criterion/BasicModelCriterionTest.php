<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\BasicModelCriterion;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for BasicModelCriterion.
 *
 * @author François Zaninotto
 */
class BasicModelCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToAddsBindingInfoForNotNullValues()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL = ?', 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToThrowsExceptionWhenBindingAValueToAClauseWithNoQuestionMark()
    {
        $this->expectException(InvalidClauseException::class);

        $cton = new BasicModelCriterion(new Criteria(), 'A.COL = B.COL', 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToAddsClauseWithoutBindingForNullValues()
    {
        $cton = new BasicModelCriterion(new Criteria(), 'A.COL IS NULL', 'A.COL', null);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IS NULL', $ps);
        $this->assertEquals([], $params);
    }
}
