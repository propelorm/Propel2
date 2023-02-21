<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config\Loader;

use InvalidArgumentException;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\InvalidArgumentException as PropelInvalidArgumentException;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\PhpFileLoader;
use Propel\Tests\TestCase;
use Propel\Generator\Util\VfsTrait;

class PhpFileLoaderTest extends TestCase
{
    use VfsTrait;

    /** @var PhpFileLoader */
    protected $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->loader = new PhpFileLoader(new FileLocator($this->getRoot()->url()));
    }

    /**
     * @return void
     */
    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.php'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.php.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.inc.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    /**
     * @return void
     */
    public function testPhpFileCanBeLoaded()
    {
        $content = <<<EOF
<?php

    return ['foo' => 'bar', 'bar' => 'baz'];

EOF;
        $this->newFile('parameters.php', $content);
        $test = $this->loader->load('parameters.php');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    /**
     * @return void
     */
    public function testPhpFileDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The file "inexistent.php" does not exist (in:');

        $this->loader->load('inexistent.php');
    }

    /**
     * @return void
     */
    public function testPhpFileHasInvalidContent()
    {
        $this->expectException(PropelInvalidArgumentException::class);
        $this->expectExceptionMessage("The configuration file 'nonvalid.php' has invalid content.");

        $content = <<<EOF
not php content
only plain
text
EOF;
        $this->newFile('nonvalid.php', $content);
        $this->loader->load('nonvalid.php');
    }

    /**
     * @return void
     */
    public function testPhpFileIsEmpty()
    {
        $this->expectException(PropelInvalidArgumentException::class);
        $this->expectExceptionMessage("The configuration file 'empty.php' has invalid content.");

        $this->newFile('empty.php');

        $this->loader->load('empty.php');
    }

    /**
     * @requires OS ^(?!Win.*)
     *
     * @return void
     */
    public function testConfigFileNotReadableThrowsException()
    {
        $this->expectException(InputOutputException::class);
        $this->expectExceptionMessage("You don't have permissions to access configuration file notreadable.php.");

        $content = <<<EOF
<?php

    return ['foo' => 'bar', 'bar' => 'baz'];

EOF;

        $this->newFile('notreadable.php', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.php');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
