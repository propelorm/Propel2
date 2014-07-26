<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Parser;

use Propel\Runtime\Parser\XmlParser;
use Propel\Tests\TestCase;

/**
 * Test for XmlParser class
 *
 * @author Francois Zaninotto
 */
class XmlParserTest extends TestCase
{
    public static function arrayXmlConversionDataProvider()
    {
        return array(
            array(array(), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data/>
", 'empty array'),
            array(array('a' => 1, 'b' => 2), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <a>1</a>
  <b>2</b>
</data>
", 'associative array'),
            array(array('a' => 0, 'b' => null, 'c' => ''), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <a>0</a>
  <b></b>
  <c><![CDATA[]]></c>
</data>
", 'associative array with empty values'),
            array(array('a' => 1, 'b' => 'bar'), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <a>1</a>
  <b><![CDATA[bar]]></b>
</data>
", 'associative array with strings'),
            array(array('a' => '<html><body><p style="width:30px;">Hello, World!</p></body></html>'), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <a><![CDATA[&lt;html&gt;&lt;body&gt;&lt;p style=&quot;width:30px;&quot;&gt;Hello, World!&lt;/p&gt;&lt;/body&gt;&lt;/html&gt;]]></a>
</data>
", 'associative array with code'),
            array(array('a' => 1, 'b' => array('foo' => 2)), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <a>1</a>
  <b>
    <foo>2</foo>
  </b>
</data>
", 'nested associative arrays'),
            array(array('Id' => 123, 'Title' => 'Pride and Prejudice', 'AuthorId' => 456, 'ISBN' => '0553213105', 'Author' => array('Id' => 456, 'FirstName' => 'Jane', 'LastName' => 'Austen')), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <Id>123</Id>
  <Title><![CDATA[Pride and Prejudice]]></Title>
  <AuthorId>456</AuthorId>
  <ISBN><![CDATA[0553213105]]></ISBN>
  <Author>
    <Id>456</Id>
    <FirstName><![CDATA[Jane]]></FirstName>
    <LastName><![CDATA[Austen]]></LastName>
  </Author>
</data>
", 'array resulting from an object conversion'),
            array(array('a1' => 1, 'b2' => 2), "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <a1>1</a1>
  <b2>2</b2>
</data>
", 'keys with numbers'),
            [['time' => new \DateTime('2014-07-23T22:27:17+0200')], "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
  <time type=\"xsd:dateTime\">2014-07-23T22:27:17+0200</time>
</data>
", '\\DateTime objects']
        );
    }

    /**
     * @dataProvider arrayXmlConversionDataProvider
     */
    public function testFromArray($arrayData, $xmlData, $type)
    {
        $parser = new XmlParser();
        $this->assertEquals($xmlData, $parser->fromArray($arrayData), 'XmlParser::fromArray() converts from ' . $type . ' correctly');
    }

    /**
     * @dataProvider arrayXmlConversionDataProvider
     */
    public function testToXML($arrayData, $xmlData, $type)
    {
        $parser = new XmlParser();
        $this->assertEquals($xmlData, $parser->toXML($arrayData), 'XmlParser::toXML() converts from ' . $type . ' correctly');
    }

    /**
     * @dataProvider arrayXmlConversionDataProvider
     */
    public function testToArray($arrayData, $xmlData, $type)
    {
        $parser = new XmlParser();
        $this->assertEquals($arrayData, $parser->toArray($xmlData), 'XmlParser::toArray() converts to ' . $type . ' correctly');
    }

    /**
     * @dataProvider arrayXmlConversionDataProvider
     */
    public function testFromXML($arrayData, $xmlData, $type)
    {
        $parser = new XmlParser();
        $this->assertEquals($arrayData, $parser->fromXML($xmlData), 'XmlParser::fromXML() converts to ' . $type . ' correctly');
    }

    public function testToArrayRespectsNullValues()
    {
        $xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<data>
<Id></Id>
<Title><![CDATA[]]></Title>
</data>";
        $parser = new XmlParser();
        $data = $parser->fromXML($xmlData);
        $this->assertNull($data['Id']);
        $this->assertSame('', $data['Title']);
    }

    public static function listToXMLDataProvider()
    {
        $list = array(
            ['Id' => 123, 'Title' => 'Pride and Prejudice', 'AuthorId' => 456, 'ISBN' => '0553213105', 'Author' => ['Id' => 456, 'FirstName' => 'Jane', 'LastName' => 'Austen']],
            ['Id' => 82, 'Title' => 'Anna Karenina', 'AuthorId' => 543, 'ISBN' => '0143035002', 'Author' => ['Id' => 543, 'FirstName' => 'Leo', 'LastName' => 'Tolstoi']],
            ['Id' => 567, 'Title' => 'War and Peace', 'AuthorId' => 543, 'ISBN' => '067003469X', 'Author' => ['Id' => 543, 'FirstName' => 'Leo', 'LastName' => 'Tolstoi']],
        );
        $xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Books>
  <Book>
    <Id>123</Id>
    <Title><![CDATA[Pride and Prejudice]]></Title>
    <AuthorId>456</AuthorId>
    <ISBN><![CDATA[0553213105]]></ISBN>
    <Author>
      <Id>456</Id>
      <FirstName><![CDATA[Jane]]></FirstName>
      <LastName><![CDATA[Austen]]></LastName>
    </Author>
  </Book>
  <Book>
    <Id>82</Id>
    <Title><![CDATA[Anna Karenina]]></Title>
    <AuthorId>543</AuthorId>
    <ISBN><![CDATA[0143035002]]></ISBN>
    <Author>
      <Id>543</Id>
      <FirstName><![CDATA[Leo]]></FirstName>
      <LastName><![CDATA[Tolstoi]]></LastName>
    </Author>
  </Book>
  <Book>
    <Id>567</Id>
    <Title><![CDATA[War and Peace]]></Title>
    <AuthorId>543</AuthorId>
    <ISBN><![CDATA[067003469X]]></ISBN>
    <Author>
      <Id>543</Id>
      <FirstName><![CDATA[Leo]]></FirstName>
      <LastName><![CDATA[Tolstoi]]></LastName>
    </Author>
  </Book>
</Books>

EOF;

        return array(array($list, $xml));
    }

    /**
     * @dataProvider listToXMLDataProvider
     */
    public function testListToXML($list, $xml)
    {
        $parser = new XmlParser();
        $this->assertEquals($xml, $parser->listToXML($list, 'Books'));
    }

    /**
     * @dataProvider listToXMLDataProvider
     */
    public function testXMLToList($list, $xml)
    {
        $parser = new XmlParser();
        $this->assertEquals($list, $parser->fromXML($xml));
    }
}
