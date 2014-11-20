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
    use DataProviderTrait;

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

    public function testNotExistingConfigFileLoadsDefaultSettingsAndDoesNotThrowExceptions()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('doctrine.yaml', $yamlConf);

        $manager = new TestableConfigurationManager();
    }

    public function testBackupConfigFilesAreIgnored()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml.bak', $yamlConf);
        $this->getFilesystem()->dumpFile('propel.yaml~', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertArrayNotHasKey('bar', $actual);
        $this->assertArrayNotHasKey('baz', $actual);
    }

    public function testUnsupportedExtensionsAreIgnored()
    {
        $yamlConf = <<<EOF
foo: bar
bar: baz
EOF;
        $this->getFilesystem()->dumpFile('propel.log', $yamlConf);

        $manager = new TestableConfigurationManager();
        $actual = $manager->get();

        $this->assertArrayNotHasKey('bar', $actual);
        $this->assertArrayNotHasKey('baz', $actual);
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

    public function testLoadInGivenDirectory()
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
        $this->getFilesystem()->dumpFile('myDir/mySubdir/propel.yaml', $yamlConf);
        $this->getFilesystem()->dumpFile('myDir/mySubdir/propel.yaml.dist', $yamlDistConf);

        $manager = new TestableConfigurationManager('myDir/mySubdir/');
        $actual = $manager->get();

        $this->assertEquals(array('foo' => 'bar', 'bar' => 'baz'), $actual['runtime']);
        $this->assertEquals(array('bfoo' => 'bbar', 'bbar' => 'bbaz'), $actual['buildtime']);
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
     * @expectedExceptionMessage Unrecognized options "foo, bar" under "propel"
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

    public function testNotDefineRuntimeAndGeneratorSectionUsesDefaultConnections()
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

        $this->assertArrayHasKey('runtime', $manager->get());
        $this->assertArrayHasKey('generator', $manager->get());

        $this->assertArrayHasKey('connections', $manager->getSection('runtime'));
        $this->assertArrayHasKey('connections', $manager->getSection('generator'));

        $this->assertEquals(['default'], $manager->get()['runtime']['connections']);
        $this->assertEquals(['default'], $manager->get()['generator']['connections']);
    }

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "database" at path "propel" must be configured
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

    /**
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Dots are not allowed in connection names
     */
    public function testDotInConnectionNamesArentAccepted()
    {
        $yamlConf = <<<EOF
propel:
  database:
      connections:
          mysource.name:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
    }

    /**
     * @dataProvider providerForInvalidConnections
     */
    public function testRuntimeOrGeneratorConnectionIsNotInConfiguredConnectionsThrowsException($yamlConf, $section)
    {
        $this->setExpectedException("Propel\Common\Config\Exception\InvalidConfigurationException",
            "`wrongsource` isn't a valid configured connection (Section: propel.$section.connections).");

        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $manager = new ConfigurationManager();
    }

    /**
     * @dataProvider providerForInvalidDefaultConnection
     */
    public function testRuntimeOrGeneratorDefaultConnectionIsNotInConfiguredConnectionsThrowsException($yamlConf, $section)
    {
        $this->setExpectedException("Propel\Common\Config\Exception\InvalidConfigurationException",
            "`wrongsource` isn't a valid configured connection (Section: propel.$section.defaultConnection).");

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
  generator:
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
  generator:
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

    public function testGetConfigProperty()
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
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $this->assertEquals('mysource', $manager->getConfigProperty('runtime.defaultConnection'));
        $this->assertEquals('yoursource', $manager->getConfigProperty('runtime.connections.1'));
        $this->assertEquals('root', $manager->getConfigProperty('database.connections.mysource.user'));
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid configuration property name
     */
    public function testGetConfigPropertyBadNameThrowsException()
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
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $value = $manager->getConfigProperty(10);
    }

    public function testGetConfigPropertyBadName()
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
  generator:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $manager = new ConfigurationManager();
        $value = $manager->getConfigProperty('database.connections.adapter');

        $this->assertNull($value);
    }

    public function testProcessWithParam()
    {
        $configs = array(
            'propel' => array(
                'database' => array(
                    'connections' => array(
                        'default' => array(
                            'adapter' => 'sqlite',
                            'classname' => 'Propel\Runtime\Connection\DebugPDO',
                            'dsn' => 'sqlite::memory:',
                            'user' => '',
                            'password' => ''
                        )
                    )
                ),
                'runtime' => array(
                    'defaultConnection' => 'default',
                    'connections' => array('default')
                ),
                'generator' => array(
                    'defaultConnection' => 'default',
                    'connections' => array('default')
                )
            )
        );

        $manager = new NotLoadingConfigurationManager($configs);
        $actual = $manager->GetSection('database')['connections'];

        $this->assertEquals($configs['propel']['database']['connections'], $actual);
    }

    public function testProcessWrongParameter()
    {
        $manager = new NotLoadingConfigurationManager(null);

        $this->assertEmpty($manager->get());
    }

    public function testGetConfigurationParametersArrayTest()
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
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
  runtime:
      defaultConnection: mysource
      connections:
          - mysource
          - yoursource
  generator:
      defaultConnection: mysource
      connections:
          - mysource
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);

        $expectedRuntime = array(
            'mysource' => array(
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=mydb',
                'user' => 'root',
                'password' => ''
            ),
            'yoursource' => array(
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=yourdb',
                'user' => 'root',
                'password' => ''
            )
        );

        $expectedGenerator = array(
            'mysource' => array(
                'adapter' => 'mysql',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=mydb',
                'user' => 'root',
                'password' => ''
            )
        );

        $manager = new ConfigurationManager();
        $this->assertEquals($expectedRuntime, $manager->getConnectionParametersArray('runtime'));
        $this->assertEquals($expectedRuntime, $manager->getConnectionParametersArray()); //default `runtime`
        $this->assertEquals($expectedGenerator, $manager->getConnectionParametersArray('generator'));
        $this->assertNull($manager->getConnectionParametersArray('bad_section'));
    }

    public function testSetConnectionsIfNotDefined()
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
          yoursource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=yourdb
              user: root
              password:
EOF;
        $this->getFilesystem()->dumpFile('propel.yaml', $yamlConf);
        $manager = new ConfigurationManager();

        $this->assertEquals('mysource', $manager->getSection('generator')['defaultConnection']);
        $this->assertEquals('mysource', $manager->getSection('runtime')['defaultConnection']);
        $this->assertEquals(array('mysource', 'yoursource'), $manager->getSection('generator')['connections']);
        $this->assertEquals(array('mysource', 'yoursource'), $manager->getSection('runtime')['connections']);
    }
}

class TestableConfigurationManager extends ConfigurationManager
{
    public function __construct($filename = 'propel', $extraConf = null)
    {
        $this->load($filename, $extraConf);
    }
}

class NotLoadingConfigurationManager extends ConfigurationManager
{
    public function __construct($configs = null)
    {
        $this->process($configs);
    }
}
