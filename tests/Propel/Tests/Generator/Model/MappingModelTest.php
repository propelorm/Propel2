<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\MappingModel;
use Propel\Tests\TestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MappingModelTest extends TestCase
{
    /**
     * @dataProvider providerForGetDefaultValueForArray
     */
    public function testGetDefaultValueForArray($value, $expected)
    {
        $mappingModel = new TestableMappingModel();
        $this->assertEquals($expected, $mappingModel->getDefaultValueForArray($value));
    }

    public static function providerForGetDefaultValueForArray()
    {
        return array(
            array('', null),
            array(null, null),
            array('FOO', '||FOO||'),
            array('FOO, BAR', '||FOO | BAR||'),
            array('FOO , BAR', '||FOO | BAR||'),
            array('FOO,BAR', '||FOO | BAR||'),
            array(' ', null),
            array(', ', null),
        );
    }
}

class TestableMappingModel extends MappingModel
{
    public function getDefaultValueForArray($value)
    {
        return parent::getDefaultValueForArray($value);
    }

    public function appendXml(DOMNode $node)
    {
    }

    protected function setupObject()
    {
    }
}
