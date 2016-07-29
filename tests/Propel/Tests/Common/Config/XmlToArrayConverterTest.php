<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config;

use Propel\Common\Config\XmlToArrayConverter;

class XmlToArrayConverterTest extends ConfigTestCase
{
    public function provider()
    {
        return array(
            array(<<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML
, array('movie' => array(0 => array('title' => 'Star Wars'), 1 => array('title' => 'The Lord Of The Rings')))
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
            array(<<<XML
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
        <dsn>mysql:host=slave-server1;dbname=bookstore</dsn>
       </connection>
       <connection>
        <dsn>mysql:host=slave-server2;dbname=bookstore</dsn>
       </connection>
      </slaves>
    </datasource>
  </datasources>
</config>
XML
, array('datasources' => array(
    'bookstore' => array(
        'adapter' => 'mysql',
        'connection' => array('dsn' => 'mysql:host=localhost;dbname=bookstore'),
        'slaves' => array(
            'connection' => array(
                array('dsn' => 'mysql:host=slave-server1;dbname=bookstore'),
                array('dsn' => 'mysql:host=slave-server2;dbname=bookstore'),
            ),
        ),
    ),
    'default' => 'bookstore',
    ))
            ),
            array(<<<XML
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
XML
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
            array(<<<XML
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
XML
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

    /**
     * @dataProvider provider
     */
    public function testConvertFromString($xml, $expected)
    {
        $actual = XmlToArrayConverter::convert($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider provider
     */
    public function testConvertFromFile($xml, $expected)
    {
        $this->dumpTempFile('testconvert.xml', $xml);
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/testconvert.xml');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage XmlToArrayConverter::convert method expects an xml file to parse, or a string containing valid xml
     */
    public function testInvalidFileNameThrowsException()
    {
        XmlToArrayConverter::convert(1);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testInexistentFileThrowsException()
    {
        XmlToArrayConverter::convert('nonexistent.xml');
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     */
    public function testInvalidXmlThrowsException()
    {
        $invalidXml = <<< XML
No xml
only plain text
---------
XML;
        XmlToArrayConverter::convert($invalidXml);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\XmlParseException
     * @expectedExceptionMessage An error occurred while parsing XML configuration file:
     */
    public function testErrorInXmlThrowsException()
    {
        $xmlWithError = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML;
        XmlToArrayConverter::convert($xmlWithError);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\XmlParseException
     * @expectedExceptionMessage Some errors occurred while parsing XML configuration file:
    - Fatal Error 76: Opening and ending tag mismatch: titles line 4 and title
    - Fatal Error 73: expected '>'
    - Fatal Error 5: Extra content at the end of the document
     */
    public function testMultipleErrorsInXmlThrowsException()
    {
        $xmlWithErrors = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</moviess>
XML;
        XmlToArrayConverter::convert($xmlWithErrors);
    }

    public function testEmptyFileReturnsEmptyArray()
    {
        $this->dumpTempFile('empty.xml', '');
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/empty.xml');

        $this->assertEquals(array(), $actual);
    }
}
