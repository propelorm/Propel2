<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\SqlDefaultPlatform;
use Propel\Runtime\Propel;
use \Propel\Tests\TestCase;

class DefaultPlatformTest extends TestCase
{
    protected $platform;

    /**
     * Get the Platform object for this class
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    protected function getPlatform()
    {
        if (null === $this->platform) {
            $this->platform = new SqlDefaultPlatform();
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
        return array(
            array(true),
            array('TRUE'),
            array('true'),
            array('1'),
            array(1),
            array('y'),
            array('Y'),
            array('yes'),
            array('YES'),
        );
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
        return array(
            array(false),
            array('FALSE'),
            array('false'),
            array('0'),
            array(0),
            array('n'),
            array('N'),
            array('no'),
            array('NO'),
            array('foo'),
        );
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

    protected function createField($type, $defaultValue)
    {
        $column = new Field();
        $column->setType($type);
        $column->setDefaultValue($defaultValue);

        return $column;
    }

    public function createEnumField($defaultValues, $defaultValue)
    {
        $column = new Field();
        $column->setType(PropelTypes::ENUM);
        $column->setValueSet($defaultValues);
        $column->setDefaultValue($defaultValue);

        return $column;
    }

    public function getFieldDefaultValueDDLDataProvider()
    {
        return array(
            array($this->createField(PropelTypes::INTEGER, 0), "DEFAULT 0"),
            array($this->createField(PropelTypes::INTEGER, '0'), "DEFAULT 0"),
            array($this->createField(PropelTypes::VARCHAR, 'foo'), "DEFAULT 'foo'"),
            array($this->createField(PropelTypes::VARCHAR, 0), "DEFAULT '0'"),
            array($this->createField(PropelTypes::BOOLEAN, true), "DEFAULT 1"),
            array($this->createField(PropelTypes::BOOLEAN, false), "DEFAULT 0"),
            array($this->createField(PropelTypes::BOOLEAN, 'true'), "DEFAULT 1"),
            array($this->createField(PropelTypes::BOOLEAN, 'false'), "DEFAULT 0"),
            array($this->createField(PropelTypes::BOOLEAN, 'TRUE'), "DEFAULT 1"),
            array($this->createEnumField(array('foo', 'bar', 'baz'), 'foo'), "DEFAULT 'foo'"),
            array($this->createEnumField(array('foo', 'bar', 'baz'), 'bar'), "DEFAULT 'bar'"),
            array($this->createEnumField(array('foo', 'bar', 'baz'), 'baz'), "DEFAULT 'baz'"),
        );
    }

    /**
     * @dataProvider getFieldDefaultValueDDLDataProvider
     */
    public function testGetFieldDefaultValueDDL($column, $default)
    {
        $this->assertEquals($default, $this->getPlatform()->getFieldDefaultValueDDL($column));
    }

}
