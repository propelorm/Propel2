<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Exception\Propel\Generator\Exception\InvalidArgumentExceptio;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\MappingModel;
use Propel\Tests\TestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti
 */
class MappingModelTest extends TestCase
{
    public function testHasAttributes()
    {
        $mappingModel = new TestableMappingModel();
        $this->assertObjectHasAttribute('name', $mappingModel);
        $this->assertObjectHasAttribute('sqlName', $mappingModel);
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     */
    public function testSetNameNullThrowsException()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName(null);
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     */
    public function testSetNameNullStringThrowsException()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName('');
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     */
    public function testSetNameScalarNotStringThrowsException()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName(25);
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     */
    public function testSetNameObjectThrowsException()
    {
        $obj = new TestableMappingModel();
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName($obj);
    }

    /**
     * @dataProvider providerForTestSetName
     */
    public function testSetName($value, $expected)
    {
        $mappingModel = new TestableMappingModel();

        $mappingModel->setName($value);
        $this->assertEquals($expected, $mappingModel->getName());
    }

    public function testSetSqlNameNull()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName('FooBar');
        $mappingModel->setSqlName(null);
        $this->assertEquals('foo_bar', $mappingModel->getSqlName());
    }

    public function testSetSqlNameNullString()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName('FooBar');
        $mappingModel->setSqlName('');
        $this->assertEquals('foo_bar', $mappingModel->getSqlName());
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     */
    public function testSetSqlNameScalarNotStringThrowsException()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setSqlName(25);
    }

    /**
     * @expectedException Propel\Generator\Exception\InvalidArgumentException
     */
    public function testSetSqlNameObjectThrowsException()
    {
        $obj = new TestableMappingModel();
        $mappingModel = new TestableMappingModel();
        $mappingModel->setSqlName($obj);
    }

    public function testSetSqlNameDoesNotChangeTheFormat()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setSqlName('FooBar');
        $this->assertEquals('FooBar', $mappingModel->getSqlName());
    }

    /**
     * @expectedException Propel\Generator\Exception\BuildException
     */
    public function testGetSqlNameDefaultThrowsExceptionIfNameIsNOtSet()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->getSqlName();
    }

    public function testGetSqlNameDefault()
    {
        $mappingModel = new TestableMappingModel();
        $mappingModel->setName('AuthorId');
        $this->assertEquals('author_id', $mappingModel->getSqlName());
    }

    /**
     * @dataProvider providerForGetDefaultValueForArray
     */
    public function testGetDefaultValueForArray($value, $expected)
    {
        $mappingModel = new TestableMappingModel();
        $this->assertEquals($expected, $mappingModel->getDefaultValueForArray($value));
    }

    public function providerForGetDefaultValueForArray()
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

    public function providerForTestSetName()
    {
        return [
            ['ar123', 'ar123'],
            ['id', 'id'],
            ['Author', 'author'],
            ['the_dark_side_of_the_moon', 'theDarkSideOfTheMoon'],
            ['AuthorId', 'authorId'],
        ];
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
