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
use Propel\Runtime\ActiveQuery\Criterion\BasicCriterion;
use Propel\Runtime\Adapter\Pdo\CubridAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Propel;

/**
 * Test class for BasicCriterion.
 *
 * @author François Zaninotto
 */
class BasicCriterionTest extends BaseTestCase
{
    /**
     * @private If current adapter is Cubrid
     */
    private $isCubrid;

    //Whithout this snippet, these tests fail, if we're using Cubrid
    public function setUp()
    {
       if (count(Propel::getServiceContainer()->getConnectionManagers()) <= 0) {
           //Propel is not initialized
           $this->isCubrid = false;
       } else {
           $this->isCubrid = Propel::getServiceContainer()->getAdapter() instanceof CubridAdapter;
       }
    }

    public function testAppendPsToCreatesAnEqualConditionByDefault()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', 'foo');

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        if ($this->isCubrid) {
            $this->assertEquals('`A`.`COL`=:p1', $ps);
        } else {
            $this->assertEquals('A.COL=:p1', $ps);
        }

        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToAcceptsAComparisonType()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', 'foo', Criteria::GREATER_THAN);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        if ($this->isCubrid) {
            $this->assertEquals('`A`.`COL`>:p1', $ps);
        } else {
            $this->assertEquals('A.COL>:p1', $ps);
        }
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public function testAppendPsToCreatesACaseInsensitiveComparisonIfSpecified()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', 'foo');
        $cton->setAdapter(new SqliteAdapter());
        $cton->setIgnoreCase(true);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        $this->assertEquals('UPPER(A.COL)=UPPER(:p1)', $ps);
        $expected = array(
            array('table' => 'A', 'column' => 'COL', 'value' => 'foo')
        );
        $this->assertEquals($expected, $params);
    }

    public static function supportedANSIFunctions()
    {
        return array(
            array(Criteria::CURRENT_DATE),
            array(Criteria::CURRENT_TIME),
            array(Criteria::CURRENT_TIMESTAMP)
        );
    }

    /**
     * @dataProvider supportedANSIFunctions
     */
    public function testAppendPsToAcceptsAnANSIDateFunctionForValue($ansiFunction)
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', $ansiFunction);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        if ($this->isCubrid) {
            $this->assertEquals('`A`.`COL`=' . $ansiFunction, $ps);
        } else {
            $this->assertEquals('A.COL=' . $ansiFunction, $ps);
        }
        $this->assertEquals(array(), $params);
    }

    public function testAppendPsCanHandleEqualToNull()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', null);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        if ($this->isCubrid) {
            $this->assertEquals('`A`.`COL` IS NULL ', $ps);
        } else {
            $this->assertEquals('A.COL IS NULL ', $ps);
        }
        $this->assertEquals(array(), $params);
    }

    public function testAppendPsCanHandleNotEqualToNull()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', null, Criteria::NOT_EQUAL);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

        if ($this->isCubrid) {
            $this->assertEquals('`A`.`COL` IS NOT NULL ', $ps);
        } else {
            $this->assertEquals('A.COL IS NOT NULL ', $ps);
        }
        $this->assertEquals(array(), $params);
    }

    /**
     * @expectedException Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException
     */
    public function testAppendPsThrowsExceptionWhenValueIsNullAndComparisonIsComplex()
    {
        $cton = new BasicCriterion(new Criteria(), 'A.COL', null, Criteria::GREATER_THAN);

        $params = array();
        $ps = '';
        $cton->appendPsTo($ps, $params);

    }

}
