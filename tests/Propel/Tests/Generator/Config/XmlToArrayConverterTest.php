<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Config;

use Propel\Generator\Config\XmlToArrayConverter;
use Propel\Tests\TestCase;

class XmlToArrayConverterTest extends TestCase
{
    public function testConvertConvertsLogSectionOutsidePropelElement()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/propel.log</path>
      <level>300</level>
    </logger>
  </log>
</config>
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('log' => array('logger' => array(
            'type' => 'stream',
            'name' => 'defaultLogger',
            'level' => '300',
            'path' => '/var/log/propel.log',
        )));
        $this->assertEquals($expected, $converted);
    }

    // Only for BC with Propel 1.x - remove at will
    public function testConvertConvertsLogSectionInsidePropelElement()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <propel>
    <log>
      <logger name="defaultLogger">
        <type>stream</type>
        <path>/var/log/propel.log</path>
        <level>300</level>
      </logger>
    </log>
  </propel>
</config>
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('log' => array('logger' => array(
            'type' => 'stream',
            'name' => 'defaultLogger',
            'level' => '300',
            'path' => '/var/log/propel.log',
        )));
        $this->assertEquals($expected, $converted);
    }

    public function testConvertConvertsLogSectionWithMultipleLoggers()
    {
        $xml = <<<EOF
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
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('log' => array(
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
        $this->assertEquals($expected, $converted);
    }

    public function testConvertConvertsDatasourcesSectionWithSingleConnection()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <datasources default="bookstore">
    <datasource id="bookstore">
      <adapter>mysql</adapter>
      <connection>
        <classname>DebugPDO</classname>
        <dsn>mysql:host=localhost;dbname=bookstore</dsn>
        <user>testuser</user>
        <password>password</password>
        <options>
          <option id="ATTR_PERSISTENT">false</option>
        </options>
        <attributes>
          <option id="ATTR_EMULATE_PREPARES">true</option>
        </attributes>
      </connection>
    </datasource>
  </datasources>
</config>
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('datasources' => array(
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
        $this->assertEquals($expected, $converted);
    }

    public function testConvertConvertsDatasourcesSectionWithSlaveSection()
    {
        $xml = <<<EOF
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
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('datasources' => array(
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
        ));
        $this->assertEquals($expected, $converted);
    }

    public function testConvertConvertsDatasourcesSectionOutsidePropelElement()
    {
        $xml = <<<EOF
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
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('datasources' => array(
          'bookstore' => array(
            'adapter' => 'mysql',
            'connection' => array(
              'dsn' => 'mysql:host=localhost;dbname=bookstore',
            ),
          ),
          'default' => 'bookstore',
        ));
        $this->assertEquals($expected, $converted);
    }

    // Only for BC with Propel 1.x - remove at will
    public function testConvertConvertsDatasourcesSectionInsidePropelElement()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <propel>
  <datasources default="bookstore">
    <datasource id="bookstore">
      <adapter>mysql</adapter>
      <connection>
        <dsn>mysql:host=localhost;dbname=bookstore</dsn>
      </connection>
    </datasource>
  </datasources>
  </propel>
</config>
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('datasources' => array(
          'bookstore' => array(
            'adapter' => 'mysql',
            'connection' => array(
              'dsn' => 'mysql:host=localhost;dbname=bookstore',
            ),
          ),
          'default' => 'bookstore',
        ));
        $this->assertEquals($expected, $converted);
    }


    public function testConvertConvertsProfilerSectionOutsidePropelElement()
    {
        $xml = <<<EOF
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
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('profiler' => array(
            'class' => '\Runtime\Runtime\Util\Profiler',
            'slowTreshold' => 0.2,
            'details' => array(
                'time' => array('name' => 'Time', 'precision' => 3, 'pad' => '8'),
                'mem' => array('name' => 'Memory', 'precision' => 3, 'pad' => '8'),
            ),
            'innerGlue' => ': ',
            'outerGlue' => ' | '
        ));
        $this->assertEquals($expected, $converted);
    }

    // Only for BC with Propel 1.x - remove at will
    public function testConvertConvertsProfilerSectionInsidePropelElement()
    {
        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <propel>
    <profiler class="\Runtime\Runtime\Util\Profiler">
      <slowTreshold>0.2</slowTreshold>
      <details>
        <time name="Time" precision="3" pad="8" />
        <mem name="Memory" precision="3" pad="8" />
      </details>
      <innerGlue>: </innerGlue>
      <outerGlue> | </outerGlue>
    </profiler>
  </propel>
 </config>
EOF;
        $converted = XmlToArrayConverter::convert($xml);
        $expected = array('profiler' => array(
            'class' => '\Runtime\Runtime\Util\Profiler',
            'slowTreshold' => 0.2,
            'details' => array(
                'time' => array('name' => 'Time', 'precision' => 3, 'pad' => '8'),
                'mem' => array('name' => 'Memory', 'precision' => 3, 'pad' => '8'),
            ),
            'innerGlue' => ': ',
            'outerGlue' => ' | '
        ));
        $this->assertEquals($expected, $converted);
    }
}
