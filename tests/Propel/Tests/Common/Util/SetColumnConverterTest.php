<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Util;

use Propel\Common\Util\SetColumnConverter;

/**
 * Tests for SetColumnConverter class.
 * 
 * @author Moritz Schroeder <moritz.schroeder@molabs.de> 
 */
class SetColumnConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array  $values
     * @param string $validInteger
     * 
     * @dataProvider convertValuesProvider
     */
    public function testConvertToIntValidValues(array $values, $validInteger)
    {
        $valueSet = ['a','b','c','d','e','f'];
        $intValue = SetColumnConverter::convertToInt($values, $valueSet);
        $this->assertEquals($validInteger, $intValue);
    }

    public function testConvertToIntStringValue()
    {
        $valueSet = ['a','b','c','d','e','f'];
        $intValue = SetColumnConverter::convertToInt('c', $valueSet);
        $this->assertEquals('4', $intValue);
    }

    public function testConvertToIntNullValue()
    {
        $valueSet = ['a','b','c','d','e','f'];
        $intValue = SetColumnConverter::convertToInt(null, $valueSet);
        $this->assertEquals('0', $intValue);
    }

    /**
     * @expectedException \Propel\Common\Exception\SetColumnConverterException
     */
    public function testConvertToIntValueNotInSet()
    {
        $valueSet = ['a','b','c','d','e','f'];
        SetColumnConverter::convertToInt(['g'], $valueSet);
    }

    /**
     * @param array  $validArray
     * @param string $intValue
     *
     * @dataProvider convertValuesProvider
     */
    public function testConvertIntToArrayValidValues(array $validArray, $intValue)
    {
        $valueSet = ['a','b','c','d','e','f'];
        $arrayValue = SetColumnConverter::convertIntToArray($intValue, $valueSet);
        $this->assertEquals($validArray, $arrayValue);
    }

    public function testConvertIntToArrayNullValue()
    {
        $valueSet = ['a','b','c','d','e','f'];
        $arrayValue = SetColumnConverter::convertIntToArray(null, $valueSet);
        $this->assertEquals([], $arrayValue);
    }

    /**
     * @expectedException \Propel\Common\Exception\SetColumnConverterException
     */
    public function testConvertIntToArrayIntOutOfRange()
    {
        $valueSet = ['a','b','c','d','e','f'];
        SetColumnConverter::convertIntToArray('65', $valueSet);
    }
    
    public function convertValuesProvider()
    {
        return [
            [['a'],             '1'],
            [['a', 'f'],        '33'],
            [['a', 'e', 'f'],   '49'],
            [['e', 'f'],        '48'],
        ];
    }
}
