<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\Collection\ArrayCollection;
use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\InCriterion;

/**
 * Test class for InCriterion.
 *
 * @author François Zaninotto
 */
class InCriterionTest extends BaseTestCase
{

    public function testAppendPsToCreatesAnInConditionByDefault()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', array('foo'));

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesANotInConditionWhenSpecified()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', array('foo'), Criteria::NOT_IN);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT IN (:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesAnInConditionUsingAColumnAlias()
    {
        $cton = new InCriterion(new Criteria(), 'my_alias', array('foo'));

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('my_alias IN (:p1)', $ps);
        $expected = array(
            array('table' => null, 'column' => 'my_alias', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesAnInConditionUsingATableAlias()
    {
        $c = new Criteria();
        $c->addAlias('bar_alias', 'bar');
        $cton = new InCriterion($c, 'bar_alias.COL', array('foo'));

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('bar_alias.COL IN (:p1)', $ps);
        $expected = array(
            array('table' => 'bar', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithArrayValueCreatesAnInCondition()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', array('foo', 'bar'));

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1,:p2)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo'),
            array('table' => 'A', 'column' => 'COL', 'value' => 'bar')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithScalarValueCreatesAnInCondition()
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', 'foo');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public static function providerForNotEmptyValues()
    {
        return array(
            array(''),
            array(0),
            array(true),
            array(false)
        );
    }

    /**
     * @dataProvider providerForNotEmptyValues
     */
    public function testAppendPsToWithNotEmptyValueCreatesAnInCondition($notEmptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $notEmptyValue);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => $notEmptyValue)
        );
        $this->assertEquals($expected, $params);
    }

    public static function providerForEmptyValues()
    {
        return array(
            array(array()),
            array(null)
        );
    }

    /**
     * @dataProvider providerForEmptyValues
     */
    public function testAppendPsToWithInAndEmptyValueCreatesAnAlwaysFalseCondition($emptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $emptyValue);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1<>1', $ps);
        $expected = array();
        $this->assertEquals($expected, $params);
    }

   /**
     * @dataProvider providerForEmptyValues
     */
    public function testAppendPsToWithNotInAndEmptyValueCreatesAnAlwaysTrueCondition($emptyValue)
    {
        $cton = new InCriterion(new Criteria(), 'A.COL', $emptyValue, Criteria::NOT_IN);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1=1', $ps);
        $expected = array();
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithArrayCollection()
    {
        $collection = new ArrayCollection(array('foo'));
        $cton = new InCriterion(new Criteria(), 'A.COL', $collection);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL IN (:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

}
