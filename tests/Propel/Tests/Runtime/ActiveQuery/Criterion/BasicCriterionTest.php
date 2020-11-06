<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\BasicCriterion;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for BasicCriterion.
 *
 * @author François Zaninotto
 */
class BasicCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToCreatesAnEqualConditionByDefault()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL=:p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToAcceptsAComparisonType()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', 'foo', Criteria::GREATER_THAN);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL>:p1', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesACaseInsensitiveComparisonIfSpecified()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', 'foo');
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('UPPER(A.COL)=UPPER(:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    public static function supportedANSIFunctions()
    {
        return [
            [Criteria::CURRENT_DATE],
            [Criteria::CURRENT_TIME],
            [Criteria::CURRENT_TIMESTAMP],
        ];
    }

    /**
     * @dataProvider supportedANSIFunctions
     *
     * @return void
     */
    public function testAppendPsToAcceptsAnANSIDateFunctionForValue($ansiFunction)
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', $ansiFunction);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL=' . $ansiFunction, $ps);
        $this->assertEquals([], $params);
    }

    /**
     * @return void
     */
    public function testAppendPsCanHandleEqualToNull()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', null);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IS NULL ', $ps);
        $this->assertEquals([], $params);
    }

    /**
     * @return void
     */
    public function testAppendPsCanHandleNotEqualToNull()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', null, Criteria::NOT_EQUAL);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IS NOT NULL ', $ps);
        $this->assertEquals([], $params);
    }

    /**
     * @return void
     */
    public function testAppendPsThrowsExceptionWhenValueIsNullAndComparisonIsComplex()
    {
        $this->expectException(InvalidValueException::class);

        $cton = new BasicCriterion(new Criteria(), 'A.COL', null, Criteria::GREATER_THAN);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);
    }
}
