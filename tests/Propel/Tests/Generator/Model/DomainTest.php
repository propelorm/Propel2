<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Domain;

/**
 * Unit test suite for the Domain model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DomainTest extends ModelTestCase
{
    public function testCreateNewDomain()
    {
        $domain = new Domain('FLOAT', 'DOUBLE', 10, 2);

        $this->assertSame('FLOAT', $domain->getType());
        $this->assertSame('DOUBLE', $domain->getSqlType());
        $this->assertSame(10, $domain->getSize());
        $this->assertSame(2, $domain->getScale());
    }

    /**
     * @dataProvider provideDomainData
     *
     */
    public function testSetupObject($default, $expression)
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->will($this->returnValue(new Domain('BOOLEAN')))
        ;

        $domain = new Domain();
        $domain->setDatabase($this->getDatabaseMock('bookstore', array(
            'platform' => $platform
        )));
        $domain->loadMapping(array(
            'type' => 'BOOLEAN',
            'name' => 'foo',
            'default' => $default,
            'defaultExpr' => $expression,
            'size' => 10,
            'scale' => 2,
            'description' => 'Some description',
        ));

        $this->assertSame('BOOLEAN', $domain->getType());
        $this->assertSame('foo', $domain->getName());
        $this->assertInstanceOf('Propel\Generator\Model\ColumnDefaultValue', $domain->getDefaultValue());
        $this->assertSame(10, $domain->getSize());
        $this->assertSame(2, $domain->getScale());
        $this->assertSame('Some description', $domain->getDescription());
    }

    public function provideDomainData()
    {
        return array(
            array(1, null),
            array(null, 'NOW()'),
        );
    }

    public function testSetDatabase()
    {
        $domain = new Domain();
        $domain->setDatabase($this->getDatabaseMock('bookstore'));

        $this->assertInstanceOf('Propel\Generator\Model\Database', $domain->getDatabase());
    }

    public function testReplaceMappingAndSqlTypes()
    {
        $value = $this->getColumnDefaultValueMock();

        $domain = new Domain('FLOAT', 'DOUBLE');
        $domain->replaceType('BOOLEAN');
        $domain->replaceSqlType('INT');
        $domain->replaceDefaultValue($value);

        $this->assertSame('BOOLEAN', $domain->getType());
        $this->assertSame('INT', $domain->getSqlType());
        $this->assertInstanceOf('Propel\Generator\Model\ColumnDefaultValue', $value);
    }

    public function testGetNoPhpDefaultValue()
    {
        $domain = new Domain();

        $this->assertNull($domain->getPhpDefaultValue());
    }

    public function testGetPhpDefaultValue()
    {
        $value = $this->getColumnDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('foo'))
        ;

        $domain = new Domain('VARCHAR');
        $domain->setDefaultValue($value);

        $this->assertSame('foo', $domain->getPhpDefaultValue());
    }

    /**
     * @dataProvider provideBooleanValues
     *
     */
    public function testGetBooleanValue($mappingType, $booleanAsString, $expected)
    {
        $value = $this->getColumnDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($booleanAsString))
        ;

        $domain = new Domain($mappingType);
        $domain->setDefaultValue($value);

        $this->assertSame($expected, $domain->getPhpDefaultValue());
    }

    public function provideBooleanValues()
    {
        return array(
            array('BOOLEAN', 1, true),
            array('BOOLEAN', 0, false),
            array('BOOLEAN', 't', true),
            array('BOOLEAN', 'f', false),
            array('BOOLEAN', 'y', true),
            array('BOOLEAN', 'n', false),
            array('BOOLEAN', 'yes', true),
            array('BOOLEAN', 'no', false),
            array('BOOLEAN', 'true', true),
            array('BOOLEAN_EMU', 'true', true),
            array('BOOLEAN', 'false', false),
            array('BOOLEAN_EMU', 'false', false),
        );
    }

    public function testCantGetPhpDefaultValue()
    {
        $value = $this->getColumnDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('isExpression')
            ->will($this->returnValue(true))
        ;

        $domain = new Domain();
        $domain->setDefaultValue($value);

        $this->setExpectedException('Propel\Generator\Exception\EngineException');
        $domain->getPhpDefaultValue();
    }

    /**
     * @dataProvider provideSizeDefinitions
     *
     */
    public function testGetSizeDefinition($size, $scale, $definition)
    {
        $domain = new Domain('FLOAT', 'DOUBLE', $size, $scale);

        $this->assertSame($definition, $domain->getSizeDefinition());
    }

    public function provideSizeDefinitions()
    {
        return array(
            array(10, null, '(10)'),
            array(10, 2, '(10,2)'),
            array(null, null, ''),
        );
    }

    public function testCopyDomain()
    {
        $value = $this->getColumnDefaultValueMock();

        $domain = new Domain();
        $domain->setType('FLOAT');
        $domain->setSqlType('DOUBLE');
        $domain->setSize(10);
        $domain->setScale(2);
        $domain->setName('Mapping between FLOAT and DOUBLE');
        $domain->setDescription('Some description');
        $domain->setDefaultValue($value);

        $newDomain = new Domain();
        $newDomain->copy($domain);

        $this->assertSame('FLOAT', $newDomain->getType());
        $this->assertSame('DOUBLE', $newDomain->getSqlType());
        $this->assertSame(10, $newDomain->getSize());
        $this->assertSame(2, $newDomain->getScale());
        $this->assertSame('Mapping between FLOAT and DOUBLE', $newDomain->getName());
        $this->assertSame('Some description', $newDomain->getDescription());
        $this->assertInstanceOf('Propel\Generator\Model\ColumnDefaultValue', $value);
    }

    private function getColumnDefaultValueMock()
    {
        $value = $this
            ->getMockBuilder('Propel\Generator\Model\ColumnDefaultValue')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $value;
    }
}
