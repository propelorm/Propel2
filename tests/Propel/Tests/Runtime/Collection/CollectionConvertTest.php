<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Collection;

use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;

use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for Collection.
 *
 * @author Francois Zaninotto
 * @version    $Id: CollectionTest.php 1348 2009-12-03 21:49:00Z francois $
 */
class CollectionConvertTest extends TestCaseFixtures
{
    private $coll;

    protected function setUp()
    {
        parent::setUp();

        $book1 = new Book();
        $book1->setId(9012);
        $book1->setTitle('Don Juan');
        $book1->setISBN('0140422161');
        $book1->setPrice(12.99);
        $book2 = new Book();
        $book2->setId(58);
        $book2->setTitle('Harry Potter and the Order of the Phoenix');
        $book2->setISBN('043935806X');
        $book2->setPrice(10.99);

        $this->coll = new ObjectCollection();
        $this->coll->setModel('\Propel\Tests\Bookstore\Book');
        $this->coll[]= $book1;
        $this->coll[]= $book2;
    }

    public function toXmlDataProvider()
    {
        $expected = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Books>
  <Book>
    <id>9012</id>
    <title><![CDATA[Don Juan]]></title>
    <ISBN><![CDATA[0140422161]]></ISBN>
    <price>12.99</price>
  </Book>
  <Book>
    <id>58</id>
    <title><![CDATA[Harry Potter and the Order of the Phoenix]]></title>
    <ISBN><![CDATA[043935806X]]></ISBN>
    <price>10.99</price>
  </Book>
</Books>

EOF;

        return array(array($expected));
    }

    /**
     * @dataProvider toXmlDataProvider
     */
    public function testToXML($expected)
    {
        $this->assertEquals($expected, $this->coll->toXML());
    }

    /**
     * @dataProvider toXmlDataProvider
     */
    public function testFromXML($expected)
    {
        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');
        $coll->fromXML($expected);

        $this->assertEquals($this->coll->getData(), $coll->getData());
    }

    public function toYamlDataProvider()
    {
        $expected = <<<EOF
Books:
    -
        id: 9012
        title: 'Don Juan'
        ISBN: '0140422161'
        price: 12.99
    -
        id: 58
        title: 'Harry Potter and the Order of the Phoenix'
        ISBN: 043935806X
        price: 10.99

EOF;

        return array(array($expected));
    }

    /**
     * @dataProvider toYamlDataProvider
     */
    public function testToYAML($expected)
    {
        $this->assertEquals($expected, $this->coll->toYAML());
    }

    /**
     * @dataProvider toYamlDataProvider
     */
    public function testFromYAML($expected)
    {
        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');
        $coll->fromYAML($expected);

        $this->assertEquals($this->coll->getData(), $coll->getData());
    }

    public function toJsonDataProvider()
    {
        $expected = <<<EOF
{"Books":[{"id":9012,"title":"Don Juan","ISBN":"0140422161","price":12.99},{"id":58,"title":"Harry Potter and the Order of the Phoenix","ISBN":"043935806X","price":10.99}]}
EOF;

        return array(array($expected));
    }

    /**
     * @dataProvider toJsonDataProvider
     */
    public function testToJSON($expected)
    {
        $this->assertEquals($expected, $this->coll->toJSON());
    }

    /**
     * @dataProvider toJsonDataProvider
     */
    public function testfromJSON($expected)
    {
        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');
        $coll->fromJSON($expected);

        $this->assertEquals($this->coll->getData(), $coll->getData());
    }

    public function toCsvDataProvider()
    {
        $expected = "id,title,ISBN,price\r\n9012,Don Juan,0140422161,12.99\r\n58,Harry Potter and the Order of the Phoenix,043935806X,10.99\r\n";

        return array(array($expected));
    }

    /**
     * @dataProvider toCsvDataProvider
     */
    public function testToCSV($expected)
    {
        $this->assertEquals($expected, $this->coll->toCSV());
    }

    /**
     * @dataProvider toCsvDataProvider
     */
    public function testfromCSV($expected)
    {
        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');
        $coll->fromCSV($expected);

        $this->assertEquals($this->coll->getData(), $coll->getData());
    }

    /**
     * @dataProvider toYamlDataProvider
     */
    public function testToStringUsesDefaultStringFormat($expected)
    {
        $this->assertEquals($expected, (string) $this->coll, 'Collection::__toString() uses the YAML representation by default');
    }

    public function testToStringUsesCustomStringFormat()
    {
        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Publisher');
        $publisher = new Publisher();
        $publisher->setId(12345);
        $publisher->setName('Penguinoo');
        $coll[]= $publisher;
        $expected = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<Publishers>
  <Publisher>
    <id>12345</id>
    <name><![CDATA[Penguinoo]]></name>
  </Publisher>
</Publishers>

EOF;
        $this->assertEquals($expected, (string) $coll);
    }

}
