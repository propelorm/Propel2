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
use Propel\Runtime\ActiveQuery\Criterion\InModelCriterion;

/**
 * Test class for InModelCriterion.
 *
 * @author François Zaninotto
 */
class InModelCriterionTest extends BaseTestCase
{
    public function testAppendPsToCreatesAnInConditionByDefault()
    {
        $cton = new InModelCriterion(new Criteria(), 'A.COL IN ?', 'A.COL', array('foo'));

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
        $cton = new InModelCriterion(new Criteria(), 'A.COL NOT IN ?', 'A.COL', array('foo'));

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('A.COL NOT IN (:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToWithArrayValueCreatesAnInCondition()
    {
        $cton = new InModelCriterion(new Criteria(), 'A.COL IN ?', 'A.COL', array('foo', 'bar'));

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
        $cton = new InModelCriterion(new Criteria(), 'A.COL IN ?', 'A.COL', 'foo');

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
        $cton = new InModelCriterion(new Criteria(), 'A.COL IN ?', 'A.COL', $notEmptyValue);

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
        $cton = new InModelCriterion(new Criteria(), 'A.COL IN ?', 'A.COL', $emptyValue);

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
        $cton = new InModelCriterion(new Criteria(), 'A.COL NOT IN ?', 'A.COL', $emptyValue);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('1=1', $ps);
        $expected = array();
        $this->assertEquals($expected, $params);
    }

   /**
     * @dataProvider providerForEmptyValues
     */
    public function testAppendPsToWithNotInAndEmptyValueIsCaseInsensitive($emptyValue)
    {
        $cton = new InModelCriterion(new Criteria(), 'A.COL not in ?', 'A.COL', $emptyValue);

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
        $cton = new InModelCriterion(new Criteria(), 'A.COL IN ?', 'A.COL', $collection);

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
