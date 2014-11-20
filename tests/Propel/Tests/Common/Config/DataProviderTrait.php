<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

/**
 * This trait contains the data providers for ConfigurationManagerTest class.
 */
trait DataProviderTrait
{
    public function providerForInvalidConnections()
    {
        return array(
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  runtime:
      defaultConnection: wrongsource
      connections:
          - wrongsource

"
            , 'runtime'),
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  runtime:
      defaultConnection: wrongsource
      connections:
          - mysource
          - wrongsource

"
            , 'runtime'),
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  generator:
      defaultConnection: wrongsource
      connections:
          - wrongsource

"
            , 'generator'),
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  generator:
      defaultConnection: wrongsource
      connections:
          - wrongsource
          - mysource

"
            , 'generator'),
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  generator:
      defaultConnection: wrongsource
      connections:
          - wrongsource

  runtime:
      defaultConnection: wrongsource
      connections:
          - wrongsource


"
            , 'runtime'),
        );
    }

    public function providerForInvalidDefaultConnection()
    {
        return array(
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  runtime:
      defaultConnection: wrongsource
      connections:
          - mysource

"
            , 'runtime'),
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  generator:
      defaultConnection: wrongsource
      connections:
          - mysource

"
            , 'generator'),
            array("
propel:
  database:
      connections:
          mysource:
              adapter: mysql
              classname: Propel\Runtime\Connection\DebugPDO
              dsn: mysql:host=localhost;dbname=mydb
              user: root
              password:
  generator:
      defaultConnection: wrongsource
      connections:
          - mysource

  runtime:
      defaultConnection: wrongsource
      connections:
          - mysource


"
            , 'runtime'),
        );
    }

    public function providerForXmlToArrayConverter()
    {
        return array(
            array(<<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>Star Wars</title>
  <starred>True</starred>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
  <starred>false</starred>
 </movie>
</movies>
XML
            , array('movie' => array(0 => array('title' => 'Star Wars', 'starred' => true), 1 => array('title' => 'The Lord Of The Rings', 'starred' => false)))
            ),
            array(<<< XML
<?xml version="1.0" encoding="utf-8"?>
<config>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/propel.log</path>
      <level>300</level>
    </logger>
    <logger name="bookstore">
      <type>stream</type>
      <path>/var/log/propel_bookstore.log</path>
    </logger>
  </log>
</config>
XML
            , array('log' => array(
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
            ))
            ),
            array(<<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <datasources default="bookstore">
    <datasource id="bookstore">
      <adapter>mysql</adapter>
      <connection>
        <dsn>mysql:host=localhost;dbname=bookstore</dsn>
      </connection>
      <slaves>
       <connection>
        <dsn>mysql:host=slave-server1; dbname=bookstore</dsn>
       </connection>
       <connection>
        <dsn>mysql:host=slave-server2; dbname=bookstore</dsn>
       </connection>
      </slaves>
    </datasource>
  </datasources>
</config>
EOF
            , array('datasources' => array(
                'bookstore' => array(
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
            ))
            ),
            array(<<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <datasources default="bookstore">
    <datasource id="bookstore">
      <adapter>mysql</adapter>
      <connection>
        <dsn>mysql:host=localhost;dbname=bookstore</dsn>
      </connection>
    </datasource>
  </datasources>
</config>
EOF
            , array('datasources' => array(
                'bookstore' => array(
                    'adapter' => 'mysql',
                    'connection' => array(
                        'dsn' => 'mysql:host=localhost;dbname=bookstore',
                    ),
                ),
                'default' => 'bookstore',
            ))
            ),
            array(<<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <profiler class="\Runtime\Runtime\Util\Profiler">
    <slowTreshold>0.2</slowTreshold>
    <details>
      <time name="Time" precision="3" pad="8" />
      <mem name="Memory" precision="3" pad="8" />
    </details>
    <innerGlue>: </innerGlue>
    <outerGlue> | </outerGlue>
  </profiler>
 </config>
EOF
            , array('profiler' => array(
                'class' => '\Runtime\Runtime\Util\Profiler',
                'slowTreshold' => 0.2,
                'details' => array(
                    'time' => array('name' => 'Time', 'precision' => 3, 'pad' => '8'),
                    'mem' => array('name' => 'Memory', 'precision' => 3, 'pad' => '8'),
                ),
                'innerGlue' => ': ',
                'outerGlue' => ' | '
            ))
            )
        );
    }
}
