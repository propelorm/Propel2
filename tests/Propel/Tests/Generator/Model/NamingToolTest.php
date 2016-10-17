<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\NamingTool;
use Propel\Tests\TestCase;

class NamingToolTest extends TestCase
{
    public function providerForUnderscore()
    {
        return [
            ['easy', 'easy'],
            ['easyString', 'easy_string'],
            ['EasyUpperString', 'easy_upper_string'],
            ['UPPER', 'upper'],
            ['SimpleXML', 'simple_xml'],
            ['XMLSimple', 'xml_simple'],
            ['LeftCENTERRight', 'left_center_right'],
            ['TableWithAVeryLongName', 'table_with_a_very_long_name'],
            ['AString', 'a_string'],
            ['IndexId1', 'index_id1'],
            ['Various1Numbers234In987The4657Name', 'various1_numbers234_in987_the4657_name'],
            ['already_underscore_string', 'already_underscore_string']
        ];
    }

    public function providerForCamelCase()
    {
        return [
            ['easy', 'easy'],
            ['easyString', 'easy_string'],
            ['easyUpperString', 'easy_upper_string'],
            ['leftCenterRight', 'left_center_right'],
            ['tableWithAVeryLongName', 'table_with_a_very_long_name'],
            ['aString', 'a_string'],
            ['indexId1', 'index_id1'],
            ['various1Numbers234In987The4657Name', 'various1_numbers234_in987_the4657_name'],
            ['alreadyCamelCaseString', 'alreadyCamelCaseString'],
            ['upperCamelCase', 'UpperCamelCase']
        ];
    }

    /**
     * @dataProvider providerForUnderscore
     */
    public function testToUnderscore($string, $expected)
    {
        $this->assertEquals($expected, NamingTool::toUnderscore($string));
    }

    /**
     * @dataProvider providerForCamelCase
     */
    public function testCamelCase($expected, $string)
    {
        $this->assertEquals($expected, NamingTool::toCamelCase($string));
    }

    /**
     * @dataProvider providerForCamelCase
     */
    public function testUpperCamelCase($expected, $string)
    {
        $this->assertEquals(ucfirst($expected), NamingTool::toUpperCamelCase($string));
    }
}

