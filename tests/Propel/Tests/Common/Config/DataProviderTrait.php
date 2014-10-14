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
}
