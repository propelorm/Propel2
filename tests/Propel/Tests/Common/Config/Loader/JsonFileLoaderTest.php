<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use Propel\Common\Config\Loader\JsonFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\Common\Config\ConfigTestCase;

class JsonFileLoaderTest extends ConfigTestCase
{
    protected $loader;

    protected function setUp()
    {
        $this->loader = new JsonFileLoader(new FileLocator(sys_get_temp_dir()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.json.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testJsonFileCanBeLoaded()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $this->dumpTempFile('parameters.json', $content);

        $actual = $this->loader->load('parameters.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.json" does not exist (in:
     */
    public function testJsonFileDoesNotExist()
    {
        $this->loader->load('inexistent.json');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\JsonParseException
     */
    public function testJsonFileHasInvalidContent()
    {
        $content = <<<EOF
not json content
only plain
text
EOF;
        $this->dumpTempFile('nonvalid.json', $content);

        $this->loader->load('nonvalid.json');
    }

    public function testJsonFileIsEmpty()
    {
        $content = '';
        $this->dumpTempFile('empty.json', $content);

        $actual = $this->loader->load('empty.json');

        $this->assertEquals(array(), $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file notreadable.json.
     */
    public function testJsonFileNotReadableThrowsException()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;

        $this->dumpTempFile('notreadable.json', $content);
        $this->getFilesystem()->chmod(sys_get_temp_dir() . '/notreadable.json', 0200);

        $actual = $this->loader->load('notreadable.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);

    }
}
