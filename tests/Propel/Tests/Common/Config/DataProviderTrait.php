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
        return [
            ["
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
            , 'runtime'],
            ["
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
            , 'runtime'],
            ["
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
            , 'generator'],
            ["
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
            , 'generator'],
            ["
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
            , 'runtime'],
        ];
    }

    public function providerForInvalidDefaultConnection()
    {
        return [
            ["
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
            , 'runtime'],
            ["
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
            , 'generator'],
            ["
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
            , 'runtime'],
        ];
    }

    public function providerForXmlToArrayConverter()
    {
        return [
            [<<< XML
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
            , ['movie' => [0 => ['title' => 'Star Wars', 'starred' => true], 1 => ['title' => 'The Lord Of The Rings', 'starred' => false]]]
            ],
            [<<< XML
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
            , ['log' => [
                'logger' => [
                    [
                        'type' => 'stream',
                        'path' => '/var/log/propel.log',
                        'level' => '300',
                        'name' => 'defaultLogger',
                    ],
                    [
                        'type' => 'stream',
                        'path' => '/var/log/propel_bookstore.log',
                        'name' => 'bookstore',
                    ],
                ],
            ]]
            ],
            [<<<EOF
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
            , ['datasources' => [
                'bookstore' => [
                    'adapter' => 'mysql',
                    'connection' => ['dsn' => 'mysql:host=localhost;dbname=bookstore'],
                    'slaves' => [
                        'connection' => [
                            ['dsn' => 'mysql:host=slave-server1; dbname=bookstore'],
                            ['dsn' => 'mysql:host=slave-server2; dbname=bookstore'],
                        ],
                    ],
                ],
                'default' => 'bookstore',
            ]]
            ],
            [<<<EOF
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
            , ['datasources' => [
                'bookstore' => [
                    'adapter' => 'mysql',
                    'connection' => [
                        'dsn' => 'mysql:host=localhost;dbname=bookstore',
                    ],
                ],
                'default' => 'bookstore',
            ]]
            ],
            [<<<EOF
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
            , ['profiler' => [
                'class' => '\Runtime\Runtime\Util\Profiler',
                'slowTreshold' => 0.2,
                'details' => [
                    'time' => ['name' => 'Time', 'precision' => 3, 'pad' => '8'],
                    'mem' => ['name' => 'Memory', 'precision' => 3, 'pad' => '8'],
                ],
                'innerGlue' => ': ',
                'outerGlue' => ' | '
            ]]
            ]
        ];
    }

    public function providerForXmlToArrayConverterXmlInclusions() {
        return [
            [
                <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<database name="named" defaultIdMethod="native">
    <xi:include xmlns:xi="http://www.w3.org/2001/XInclude"
                href="testconvert_include.xml"
                xpointer="xpointer( /database/* )"
                />
</database>
XML
                , <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<database name="mixin" defaultIdMethod="native">
    <table name="book" phpName="Book"/>
</database>
XML
            , ['table' => [
                'name' => 'book',
                'phpName' => 'Book',
            ]]
            ],
        ];
    }
}
