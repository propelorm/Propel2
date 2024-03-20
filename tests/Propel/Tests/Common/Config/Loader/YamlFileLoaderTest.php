<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config\Loader;

use InvalidArgumentException;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\YamlFileLoader;
use Propel\Tests\TestCase;
use Propel\Generator\Util\VfsTrait;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var YamlFileLoader */
    protected $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->loader = new YamlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    /**
     * @return void
     */
    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yaml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    /**
     * @return void
     */
    public function testYamlFileCanBeLoaded()
    {
        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        $this->newFile('parameters.yaml', $content);

        $test = $this->loader->load('parameters.yaml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @return void
     */
    public function testYamlFileDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "inexistent.yaml" does not exist (in:');

        $this->loader->load('inexistent.yaml');
    }

    /**
     * @return void
     */
    public function testYamlFileHasInvalidContent()
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unable to parse');

        $content = <<<EOF
not yaml content
only plain
text
EOF;
        $this->newFile('nonvalid.yaml', $content);
        $this->loader->load('nonvalid.yaml');
    }

    /**
     * @return void
     */
    public function testYamlFileIsEmpty()
    {
        $this->expectException(InputOutputException::class);
        $this->expectExceptionMessage("Unable to read configuration file `empty.yaml`.");

        $this->newFile('empty.yaml', '');

        $actual = $this->loader->load('empty.yaml');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     *
     * @return void
     */
    public function testYamlFileNotReadableThrowsException()
    {
        $this->expectException(InputOutputException::class);
        $this->expectExceptionMessage("You don't have permissions to access configuration file `notreadable.yaml`.");

        $content = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('notreadable.yaml', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.yaml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
