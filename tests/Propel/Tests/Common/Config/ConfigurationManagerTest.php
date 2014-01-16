<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

use Propel\Common\Config\ConfigurationManager;

class ConfigurationManagerTest extends ConfigTestCase
{
    /**
     * Current working directory
     */
    private $currentDir;

    /**
     * Directory in which to create temporary fixtures
     */
    private $fixturesDir;

    public function setUp()
    {
        $this->currentDir = getcwd();
        $this->fixturesDir = realpath( __DIR__ . '/../../../../Fixtures') . '/Configuration';

        $this->getFilesystem()->mkdir($this->fixturesDir);
        chdir($this->fixturesDir);
    }

    public function tearDown()
    {
        $this->getFileSystem()->remove($this->fixturesDir);

        chdir($this->currentDir);
    }

    public function testLoadConfigFileInCurrentDirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testLoadConfigFileInConfigSubdirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('config/propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testLoadConfigFileInConfSubdirectory()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('conf/propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @exceptionMessage Propel configuration file not found
     */
    public function testWrongConfigFileThrowsException()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('doctrine.yaml', $yamlConf);

        $manager = new ConfigurationManager();
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @exceptionMessage Propel expects only one configuration file
     */
    public function testMoreThanOneConfigurationFileInSameDirectoryThrowsException()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $iniConf = <<<EOF
foo = bar
bar = baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('propel.ini', $iniConf);

        $manager = new ConfigurationManager();
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @exceptionMessage Propel expects only one configuration file
     */
    public function testMoreThanOneConfigurationFileInDifferentDirectoriesThrowsException()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $iniConf = <<<EOF
foo = bar
bar = baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('conf/propel.ini', $iniConf);

        $manager = new ConfigurationManager();
    }

    public function testGetSection()
    {
        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->getSection('buildtime');

        $this->assertEquals('bbar', $actual['bfoo']);
        $this->assertEquals('bbaz', $actual['bbar']);
    }

    public function testLoadGivenConfigFile()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml', $yamlConf);

        $manager = new ConfigurationManager('myDir/mySubdir/myConfigFile.yaml');
        $actual = $manager->get();

        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $actual);
    }

    public function testLoadAlsoDistConfigFile()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;

        $this->getFilesystem()->dumpFile('propel.yaml.dist', $yamlDistConf);
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals(array('bfoo' => 'bbar', 'bbar' => 'bbaz'), $actual['buildtime']);
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $actual['runtime']);
    }

    public function testLoadOnlyDistFile()
    {
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;

        $this->getFilesystem()->dumpFile('propel.yaml.dist', $yamlDistConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertEquals(array('runtime' => array('foo' => 'bar', 'bar' => 'baz')), $actual);
    }

    public function testLoadGivenFileAndDist()
    {
        $yamlConf = <<<EOF
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml.dist', $yamlDistConf);

        $manager = new ConfigurationManager('myDir/mySubdir/myConfigFile.yaml');
        $actual = $manager->get();

        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $actual['runtime']);
        $this->assertEquals(array('bfoo' => 'bbar', 'bbar' => 'bbaz'), $actual['buildtime']);
    }

    public function testLoadDistGivenFileOnly()
    {
        $yamlDistConf = <<<EOF
runtime:
    foo: bar
    bar: baz
EOF;
        $this->getFilesystem()->dumpFile('myDir/mySubdir/myConfigFile.yaml.dist', $yamlDistConf);

        $manager = new ConfigurationManager('myDir/mySubdir/myConfigFile.yaml.dist');
        $actual = $manager->get();

        $this->assertEquals(array('runtime' => array('foo' => 'bar', 'bar' => 'baz')), $actual);
    }
}
