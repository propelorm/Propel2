<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use Propel\Common\Config\Loader\IniFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\Common\Config\ConfigTestCase;

class IniFileLoaderTest extends ConfigTestCase
{
    protected $loader;

    protected function setUp()
    {
        $this->loader = new IniFileLoader(new FileLocator(sys_get_temp_dir()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.ini'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.ini.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testIniFileCanBeLoaded()
    {
        $content = <<<EOF
;test ini
foo = bar
bar = baz
EOF;
        $this->dumpTempFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.ini" does not exist (in:
     */
    public function testIniFileDoesNotExist()
    {
        $this->loader->load('inexistent.ini');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage The configuration file 'nonvalid.ini' has invalid content.
     */
    public function testIniFileHasInvalidContent()
    {
        $content = <<<EOF
{not ini content}
only plain
text
EOF;
        $this->dumpTempFile('nonvalid.ini', $content);

        @$this->loader->load('nonvalid.ini');
    }

    public function testIniFileIsEmpty()
    {
        $content = '';
        $this->dumpTempFile('empty.ini', $content);

        $actual = $this->loader->load('empty.ini');

        $this->assertEquals(array(), $actual);
    }

    public function testWithSections()
    {
        $content = <<<EOF
[Cartoons]
Dog          = Pluto
Donald[]     = Huey
Donald[]     = Dewey
Donald[]     = Louie
Mickey[love] = Minnie
EOF;
        $this->dumpTempFile('section.ini', $content);
        $actual = $this->loader->load('section.ini');

        $this->assertEquals('Pluto', $actual['Cartoons']['Dog']);
        $this->assertEquals('Huey', $actual['Cartoons']['Donald'][0]);
        $this->assertEquals('Dewey', $actual['Cartoons']['Donald'][1]);
        $this->assertEquals('Louie', $actual['Cartoons']['Donald'][2]);
        $this->assertEquals('Minnie', $actual['Cartoons']['Mickey']['love']);
    }

    public function testNestedSections()
    {
        $content = <<<EOF
foo.bar.baz   = foobar
foo.bar.babaz = foobabar
bla.foo       = blafoo
bla.bar       = blabar
EOF;
        $this->dumpTempFile('nested.ini', $content);
        $actual = $this->loader->load('nested.ini');

        $this->assertEquals('foobar', $actual['foo']['bar']['baz']);
        $this->assertEquals('foobabar', $actual['foo']['bar']['babaz']);
        $this->assertEquals('blafoo', $actual['bla']['foo']);
        $this->assertEquals('blabar', $actual['bla']['bar']);
    }

    public function testMixedNestedSections()
    {
        $content = <<<EOF
bla.foo.bar = foobar
bla.foobar[] = foobarArray
bla.foo.baz[] = foobaz1
bla.foo.baz[] = foobaz2

EOF;
        $this->dumpTempFile('mixnested.ini', $content);
        $actual = $this->loader->load('mixnested.ini');

        $this->assertEquals('foobar', $actual['bla']['foo']['bar']);
        $this->assertEquals('foobarArray', $actual['bla']['foobar'][0]);
        $this->assertEquals('foobaz1', $actual['bla']['foo']['baz'][0]);
        $this->assertEquals('foobaz2', $actual['bla']['foo']['baz'][1]);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\IniParseException
     * @expectedExceptionMessage Invalid key ".foo"
     */
    public function testInvalidSectionThrowsException()
    {
        $content = <<<EOF
.foo = bar
bar = baz
EOF;
        $this->dumpTempFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\IniParseException
     * @expectedExceptionMessage Invalid key "foo."
     */
    public function testInvalidParamThrowsException()
    {
        $content = <<<EOF
foo. = bar
bar = baz
EOF;
        $this->dumpTempFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\IniParseException
     * @expectedExceptionMessage Cannot create sub-key for "foo", as key already exists
     */
    public function testAlreadyExistentParamThrowsException()
    {
        $content = <<<EOF
foo = bar
foo.babar = baz
EOF;
        $this->dumpTempFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
    }

    public function testSectionZero()
    {
        $content = <<<EOF
foo = bar
0.babar = baz
EOF;
        $this->dumpTempFile('parameters.ini', $content);

        $this->assertEquals(array('0' => array('foo' => 'bar', 'babar' => 'baz')), $this->loader->load('parameters.ini'));
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file notreadable.ini.
     */
    public function testIniFileNotReadableThrowsException()
    {
        $content = <<<EOF
foo = bar
bar = baz
EOF;

        $this->dumpTempFile('notreadable.ini', $content);
        $this->getFilesystem()->chmod(sys_get_temp_dir() . '/notreadable.ini', 0200);

        $actual = $this->loader->load('notreadable.ini');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);

    }
}

