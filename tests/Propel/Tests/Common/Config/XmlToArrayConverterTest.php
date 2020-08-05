<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Common\Config;

use Propel\Common\Config\XmlToArrayConverter;

class XmlToArrayConverterTest extends ConfigTestCase
{
    use DataProviderTrait;

    /**
     * @dataProvider providerForXmlToArrayConverter
     *
     * @return void
     */
    public function testConvertFromString($xml, $expected)
    {
        $actual = XmlToArrayConverter::convert($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverter
     *
     * @return void
     */
    public function testConvertFromFile($xml, $expected)
    {
        $this->dumpTempFile('testconvert.xml', $xml);
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/testconvert.xml');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverterXmlInclusions
     *
     * @return void
     */
    public function testConvertFromFileWithXmlInclusion($xmlLoad, $xmlInclude, $expected)
    {
        $this->dumpTempFile('testconvert.xml', $xmlLoad);
        $this->dumpTempFile('testconvert_include.xml', $xmlInclude);
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/testconvert.xml');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage XmlToArrayConverter::convert method expects an xml file to parse, or a string containing valid xml
     *
     * @return void
     */
    public function testInvalidFileNameThrowsException()
    {
        XmlToArrayConverter::convert(1);
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     *
     * @return void
     */
    public function testInexistentFileThrowsException()
    {
        XmlToArrayConverter::convert('nonexistent.xml');
    }

    /**
     * @expectedException \Propel\Common\Config\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid xml content
     *
     * @return void
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
     * @expectedException \Propel\Common\Config\Exception\XmlParseException
     * @expectedExceptionMessage An error occurred while parsing XML configuration file:
     *
     * @return void
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
     * @expectedException \Propel\Common\Config\Exception\XmlParseException
     * @expectedExceptionMessage Some errors occurred while parsing XML configuration file:
    - Fatal Error 76: Opening and ending tag mismatch: titles line 4 and title
    - Fatal Error 73: expected '>'
    - Fatal Error 5: Extra content at the end of the document
     *
     * @return void
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

    /**
     * @return void
     */
    public function testEmptyFileReturnsEmptyArray()
    {
        $this->dumpTempFile('empty.xml', '');
        $actual = XmlToArrayConverter::convert(sys_get_temp_dir() . '/empty.xml');

        $this->assertEquals([], $actual);
    }
}
