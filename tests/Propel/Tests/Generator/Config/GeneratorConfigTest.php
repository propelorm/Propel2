<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Tests\TestCase;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @package	propel.generator.config
 */
class GeneratorConfigTest extends TestCase
{
    protected $pathToFixtureFiles;

    public function setUp()
    {
        $this->pathToFixtureFiles = __DIR__ . '/../../../../Fixtures/generator/config';
    }

    public function testGetClassNameWithClass()
    {
        $file = $this->pathToFixtureFiles . '/Foobar.php';

        if (!file_exists($file)) {
            $this->markTestSkipped();
        }

        // Load the file to simulate the autoloading process
        require $file;

        $generator = new GeneratorConfig();
        $generator->setBuildProperty('propel.foo.bar', 'Foobar');

        $this->assertSame('Foobar', $generator->getClassName('propel.foo.bar'));
    }

    public function testGetClassNameWithClassAndNamespace()
    {
        $file = $this->pathToFixtureFiles . '/FoobarWithNS.php';

        if (!file_exists($file)) {
            $this->markTestSkipped();
        }

        // Load the file to simulate the autoloading process
        require $file;

        $generator = new GeneratorConfig();
        $generator->setBuildProperty('propel.foo.bar', '\Foo\Test\FoobarWithNS');

        $this->assertSame('\Foo\Test\FoobarWithNS', $generator->getClassName('propel.foo.bar'));
    }

    /**
      * @expectedException \Propel\Generator\Exception\BuildException
      */
    public function testGetClassNameOnInexistantProperty()
    {
        $generator = new GeneratorConfig();
        $generator->getClassName('propel.foo.bar');
    }
}
