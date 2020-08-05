<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\InCriterion;
use Propel\Runtime\Collection\ArrayCollection;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * Test class for InCriterion.
 *
 * @author François Zaninotto
 */
class InCriterionTest extends BaseTestCase
{
    /**
     * @return void
     */
    public function testAppendPsToCreatesAnInConditionByDefault()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', ['foo']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesANotInConditionWhenSpecified()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', ['foo'], Criteria::NOT_IN);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT IN (:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesAnInConditionUsingAColumnAlias()
    {
        $cton = new InCriterion(new Criteria(), 'my_alias', ['foo']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('my_alias IN (:p1)', $ps);
        $expected = [
            ['table' => null, 'column' => 'my_alias', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToCreatesAnInConditionUsingATableAlias()
    {
        $c = new Criteria();
        $c->addAlias('bar_alias', 'bar');
        $cton = new InCriterion($c, 'bar_alias.COL', ['foo']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('bar_alias.COL IN (:p1)', $ps);
        $expected = [
            ['table' => 'bar', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToWithArrayValueCreatesAnInCondition()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', ['foo', 'bar']);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1,:p2)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
            ['table' => 'A', 'column' => 'COL', 'value' => 'bar'],
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToWithScalarValueCreatesAnInCondition()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', 'foo');

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }

    public static function providerForNotEmptyValues()
    {
        return [
            [''],
            [0],
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider providerForNotEmptyValues
     *
     * @return void
     */
    public function testAppendPsToWithNotEmptyValueCreatesAnInCondition($notEmptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $notEmptyValue);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => $notEmptyValue],
        ];
        $this->assertEquals($expected, $params);
    }

    public static function providerForEmptyValues()
    {
        return [
            [[]],
            [null],
        ];
    }

    /**
     * @dataProvider providerForEmptyValues
     *
     * @return void
     */
    public function testAppendPsToWithInAndEmptyValueCreatesAnAlwaysFalseCondition($emptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $emptyValue);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1<>1', $ps);
        $expected = [];
        $this->assertEquals($expected, $params);
    }

   /**
    * @dataProvider providerForEmptyValues
    *
    * @return void
    */
    public function testAppendPsToWithNotInAndEmptyValueCreatesAnAlwaysTrueCondition($emptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $emptyValue, Criteria::NOT_IN);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1=1', $ps);
        $expected = [];
        $this->assertEquals($expected, $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToWithArrayCollection()
    {
        $collection = new ArrayCollection(['foo']);
        $cton = new InCriterion(new Criteria(), 'A.COL', $collection);

        $params = [];
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = [
            ['table' => 'A', 'column' => 'COL', 'value' => 'foo'],
        ];
        $this->assertEquals($expected, $params);
    }
}
