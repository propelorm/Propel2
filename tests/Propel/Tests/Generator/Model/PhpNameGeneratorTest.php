<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\PhpNameGenerator;
use Propel\Tests\TestCase;

/**
 * Tests for PhpNameGenerator
 *
 * @author <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 */
class PhpNameGeneratorTest extends TestCase
{
    public static function phpnameMethodDataProvider()
    {
        return [
            ['foo', 'Foo'],
            ['Foo', 'Foo'],
            ['FOO', 'FOO'],
            ['123', '123'],
            ['foo_bar', 'FooBar'],
            ['bar_1', 'Bar1'],
            ['bar_0', 'Bar0'],
            ['my_CLASS_name', 'MyCLASSName'],
        ];
    }

    /**
     * @dataProvider phpnameMethodDataProvider
     *
     * @return void
     */
    public function testPhpnameMethod($input, $output)
    {
        $generator = new TestablePhpNameGenerator();
        $this->assertEquals($output, $generator->phpnameMethod($input));
    }

    public static function underscoreMethodDataProvider()
    {
        return [
            ['foo', 'Foo'],
            ['Foo', 'Foo'],
            ['Foo', 'Foo'],
            ['123', '123'],
            ['foo_bar', 'FooBar'],
            ['bar_1', 'Bar1'],
            ['bar_0', 'Bar0'],
            ['my_CLASS_name', 'MyClassName'],
        ];
    }

    /**
     * @dataProvider underscoreMethodDataProvider
     *
     * @return void
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
