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
use Propel\Runtime\ActiveQuery\Criterion\RawModelCriterion;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for RawModelCriterion.
 *
 * @author François Zaninotto
 */
class RawModelCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToThrowsExceptionWhenClauseHasNoQuestionMark()
    {
        $this->expectException(InvalidClauseException::class);

        $cton = new RawModelCriterion(new Criteria(), 'A.COL = BAR', 'A.COL', 1, null, PDO::PARAM_INT);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesAPDOClauseByDefault()
    {
        $cton = new RawModelCriterion(new Criteria(), 'A.COL = ?', 'A.COL', 1, null, PDO::PARAM_INT);

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
        $cton = new RawModelCriterion(new Criteria(), 'A.COL = ?', 'A.COL', 1);

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
