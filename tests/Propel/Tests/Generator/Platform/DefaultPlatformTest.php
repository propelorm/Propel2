<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Runtime\Propel;
use \Propel\Tests\TestCase;

class DefaultPlatformTest extends TestCase
{
    protected $platform;

    /**
     * Get the Platform object for this class
     *
     * @return Platform
     */
    protected function getPlatform()
    {
        if (null === $this->platform) {
            $this->platform = new DefaultPlatform();
        }

        return $this->platform;
    }

    protected function tearDown()
    {
        $this->platform = null;
    }

    /**
     * @dataProvider provideValidBooleanValues
     *
     */
    public function testGetBooleanString($value)
    {
        $p = $this->getPlatform();

        $this->assertEquals('1', $p->getBooleanString($value));
    }

    public function provideValidBooleanValues()
    {
        return [
            [true],
            ['TRUE'],
            ['true'],
            ['1'],
            [1],
            ['y'],
            ['Y'],
            ['yes'],
            ['YES'],
        ];
    }

    /**
     * @dataProvider provideInvalidBooleanValues
     *
     */
    public function testGetNonBooleanString($value)
    {
        $p = $this->getPlatform();

        $this->assertEquals('0', $p->getBooleanString($value));
    }

    public function provideInvalidBooleanValues()
    {
        return [
            [false],
            ['FALSE'],
            ['false'],
            ['0'],
            [0],
            ['n'],
            ['N'],
            ['no'],
            ['NO'],
            ['foo'],
        ];
    }

    public function testQuote()
    {
        $p = $this->getPlatform();

        $unquoted = "Nice";
        $quoted = $p->quote($unquoted);

        $this->assertEquals("'$unquoted'", $quoted);

        $unquoted = "Naughty ' string";
        $quoted = $p->quote($unquoted);
        $expected = "'Naughty '' string'";
        $this->assertEquals($expected, $quoted);
    }

    protected function createColumn($type, $defaultValue)
    {
        $column = new Column();
        $column->setType($type);
        $column->setDefaultValue($defaultValue);

        return $column;
    }

    public function createEnumColumn($defaultValues, $defaultValue)
    {
        $column = new Column();
        $column->setType(PropelTypes::ENUM);
        $column->setValueSet($defaultValues);
        $column->setDefaultValue($defaultValue);

        return $column;
    }

    public function createSetColumn($defaultValues, $defaultValue)
    {
        $column = new Column();
        $column->setType(PropelTypes::SET);
        $column->setValueSet($defaultValues);
        $column->setDefaultValue($defaultValue);

        return $column;
    }

    public function getColumnDefaultValueDDLDataProvider()
    {
        return [
            [$this->createColumn(PropelTypes::INTEGER, 0), "DEFAULT 0"],
            [$this->createColumn(PropelTypes::INTEGER, '0'), "DEFAULT 0"],
            [$this->createColumn(PropelTypes::VARCHAR, 'foo'), "DEFAULT 'foo'"],
            [$this->createColumn(PropelTypes::VARCHAR, 0), "DEFAULT '0'"],
            [$this->createColumn(PropelTypes::BOOLEAN, true), "DEFAULT 1"],
            [$this->createColumn(PropelTypes::BOOLEAN, false), "DEFAULT 0"],
            [$this->createColumn(PropelTypes::BOOLEAN, 'true'), "DEFAULT 1"],
            [$this->createColumn(PropelTypes::BOOLEAN, 'false'), "DEFAULT 0"],
            [$this->createColumn(PropelTypes::BOOLEAN, 'TRUE'), "DEFAULT 1"],
            [$this->createColumn(PropelTypes::BOOLEAN, 'FALSE'), "DEFAULT 0"],
            [$this->createEnumColumn(['foo', 'bar', 'baz'], 'foo'), "DEFAULT 0"],
            [$this->createEnumColumn(['foo', 'bar', 'baz'], 'bar'), "DEFAULT 1"],
            [$this->createEnumColumn(['foo', 'bar', 'baz'], 'baz'), "DEFAULT 2"],
            [$this->createSetColumn(['foo', 'bar', 'baz'], 'foo'), "DEFAULT 1"],
            [$this->createSetColumn(['foo', 'bar', 'baz'], 'bar'), "DEFAULT 2"],
            [$this->createSetColumn(['foo', 'bar', 'baz'], 'baz'), "DEFAULT 4"],
        ];
    }

    /**
     * @dataProvider getColumnDefaultValueDDLDataProvider
     */
    public function testGetColumnDefaultValueDDL($column, $default)
    {
        $this->assertEquals($default, $this->getPlatform()->getColumnDefaultValueDDL($column));
    }

}
