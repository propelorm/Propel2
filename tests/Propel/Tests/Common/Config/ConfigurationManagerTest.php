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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager('myDir/mySubdir/myConfigFile.yaml');
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager();
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

        $manager = new TestableConfigurationManager('myDir/mySubdir/myConfigFile.yaml');
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

        $manager = new TestableConfigurationManager('myDir/mySubdir/myConfigFile.yaml.dist');
        $actual = $manager->get();

        $this->assertEquals(array('runtime' => array('foo' => 'bar', 'bar' => 'baz')), $actual);
    }

    public function testMergeExtraProperties()
    {
        $extraConf = array(
            'buildtime' => array(
                'bfoo' => 'extrabar'
            ),
            'extralevel' => array(
                'extra1' => 'val1',
                'extra2' => 'val2'
            )
        );

        $yamlConf = <<<EOF
runtime:
    foo: bar
    bar: baz
buildtime:
    bfoo: bbar
    bbar: bbaz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new TestableConfigurationManager(null, $extraConf);
        $actual = $manager->get();

        $this->assertEquals($actual['runtime'], array('foo' => 'bar', 'bar' => 'baz'));
        $this->assertEquals($actual['buildtime'], array('bfoo' => 'extrabar', 'bbar' => 'bbaz'));
        $this->assertEquals($actual['extralevel'], array('extra1' => 'val1', 'extra2' => 'val2'));
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedMessage Unrecognized options "foo, bar" under "propel"
     */
    public function testInvalidHierarchyTrowsException()
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
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedMessage The child node "runtime" at path "propel" must be configured
     */
    public function testNotDefineRuntimeSectionTrowsException()
    {
        $yamlConf = <<<EOF
propel:
  general:
      project: MyAwesomeProject
      version: 2.0.0-dev
  database:
    connections:
        default:
            adapter: sqlite
            classname: Propel\Runtime\Connection\ConnectionWrapper
            dsn: sqlite:memory
            user:
            password:
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedMessage The child node "runtime" at path "propel" must be configured
     */
    public function testNotDefineDatabaseSectionTrowsException()
    {
        $yamlConf = <<<EOF
propel:
  general:
      project: MyAwesomeProject
      version: 2.0.0-dev
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
    }

    public function testLoadValidConfigurationFile()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->getSection('runtime');

        $this->assertEquals($actual['defaultConnection'], 'mysource');
        $this->assertEquals($actual['connections'], array('mysource', 'yoursource'));
    }

    public function testSomeDeafults()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
              attributes:
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
              attributes:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $actual = $manager->get();

        $this->assertTrue($actual['generator']['namespaceAutoPackage']);
        $this->assertEquals($actual['generator']['dateTime']['dateTimeClass'], 'DateTime');
        $this->assertFalse($actual['generator']['schema']['autoPackage']);
        $this->assertEquals($actual['generator']['objectModel']['pluralizerClass'], '\Propel\Common\Pluralizer\StandardEnglishPluralizer');
        $this->assertEquals($actual['generator']['objectModel']['builders']['objectstub'], '\Propel\Generator\Builder\Om\ExtensionObjectBuilder');

    }
}

class TestableConfigurationManager extends ConfigurationManager
{
    public function __construct($filename = 'propel', $extraConf = array())
    {
        $this->load($filename, $extraConf);
    }
}
