<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\ArrayToPhpConverter;
use Propel\Tests\TestCase;

class ArrayToPhpConverterTest extends TestCase
{
    public function testConvertConvertsSimpleDatasourceSection()
    {
        $conf = array('datasources' => array(
          'bookstore' => array(
            'adapter' => 'mysql',
            'connection' => array(
              'classname' => 'DebugPDO',
              'dsn' => 'mysql:host=localhost;dbname=bookstore',
              'user' => 'testuser',
              'password' => 'password',
              'options' =>  array('ATTR_PERSISTENT' => false),
              'attributes' => array('ATTR_EMULATE_PREPARES' => true),
            ),
          ),
          'default' => 'bookstore',
        ));
        $expected = <<<'EOF'
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('bookstore', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
$manager->setConfiguration(array (
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
$manager->setName('bookstore');
$serviceContainer->setConnectionManager('bookstore', $manager);
$serviceContainer->setDefaultDatasource('bookstore');
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    public function testConvertConvertsMasterSlaveDatasourceSection()
    {
        $conf = array('datasources' => array(
          'bookstore-cms' => array(
            'adapter' => 'mysql',
            'connection' => array('dsn' => 'mysql:host=localhost;dbname=bookstore'),
            'slaves' => array(
              'connection' => array(
                array('dsn' => 'mysql:host=slave-server1; dbname=bookstore'),
                array('dsn' => 'mysql:host=slave-server2; dbname=bookstore'),
              ),
            ),
          ),
        ));
        $expected = <<<'EOF'
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('bookstore-cms', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave();
$manager->setReadConfiguration(array (
  'connection' =>
  array (
    0 =>
    array (
      'dsn' => 'mysql:host=slave-server1; dbname=bookstore',
    ),
    1 =>
    array (
      'dsn' => 'mysql:host=slave-server2; dbname=bookstore',
    ),
  ),
));
$manager->setWriteConfiguration(array (
  'dsn' => 'mysql:host=localhost;dbname=bookstore',
));
$manager->setName('bookstore-cms');
$serviceContainer->setConnectionManager('bookstore-cms', $manager);
$serviceContainer->setDefaultDatasource('bookstore-cms');
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    public function testConvertConvertsProfilerSection()
    {
        $conf = array('profiler' => array(
            'class' => '\Runtime\Runtime\Util\Profiler',
            'slowTreshold' => 0.2,
            'details' => array(
                'time' => array('name' => 'Time', 'precision' => 3, 'pad' => '8'),
                'mem' => array('name' => 'Memory', 'precision' => 3, 'pad' => '8'),
            ),
            'innerGlue' => ': ',
            'outerGlue' => ' | '
        ));
        $expected = <<<'EOF'
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setProfilerClass('\Runtime\Runtime\Util\Profiler');
$serviceContainer->setProfilerConfiguration(array (
  'slowTreshold' => 0.2,
  'details' =>
  array (
    'time' =>
    array (
      'name' => 'Time',
      'precision' => 3,
      'pad' => '8',
    ),
    'mem' =>
    array (
      'name' => 'Memory',
      'precision' => 3,
      'pad' => '8',
    ),
  ),
  'innerGlue' => ': ',
  'outerGlue' => ' | ',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    public function testConvertConvertsLogSection()
    {
        $conf = array('log' => array('logger' => array(
            'type' => 'stream',
            'name' => 'defaultLogger',
            'level' => '300',
            'path' => '/var/log/propel.log',
        )));
        $expected = <<<'EOF'
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'level' => '300',
  'path' => '/var/log/propel.log',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    public function testConvertConvertsLogSectionWithMultipleLoggers()
    {
        $conf = array('log' => array(
            'logger' => array(
                array(
                    'type' => 'stream',
                    'path' => '/var/log/propel.log',
                    'level' => '300',
                    'name' => 'defaultLogger',
                ),
                array(
                    'type' => 'stream',
                    'path' => '/var/log/propel_bookstore.log',
                    'name' => 'bookstore',
                ),
            ),
        ));
        $expected = <<<'EOF'
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'path' => '/var/log/propel.log',
  'level' => '300',
));
$serviceContainer->setLoggerConfiguration('bookstore', array (
  'type' => 'stream',
  'path' => '/var/log/propel_bookstore.log',
));
EOF;
        $this->assertEquals($expected, ArrayToPhpConverter::convert($conf));
    }

    public function testConvertConvertsCompleteConfiguration()
    {
        $conf = array(
          'log' => array('logger' => array(
            'type' => 'stream',
            'name' => 'defaultLogger',
            'level' => '300',
            'path' => '/var/log/propel.log',
          )),
          'datasources' => array(
            'bookstore' => array(
              'adapter' => 'mysql',
              'connection' => array(
                'classname' => '\\Propel\\Runtime\\Connection\\DebugPDO',
                'dsn' => 'mysql:host=127.0.0.1;dbname=test',
                'user' => 'root',
                'password' => '',
                'options' => array(
                  'ATTR_PERSISTENT' => false,
                ),
                'attributes' => array(
                  'ATTR_EMULATE_PREPARES' => true,
                ),
                'settings' => array(
                  'charset' => 'utf8',
                ),
              ),
            ),
            'bookstore-cms' => array(
              'adapter' => 'mysql',
              'connection' => array('dsn' => 'mysql:host=localhost;dbname=bookstore'),
              'slaves' => array(
                'connection' => array(
                  array('dsn' => 'mysql:host=slave-server1; dbname=bookstore'),
                  array('dsn' => 'mysql:host=slave-server2; dbname=bookstore'),
                ),
              ),
            ),
            'default' => 'bookstore',
          ),
        );
        $expected = <<<'EOF'
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('bookstore', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
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
$manager->setName('bookstore');
$serviceContainer->setConnectionManager('bookstore', $manager);
$serviceContainer->setAdapterClass('bookstore-cms', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave();
$manager->setReadConfiguration(array (
  'connection' =>
  array (
    0 =>
    array (
      'dsn' => 'mysql:host=slave-server1; dbname=bookstore',
    ),
    1 =>
    array (
      'dsn' => 'mysql:host=slave-server2; dbname=bookstore',
    ),
  ),
));
$manager->setWriteConfiguration(array (
  'dsn' => 'mysql:host=localhost;dbname=bookstore',
));
$manager->setName('bookstore-cms');
$serviceContainer->setConnectionManager('bookstore-cms', $manager);
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
