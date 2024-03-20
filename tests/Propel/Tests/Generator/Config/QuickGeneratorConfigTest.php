<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Tests\TestCase;

class QuickGeneratorConfigTest extends TestCase
{
    protected $generatorConfig;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->generatorConfig = new QuickGeneratorConfig();
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
        $this->expectExceptionMessage('Invalid data model builder type `bad_type`');

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
    }

    /**
     * @return void
     */
    public function testGetConfiguredPlatform()
    {
        $this->assertNull($this->generatorConfig->getConfiguredPlatform());
    }

    /**
     * @return void
     */
    public function testGetBehaviorLocator()
    {
        $actual = $this->generatorConfig->getBehaviorLocator();

        $this->assertInstanceOf('\\Propel\\Generator\\Util\\BehaviorLocator', $actual);
    }

    /**
     * @return void
     */
    public function testPassExtraConfigProperties()
    {
        $extraConf = [
            'propel' => [
                'database' => [
                    'connections' => [
                        'fakeConn' => [
                            'adapter' => 'sqlite',
                            'dsn' => 'sqlite:fakeDb.sqlite',
                            'user' => '',
                            'password' => '',
                            'model_paths' => [
                                'src',
                                'vendor',
                            ],
                        ],
                    ],
                ],
                'runtime' => [
                    'defaultConnection' => 'fakeConn',
                    'connections' => ['fakeConn', 'default'],
                ],
                'paths' => [
                    'composerDir' => 'path/to/composer',
                ],
            ],
        ];
        $generatorConfig = new QuickGeneratorConfig($extraConf);

        $this->assertEquals('path/to/composer', $generatorConfig->get()['paths']['composerDir']);
        $this->assertEquals('fakeConn', $generatorConfig->get()['runtime']['defaultConnection']);
        $this->assertEquals(['fakeConn', 'default'], $generatorConfig->get()['runtime']['connections']);
        $this->assertEquals(
            [
                'adapter' => 'sqlite',
                'classname' => '\Propel\Runtime\Connection\ConnectionWrapper',
                'dsn' => 'sqlite:fakeDb.sqlite',
                'user' => '',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor',
                ],
            ],
            $generatorConfig->get()['database']['connections']['fakeConn']
        );
        $this->assertEquals(
            [
                'adapter' => 'sqlite',
                'classname' => 'Propel\Runtime\Connection\DebugPDO',
                'dsn' => 'sqlite::memory:',
                'user' => '',
                'password' => '',
                'model_paths' => [
                    'src',
                    'vendor',
                ],
            ],
            $generatorConfig->get()['database']['connections']['default']
        );
    }
}
