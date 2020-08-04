<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Domain;

/**
 * Unit test suite for the Domain model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DomainTest extends ModelTestCase
{
    /**
     * @return void
     */
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
     * @return void
     */
    public function testSetupObject($default, $expression)
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->will($this->returnValue(new Domain('BOOLEAN')));

        $domain = new Domain();
        $domain->setDatabase($this->getDatabaseMock('bookstore', [
            'platform' => $platform,
        ]));
        $domain->loadMapping([
            'type' => 'BOOLEAN',
            'name' => 'foo',
            'default' => $default,
            'defaultExpr' => $expression,
            'size' => 10,
            'scale' => 2,
            'description' => 'Some description',
        ]);

        $this->assertSame('BOOLEAN', $domain->getType());
        $this->assertSame('foo', $domain->getName());
        $this->assertInstanceOf('Propel\Generator\Model\ColumnDefaultValue', $domain->getDefaultValue());
        $this->assertSame(10, $domain->getSize());
        $this->assertSame(2, $domain->getScale());
        $this->assertSame('Some description', $domain->getDescription());
    }

    public function provideDomainData()
    {
        return [
            [1, null],
            [null, 'NOW()'],
        ];
    }

    /**
     * @return void
     */
    public function testSetDatabase()
    {
        $domain = new Domain();
        $domain->setDatabase($this->getDatabaseMock('bookstore'));

        $this->assertInstanceOf('Propel\Generator\Model\Database', $domain->getDatabase());
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testGetNoPhpDefaultValue()
    {
        $domain = new Domain();

        $this->assertNull($domain->getPhpDefaultValue());
    }

    /**
     * @return void
     */
    public function testGetPhpDefaultValue()
    {
        $value = $this->getColumnDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('foo'));

        $domain = new Domain('VARCHAR');
        $domain->setDefaultValue($value);

        $this->assertSame('foo', $domain->getPhpDefaultValue());
    }

    /**
     * @dataProvider provideBooleanValues
     *
     * @return void
     */
    public function testGetBooleanValue($mappingType, $booleanAsString, $expected)
    {
        $value = $this->getColumnDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($booleanAsString));

        $domain = new Domain($mappingType);
        $domain->setDefaultValue($value);

        $this->assertSame($expected, $domain->getPhpDefaultValue());
    }

    public function provideBooleanValues()
    {
        return [
            ['BOOLEAN', 1, true],
            ['BOOLEAN', 0, false],
            ['BOOLEAN', 't', true],
            ['BOOLEAN', 'f', false],
            ['BOOLEAN', 'y', true],
            ['BOOLEAN', 'n', false],
            ['BOOLEAN', 'yes', true],
            ['BOOLEAN', 'no', false],
            ['BOOLEAN', 'true', true],
            ['BOOLEAN_EMU', 'true', true],
            ['BOOLEAN', 'false', false],
            ['BOOLEAN_EMU', 'false', false],
        ];
    }

    /**
     * @return void
     */
    public function testCantGetPhpDefaultValue()
    {
        $value = $this->getColumnDefaultValueMock();
        $value
            ->expects($this->once())
            ->method('isExpression')
            ->will($this->returnValue(true));

        $domain = new Domain();
        $domain->setDefaultValue($value);

        $this->expectException(EngineException::class);
        $domain->getPhpDefaultValue();
    }

    /**
     * @dataProvider provideSizeDefinitions
     *
     * @return void
     */
    public function testGetSizeDefinition($size, $scale, $definition)
    {
        $domain = new Domain('FLOAT', 'DOUBLE', $size, $scale);

        $this->assertSame($definition, $domain->getSizeDefinition());
    }

    public function provideSizeDefinitions()
    {
        return [
            [10, null, '(10)'],
            [10, 2, '(10,2)'],
            [null, null, ''],
        ];
    }

    /**
     * @return void
     */
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
            ->getMock();

        return $value;
    }
}
