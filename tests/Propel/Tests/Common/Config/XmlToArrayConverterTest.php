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
    protected $xmlString = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML;

    public function testConvertFromString()
    {
        $actual = XmlToArrayConverter::convert($this->xmlString);

        $this->assertEquals('Star Wars', $actual['movie'][0]['title']);
        $this->assertEquals('The Lord Of The Rings', $actual['movie'][1]['title']);
    }

    public function testConvertFromFile()
    {
        $this->dumpTempFile('testconvert.xml', $this->xmlString);
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/testconvert.xml');

        $this->assertEquals('Star Wars', $actual['movie'][0]['title']);
        $this->assertEquals('The Lord Of The Rings', $actual['movie'][1]['title']);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedMessage XmlToArrayConverter::convert method expects an xml file to parse, or a string containing valid xml
     */
    public function testInvalidFileNameThrowsException()
    {
        XmlToArrayConverter::convert(1);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedMessage Invalid xml content
     */
    public function testInexistentFileThrowsException()
    {
        XmlToArrayConverter::convert('nonexistent.xml');
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedMessage Invalid xml content
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
     * @expectedMessage An error occurs while parsing XML configuration file:
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
     * @expectedMessage Some errors occur while parsing XML configuration file:
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
