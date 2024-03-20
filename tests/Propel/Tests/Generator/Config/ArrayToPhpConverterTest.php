<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\ArrayToPhpConverter;
use Propel\Tests\TestCase;

class ArrayToPhpConverterTest extends TestCase
{
    /**
     * @return void
     */
    public function testConvertConvertsSimpleDatasourceSection()
    {
        $conf = [
            'connections' => [
                'bookstore' => [
                  'adapter' => 'mysql',
                  'classname' => 'DebugPDO',
                  'dsn' => 'mysql:host=localhost;dbname=bookstore',
                  'user' => 'testuser',
                  'password' => 'password',
                  'options' => ['ATTR_PERSISTENT' => false],
                  'attributes' => ['ATTR_EMULATE_PREPARES' => true],
                ],
            ],
        ];
        $expected = <<<EOF
\$serviceContainer->setAdapterClass('bookstore', 'mysql');
\$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle('bookstore');
\$manager->setConfiguration(array (
  'classname' => 'DebugPDO',
  'dsn' => 'mysql:host=localhost;dbname=bookstore',
  'user' => 'testuser',
  'password' => 'password',
  'options' =>
  array (
    'ATTR_PERSISTENT' => false,
  ),
  'attributes' =>
  array (
    'ATTR_EMULATE_PREPARES' => true,
  ),
));
\$serviceContainer->setConnectionManager(\$manager);
\$serviceContainer->setDefaultDatasource('bookstore');
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    /**
     * @return void
     */
    public function testConvertConvertsMasterSlaveDatasourceSection()
    {
        $conf = [
            'connections' => [
                'bookstore-cms' => [
                    'adapter' => 'mysql',
                    'dsn' => 'mysql:host=localhost;dbname=bookstore',
                    'slaves' => [
                        ['dsn' => 'mysql:host=slave-server1; dbname=bookstore'],
                        ['dsn' => 'mysql:host=slave-server2; dbname=bookstore'],
                    ],
                ],
            ],
        ];
        $expected = <<<'EOF'
$serviceContainer->setAdapterClass('bookstore-cms', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave('bookstore-cms');
$manager->setReadConfiguration(array (
  0 =>
  array (
    'dsn' => 'mysql:host=slave-server1; dbname=bookstore',
  ),
  1 =>
  array (
    'dsn' => 'mysql:host=slave-server2; dbname=bookstore',
  ),
));
$manager->setWriteConfiguration(array (
  'dsn' => 'mysql:host=localhost;dbname=bookstore',
));
$serviceContainer->setConnectionManager($manager);
$serviceContainer->setDefaultDatasource('bookstore-cms');
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    /**
     * @return void
     */
    public function testConvertConvertsProfilerSection()
    {
        $conf = [
        'profiler' => [
            'classname' => '\Propel\Runtime\Util\Profiler',
            'slowThreshold' => 0.2,
            'time' => ['precision' => 3, 'pad' => '8'],
            'memory' => ['precision' => 3, 'pad' => '8'],
            'innerGlue' => ': ',
            'outerGlue' => ' | ',
        ]];
        $expected = <<<'EOF'
$serviceContainer->setProfilerClass('\Propel\Runtime\Util\Profiler');
$serviceContainer->setProfilerConfiguration(array (
  'slowThreshold' => 0.2,
  'time' =>
  array (
    'precision' => 3,
    'pad' => '8',
  ),
  'memory' =>
  array (
    'precision' => 3,
    'pad' => '8',
  ),
  'innerGlue' => ': ',
  'outerGlue' => ' | ',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    /**
     * @return void
     */
    public function testConvertConvertsLogSection()
    {
        $conf = [
        'log' => [
        'defaultLogger' => [
            'type' => 'stream',
            'level' => '300',
            'path' => '/var/log/propel.log',
        ]]];
        $expected = <<<'EOF'
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'level' => '300',
  'path' => '/var/log/propel.log',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    /**
     * @return void
     */
    public function testConvertConvertsLogSectionWithMultipleLoggers()
    {
        $conf = [
        'log' => [
            'defaultLogger' => [
                'type' => 'stream',
                'path' => '/var/log/propel.log',
                'level' => '300',
            ],
            'bookstoreLogger' => [
                'type' => 'stream',
                'path' => '/var/log/propel_bookstore.log',
            ],
        ]];
        $expected = <<<'EOF'
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'path' => '/var/log/propel.log',
  'level' => '300',
));
$serviceContainer->setLoggerConfiguration('bookstoreLogger', array (
  'type' => 'stream',
  'path' => '/var/log/propel_bookstore.log',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    /**
     * @return void
     */
    public function testConvertConvertsCompleteConfiguration()
    {
        $conf = [
          'log' => [
        'defaultLogger' => [
            'type' => 'stream',
            'level' => '300',
            'path' => '/var/log/propel.log',
          ]],
          'connections' => [
            'bookstore' => [
              'adapter' => 'mysql',
              'classname' => '\\Propel\\Runtime\\Connection\\DebugPDO',
              'dsn' => 'mysql:host=127.0.0.1;dbname=test',
              'user' => 'root',
              'password' => '',
              'options' => [
                'ATTR_PERSISTENT' => false,
              ],
              'attributes' => [
                'ATTR_EMULATE_PREPARES' => true,
              ],
              'settings' => [
                'charset' => 'utf8',
              ],
            ],
            'bookstore-cms' => [
              'adapter' => 'mysql',
              'dsn' => 'mysql:host=localhost;dbname=bookstore',
              'slaves' => [
                  ['dsn' => 'mysql:host=slave-server1; dbname=bookstore'],
                  ['dsn' => 'mysql:host=slave-server2; dbname=bookstore'],
                ],
              ],
            ],
            'defaultConnection' => 'bookstore',
        ];
        $expected = <<<'EOF'
$serviceContainer->setAdapterClass('bookstore', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle('bookstore');
$manager->setConfiguration(array (
  'classname' => '\\Propel\\Runtime\\Connection\\DebugPDO',
  'dsn' => 'mysql:host=127.0.0.1;dbname=test',
  'user' => 'root',
  'password' => '',
  'options' =>
  array (
    'ATTR_PERSISTENT' => false,
  ),
  'attributes' =>
  array (
    'ATTR_EMULATE_PREPARES' => true,
  ),
  'settings' =>
  array (
    'charset' => 'utf8',
  ),
));
$serviceContainer->setConnectionManager($manager);
$serviceContainer->setAdapterClass('bookstore-cms', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave('bookstore-cms');
$manager->setReadConfiguration(array (
  0 =>
  array (
    'dsn' => 'mysql:host=slave-server1; dbname=bookstore',
  ),
  1 =>
  array (
    'dsn' => 'mysql:host=slave-server2; dbname=bookstore',
  ),
));
$manager->setWriteConfiguration(array (
  'dsn' => 'mysql:host=localhost;dbname=bookstore',
));
$serviceContainer->setConnectionManager($manager);
$serviceContainer->setDefaultDatasource('bookstore');
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'level' => '300',
  'path' => '/var/log/propel.log',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }
}
