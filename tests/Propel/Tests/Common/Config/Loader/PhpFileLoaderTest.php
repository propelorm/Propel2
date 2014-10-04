<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use Propel\Common\Config\Loader\PhpFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\Common\Config\ConfigTestCase;

class PhpFileLoaderTest extends ConfigTestCase
{
    protected $loader;

    protected function setUp()
    {
        $this->loader = new PhpFileLoader(new FileLocator(sys_get_temp_dir()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.php'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.php.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testPhpFileCanBeLoaded()
    {
        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;
        $this->dumpTempFile('parameters.php', $content);
        $test = $this->loader->load('parameters.php');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.php" does not exist (in:
     */
    public function testPhpFileDoesNotExist()
    {
        $this->loader->load('inexistent.php');
    }

    /**
    * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
    * @expectedExceptionMessage The configuration file 'nonvalid.php' has invalid content.
    */
    public function testPhpFileHasInvalidContent()
    {
        $content = <<<EOF
not php content
only plain
text
EOF;
        $this->dumpTempFile('nonvalid.php', $content);
        $this->loader->load('nonvalid.php');
    }

    /**
     * @expectedException        Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage The configuration file 'empty.php' has invalid content.
     */
    public function testPhpFileIsEmpty()
    {
        $content = '';
        $this->dumpTempFile('empty.php', $content);

        $this->loader->load('empty.php');
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file notreadable.php.
     */
    public function testConfigFileNotReadableThrowsException()
    {
        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;

        $this->dumpTempFile('notreadable.php', $content);
        $this->getFilesystem()->chmod(sys_get_temp_dir() . '/notreadable.php', 0200);

        $actual = $this->loader->load('notreadable.php');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);

    }
}
