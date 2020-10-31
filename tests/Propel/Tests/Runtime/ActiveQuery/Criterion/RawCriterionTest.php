<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use PDO;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException;
use Propel\Runtime\ActiveQuery\Criterion\RawCriterion;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for RawCriterion.
 *
 * @author François Zaninotto
 */
class RawCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToThrowsExceptionWhenClauseHasNoQuestionMark()
    {
        $this->expectException(InvalidClauseException::class);

        $cton = new RawCriterion(new Criteria(), 'A.COL = BAR', 1, PDO::PARAM_INT);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesAPDOClauseyDefault()
    {
        $cton = new RawCriterion(new Criteria(), 'A.COL = ?', 1, PDO::PARAM_INT);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = [
            ['table' => null, 'value' => 1, 'type' => PDO::PARAM_INT],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToUsesParamStrByDefault()
    {
        $cton = new RawCriterion(new Criteria(), 'A.COL = ?', 1);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL = :p1', $ps);
        $expected = [
            ['table' => null, 'value' => 1, 'type' => PDO::PARAM_STR],
        ];
        $this->assertEquals($expected, $params);
    }
}
