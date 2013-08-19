<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\PhpNameGenerator;
use \Propel\Tests\TestCase;

/**
 * Tests for PhpNameGenerator
 *
 * @author <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 */
class PhpNameGeneratorTest extends TestCase
{
    public static function phpnameMethodDataProvider()
    {
        return array(
            array('foo', 'Foo'),
            array('Foo', 'Foo'),
            array('FOO', 'FOO'),
            array('123', '123'),
            array('foo_bar', 'FooBar'),
            array('bar_1', 'Bar1'),
            array('bar_0', 'Bar0'),
            array('my_CLASS_name', 'MyCLASSName'),
        );
    }

    /**
     * @dataProvider phpnameMethodDataProvider
     */
    public function testPhpnameMethod($input, $output)
    {
        $generator = new TestablePhpNameGenerator();
        $this->assertEquals($output, $generator->phpnameMethod($input));
    }

    public static function underscoreMethodDataProvider()
    {
        return array(
            array('foo', 'Foo'),
            array('Foo', 'Foo'),
            array('Foo', 'Foo'),
            array('123', '123'),
            array('foo_bar', 'FooBar'),
            array('bar_1', 'Bar1'),
            array('bar_0', 'Bar0'),
            array('my_CLASS_name', 'MyClassName'),
        );
    }

    /**
     * @dataProvider underscoreMethodDataProvider
     */
    public function testUnderscoreMethod($input, $output)
    {
        $generator = new TestablePhpNameGenerator();
        $this->assertEquals($output, $generator->underscoreMethod($input));
    }

}

class TestablePhpNameGenerator extends PhpNameGenerator
{
    public function phpnameMethod($schemaName)
    {
        return parent::phpnameMethod($schemaName);
    }

    public function underscoreMethod($schemaName)
    {
        return parent::underscoreMethod($schemaName);
    }
}
