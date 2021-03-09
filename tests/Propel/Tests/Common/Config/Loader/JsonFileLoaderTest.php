<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config\Loader;

use InvalidArgumentException;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\JsonParseException;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\JsonFileLoader;
use Propel\Tests\TestCase;
use Propel\Generator\Util\VfsTrait;

class JsonFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var JsonFileLoader */
    protected $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->loader = new JsonFileLoader(new FileLocator($this->getRoot()->url()));
    }

    /**
     * @return void
     */
    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.json.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    /**
     * @return void
     */
    public function testJsonFileCanBeLoaded()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $this->newFile('parameters.json', $content);

        $actual = $this->loader->load('parameters.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @return void
     */
    public function testJsonFileDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "inexistent.json" does not exist (in:');

        $this->loader->load('inexistent.json');
    }

    /**
     * @return void
     */
    public function testJsonFileHasInvalidContent()
    {
        $this->expectException(JsonParseException::class);

        $content = <<<EOF
not json content
only plain
text
EOF;
        $this->newFile('nonvalid.json', $content);

        $this->loader->load('nonvalid.json');
    }

    /**
     * @return void
     */
    public function testJsonFileIsEmpty()
    {
        $this->newFile('empty.json');

        $actual = $this->loader->load('empty.json');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     *
     * @return void
     */
    public function testJsonFileNotReadableThrowsException()
    {
        $this->expectException(InputOutputException::class);
        $this->expectExceptionMessage("You don't have permissions to access configuration file notreadable.json.");

        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $this->newFile('notreadable.json', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
