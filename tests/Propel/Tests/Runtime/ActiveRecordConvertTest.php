<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveRecord;

use Propel\Runtime\Configuration;
use Propel\Runtime\Map\EntityMap;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for ActiveRecord.
 *
 * @author François Zaninotto
 */
class ActiveRecordConvertTest extends TestCaseFixtures
{
    /**
     * @var Book
     */
    private $book;

    /**
     * @var EntityMap
     */
    private $bookEntityMap;

    protected function setUp()
    {
        parent::setUp();
        $publisher = new Publisher();
        $publisher->setId(1234);
        $publisher->setName('Penguin');
        $author = new Author();
        $author->setId(5678);
        $author->setFirstName('George');
        $author->setLastName('Byron');
        $book = new Book();
        $book->setId(9012);
        $book->setTitle('Don Juan');
        $book->setISBN('0140422161');
        $book->setPrice(12.99);
        $book->setAuthor($author);
        $book->setPublisher($publisher);
        $this->book = $book;

        $this->bookEntityMap = Configuration::getCurrentConfiguration()->getEntityMapForEntity($this->book);
    }

    public function toXmlDataProvider()
    {
        $expected = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <id>9012</id>
  <title><![CDATA[Don Juan]]></title>
  <ISBN><![CDATA[0140422161]]></ISBN>
  <price>12.99</price>
  <publisher>
    <id>1234</id>
    <name><![CDATA[Penguin]]></name>
    <books>
      <book><![CDATA[*RECURSION*]]></book>
    </books>
  </publisher>
  <author>
    <id>5678</id>
    <firstName><![CDATA[George]]></firstName>
    <lastName><![CDATA[Byron]]></lastName>
    <email></email>
    <age></age>
    <books>
      <book><![CDATA[*RECURSION*]]></book>
    </books>
  </author>
</data>

EOF;

        return array(array($expected));
    }

    /**
     * @dataProvider toXmlDataProvider
     */
    public function testToXML($expected)
    {
        $this->assertEquals($expected, $this->bookEntityMap->toXML($this->book));
    }

    /**
     * @dataProvider toXmlDataProvider
     */
    public function testFromXML($expected)
    {
        /** @var Book $book */
        $book = $this->bookEntityMap->fromXML($expected);

        $this->assertEquals($this->book->getTitle(), $book->getTitle());
        $this->assertEquals($this->book->getId(), $book->getId());
        $this->assertEquals($this->book->getPublisher()->getId(), $book->getPublisher()->getId());
        $this->assertEquals($this->book->getPublisher()->getName(), $book->getPublisher()->getName());
        $this->assertEquals($this->book->getAuthor()->getId(), $book->getAuthor()->getId());
        $this->assertEquals($this->book->getAuthor()->getLastName(), $book->getAuthor()->getLastName());
    }

    public function toYamlDataProvider()
    {
        $expected = <<<EOF
id: 9012
title: 'Don Juan'
ISBN: '0140422161'
price: 12.99
publisher:
    id: 1234
    name: Penguin
    books:
        - '*RECURSION*'
author:
    id: 5678
    firstName: George
    lastName: Byron
    email: null
    age: null
    books:
        - '*RECURSION*'

EOF;

        return array(array($expected));
    }

    /**
     * @dataProvider toYamlDataProvider
     */
    public function testToYAML($expected)
    {
        $this->assertEquals($expected, $this->bookEntityMap->toYAML($this->book));
    }

    /**
     * @dataProvider toYamlDataProvider
     */
    public function testFromYAML($expected)
    {
        $book = $this->bookEntityMap->fromYAML($expected);

        $this->assertEquals($this->book->getTitle(), $book->getTitle());
        $this->assertEquals($this->book->getId(), $book->getId());
        $this->assertEquals($this->book->getPublisher()->getId(), $book->getPublisher()->getId());
        $this->assertEquals($this->book->getPublisher()->getName(), $book->getPublisher()->getName());
        $this->assertEquals($this->book->getAuthor()->getId(), $book->getAuthor()->getId());
        $this->assertEquals($this->book->getAuthor()->getLastName(), $book->getAuthor()->getLastName());
    }

    public function toJsonDataProvider()
    {
        $expected = <<<EOF
{"id":9012,"title":"Don Juan","ISBN":"0140422161","price":12.99,"publisher":{"id":1234,"name":"Penguin","books":["*RECURSION*"]},"author":{"id":5678,"firstName":"George","lastName":"Byron","email":null,"age":null,"books":["*RECURSION*"]}}
EOF;

        return array(array($expected));
    }

    /**
     * @dataProvider toJsonDataProvider
     */
    public function testToJSON($expected)
    {
        $this->assertEquals($expected, $this->bookEntityMap->toJSON($this->book));
    }

    /**
     * @dataProvider toJsonDataProvider
     */
    public function testfromJSON($expected)
    {
        $book = $this->bookEntityMap->fromJSON($expected);

        $this->assertEquals($this->book->getTitle(), $book->getTitle());
        $this->assertEquals($this->book->getId(), $book->getId());
        $this->assertEquals($this->book->getPublisher()->getId(), $book->getPublisher()->getId());
        $this->assertEquals($this->book->getPublisher()->getName(), $book->getPublisher()->getName());
        $this->assertEquals($this->book->getAuthor()->getId(), $book->getAuthor()->getId());
        $this->assertEquals($this->book->getAuthor()->getLastName(), $book->getAuthor()->getLastName());
    }

    public function toCsvDataProvider()
    {
        $expected = "id,title,ISBN,price,publisher,author\r\n9012,Don Juan,0140422161,12.99,\"a:3:{s:2:\\\"id\\\";i:1234;s:4:\\\"name\\\";s:7:\\\"Penguin\\\";s:5:\\\"books\\\";a:1:{i:0;s:11:\\\"*RECURSION*\\\";}}\",\"a:6:{s:2:\\\"id\\\";i:5678;s:9:\\\"firstName\\\";s:6:\\\"George\\\";s:8:\\\"lastName\\\";s:5:\\\"Byron\\\";s:5:\\\"email\\\";N;s:3:\\\"age\\\";N;s:5:\\\"books\\\";a:1:{i:0;s:11:\\\"*RECURSION*\\\";}}\"\r\n";

        return array(array($expected));
    }

    /**
     * @dataProvider toCsvDataProvider
     */
    public function testToCSV($expected)
    {
        $this->assertEquals($expected, $this->bookEntityMap->toCSV($this->book));
    }

    /**
     * @dataProvider toCsvDataProvider
     */
    public function testfromCSV($expected)
    {
        $book = $this->bookEntityMap->fromCSV($expected);

        $this->assertEquals($this->book->getTitle(), $book->getTitle());
        $this->assertEquals($this->book->getId(), $book->getId());
        $this->assertEquals($this->book->getPublisher()->getId(), $book->getPublisher()->getId());
        $this->assertEquals($this->book->getPublisher()->getName(), $book->getPublisher()->getName());
        $this->assertEquals($this->book->getAuthor()->getId(), $book->getAuthor()->getId());
        $this->assertEquals($this->book->getAuthor()->getLastName(), $book->getAuthor()->getLastName());
    }

}
