<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\ClassNotFoundException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Tests\Common\Config\ConfigTestCase;
use Propel\Tests\TestCase;
use Propel\Generator\Util\VfsTrait;
use ReflectionClass;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti
 * @package propel.generator.config
 */
class GeneratorConfigTest extends TestCase
{
    use VfsTrait;

    protected $generatorConfig;

    /**
     * @return void
     */
    public function setConfig($config)
    {
        $ref = new ReflectionClass('\\Propel\\Common\\Config\\ConfigurationManager');
        $refProp = $ref->getProperty('config');
        $refProp->setAccessible(true);
        $refProp->setValue($this->generatorConfig, $config);
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $php = "
<?php
    return [
        'propel' => [
            'database' => [
                'connections' => [
                    'mysource' => [
                        'adapter' => 'sqlite',
                        'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                        'dsn' => 'sqlite:" . sys_get_temp_dir() . "/mydb',
                        'user' => 'root',
                        'password' => '',
                        'model_paths' => [
                            'src',
                            'vendor',
                        ],
                    ],
                    'yoursource' => [
                        'adapter' => 'mysql',
                        'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                        'dsn' => 'mysql:host=localhost;dbname=yourdb',
                        'user' => 'root',
                        'password' => '',
                        'model_paths' => [
                            'src',
                            'vendor',
                        ],
                    ],
                ],
            ],
            'runtime' => [
                'defaultConnection' => 'mysource',
                'connections' => ['mysource', 'yoursource'],
            ],
            'generator' => [
                'defaultConnection' => 'mysource',
                'connections' => ['mysource', 'yoursource'],
            ],
        ]
    ];
";
        $file = $this->newFile('propel.php.dist', $php);

        $this->generatorConfig = new GeneratorConfig($file->url());
    }

    /**
     * @return void
     */
    public function testGetConfiguredPlatformDeafult()
    {
        $actual = $this->generatorConfig->getConfiguredPlatform();

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\MysqlPlatform', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredPlatformGivenDatabaseName()
    {
        $actual = $this->generatorConfig->getConfiguredPlatform(null, 'mysource');

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\SqlitePlatform', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredPlatform()
    {
        $this->setConfig(['generator' => ['platformClass' => '\\Propel\\Generator\\Platform\\PgsqlPlatform']]);
        $actual = $this->generatorConfig->getConfiguredPlatform();
        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\PgsqlPlatform', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredPlatformGivenBadDatabaseNameThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid database name: no configured connection named `badsource`.');

        $this->generatorConfig->getConfiguredPlatform(null, 'badsource');
    }

    /**
     * @return void
     */
    public function testGetConfiguredPlatformGivenPlatform()
    {
        $this->setConfig(['generator' => ['platformClass' => '\\Propel\\Generator\\Platform\\PgsqlPlatform']]);
        $actual = $this->generatorConfig->getConfiguredPlatform();

        $this->assertInstanceOf('\\Propel\\Generator\\Platform\\PgsqlPlatform', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredSchemaParserDefaultClass()
    {
        $stubCon = $this->getMockBuilder('\\Propel\\Runtime\\Connection\\ConnectionWrapper')
            ->disableOriginalConstructor()->getMock();

        $actual = $this->generatorConfig->getConfiguredSchemaParser($stubCon);

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\SqliteSchemaParser', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredSchemaParserGivenClass()
    {
        $this->setConfig(
            [
            'migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Reverse\\PgsqlSchemaParser',
            ]]
        );
        $stubCon = $this->getMockBuilder('\\Propel\\Runtime\\Connection\\ConnectionWrapper')
            ->disableOriginalConstructor()->getMock();

        $actual = $this->generatorConfig->getConfiguredSchemaParser($stubCon);

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredSchemaParserGivenNonSchemaParserClass()
    {
        $this->expectException(BuildException::class);
        $this->expectExceptionMessage('Specified class (\Propel\Generator\Platform\MysqlPlatform) does not implement \Propel\Generator\Reverse\SchemaParserInterface interface.');

        $this->setConfig(
            [
            'migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Platform\\MysqlPlatform',
            ]]
        );

        $actual = $this->generatorConfig->getConfiguredSchemaParser();

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredSchemaParserGivenBadClass()
    {
        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('Reverse SchemaParser class for `\Propel\Generator\Reverse\BadSchemaParser` not found.');

        $this->setConfig(
            [
            'migrations' => [
                'tableName' => 'propel_migration',
                'parserClass' => '\\Propel\\Generator\\Reverse\\BadSchemaParser',
            ]]
        );

        $actual = $this->generatorConfig->getConfiguredSchemaParser();

        $this->assertInstanceOf('\\Propel\\Generator\\Reverse\\PgsqlSchemaParser', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredBuilder()
    {
        $stubTable = $this->getMockBuilder('\\Propel\\Generator\\Model\\Table')
            ->setConstructorArgs(['foo'])
            ->getMock();
        $actual = $this->generatorConfig->getConfiguredBuilder($stubTable, 'query');

        $this->assertInstanceOf('\\Propel\\Generator\\Builder\\Om\\QueryBuilder', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredBuilderWrongTypeThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        $stubTable = $this->getMockBuilder('\\Propel\\Generator\\Model\\Table')
            ->setConstructorArgs(['foo'])
            ->getMock();
        $actual = $this->generatorConfig->getConfiguredBuilder($stubTable, 'bad_type');
    }

    /**
     * @return void
     */
    public function testGetConfiguredPluralizer()
    {
        $actual = $this->generatorConfig->getConfiguredPluralizer();
        $this->assertInstanceOf('\\Propel\\Common\\Pluralizer\\StandardEnglishPluralizer', $actual);

        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Pluralizer\\SimpleEnglishPluralizer';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
        $this->assertInstanceOf('\\Propel\\Common\\Pluralizer\\SimpleEnglishPluralizer', $actual);
    }

    /**
     * @return void
     */
    public function testGetConfiguredPluralizerNonExistentClassThrowsException()
    {
        $this->expectException(ClassNotFoundException::class);
        $this->expectExceptionMessage('Class \Propel\Common\Pluralizer\WrongEnglishPluralizer not found.');

        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Pluralizer\\WrongEnglishPluralizer';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
    }

    /**
     * @return void
     */
    public function testGetConfiguredPluralizerWrongClassThrowsException()
    {
        $this->expectException(BuildException::class);
        $this->expectExceptionMessage('Specified class (\Propel\Common\Config\PropelConfiguration) does not implement');

        $config['generator']['objectModel']['pluralizerClass'] = '\\Propel\\Common\\Config\\PropelConfiguration';
        $this->setConfig($config);

        $actual = $this->generatorConfig->getConfiguredPluralizer();
    }

    /**
     * @return void
     */
    public function testGetBuildConnections()
    {
        $expected = [
            'mysource' => [
                'adapter' => 'sqlite',
                'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'sqlite:' . sys_get_temp_dir() . '/mydb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor',
                ],
            ],
            'yoursource' => [
                'adapter' => 'mysql',
                'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'mysql:host=localhost;dbname=yourdb',
                'user' => 'root',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor',
                ],
            ],
        ];

        $actual = $this->generatorConfig->getBuildConnections();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testGetBuildConnection()
    {
        $expected = [
            'adapter' => 'sqlite',
            'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
            'dsn' => 'sqlite:' . sys_get_temp_dir() . '/mydb',
            'user' => 'root',
            'password' => '',
            'model_paths' => [
                'src',
                'vendor',
            ],
        ];

        $actual = $this->generatorConfig->getBuildConnection();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testGetBuildConnectionGivenDatabase()
    {
        $expected = [
            'adapter' => 'mysql',
            'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
            'dsn' => 'mysql:host=localhost;dbname=yourdb',
            'user' => 'root',
            'password' => '',
            'model_paths' => [
                'src',
                'vendor',
            ],
        ];

        $actual = $this->generatorConfig->getBuildConnection('yoursource');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testGetBuildConnectionGivenWrongDatabaseThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid database name: no configured connection named `wrongsource`.');

        $actual = $this->generatorConfig->getBuildConnection('wrongsource');
    }

    /**
     * @return void
     */
    public function testGetConnectionDefault()
    {
        $actual = $this->generatorConfig->getConnection();

        $this->assertInstanceOf('\\Propel\\Runtime\\Connection\\ConnectionWrapper', $actual);
    }

    /**
     * @return void
     */
    public function testGetConnection()
    {
        $actual = $this->generatorConfig->getConnection('mysource');

        $this->assertInstanceOf('\\Propel\\Runtime\\Connection\\ConnectionWrapper', $actual);
    }

    /**
     * @return void
     */
    public function testGetConnectionWrongDatabaseThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid database name: no configured connection named `badsource`.');

        $actual = $this->generatorConfig->getConnection('badsource');
    }

    /**
     * @return void
     */
    public function testGetBehaviorLocator()
    {
        $actual = $this->generatorConfig->getBehaviorLocator();

        $this->assertInstanceOf('\\Propel\\Generator\\Util\\BehaviorLocator', $actual);
    }
}
