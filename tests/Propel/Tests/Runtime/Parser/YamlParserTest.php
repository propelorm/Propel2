<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Parser;

use Propel\Runtime\Parser\YamlParser;
use Propel\Tests\TestCase;

/**
 * Test for YamlParser class
 *
 * @author Francois Zaninotto
 */
class YamlParserTest extends TestCase
{
    public static function arrayYAMLConversionDataProvider()
    {
        return [
            [[], '{  }', 'empty array'],
            [[1, 2, 3],
        "- 1
- 2
- 3
", 'regular array'],
            [[1, '2', 3],
            "- 1
- '2'
- 3
", 'array with strings'],
            [[1, 2, [3, 4]],
            "- 1
- 2
-
    - 3
    - 4
", 'nested arrays'],
            [['a' => 1, 'b' => 2],
            "a: 1
b: 2
", 'associative array'],
            [['a' => 0, 'b' => null, 'c' => ''], "a: 0
b: null
c: ''
", 'associative array with empty values'],
            [['a' => 1, 'b' => 'bar'],
            "a: 1
b: bar
", 'associative array with strings'],
            [['a' => '<html><body><p style="width:30px;">Hello, World!</p></body></html>'],
            "a: '<html><body><p style=\"width:30px;\">Hello, World!</p></body></html>'
", 'associative array with code'],
            [['a' => 1, 'b' => ['foo' => 2]],
            "a: 1
b:
    foo: 2
", 'nested associative arrays'],
            [['Id' => 123, 'Title' => 'Pride and Prejudice', 'AuthorId' => 456, 'ISBN' => '0553213105', 'Author' => ['Id' => 456, 'FirstName' => 'Jane', 'LastName' => 'Austen']],
            "Id: 123
Title: 'Pride and Prejudice'
AuthorId: 456
ISBN: '0553213105'
Author:
    Id: 456
    FirstName: Jane
    LastName: Austen
", 'array resulting from an object conversion'],
            [['a1' => 1, 'b2' => 2], "a1: 1
b2: 2
", 'keys with numbers'],
        ];
    }

    /**
     * @dataProvider arrayYAMLConversionDataProvider
     *
     * @return void
     */
    public function testFromArray($arrayData, $YAMLData, $type)
    {
        $parser = new YamlParser();
        $this->assertEquals($YAMLData, $parser->fromArray($arrayData), 'YamlParser::fromArray() converts from ' . $type . ' correctly');
    }

    /**
     * @dataProvider arrayYAMLConversionDataProvider
     *
     * @return void
     */
    public function testToYAML($arrayData, $YAMLData, $type)
    {
        $parser = new YamlParser();
        $this->assertEquals($YAMLData, $parser->toYAML($arrayData), 'YamlParser::toYAML() converts from ' . $type . ' correctly');
    }

    /**
     * @dataProvider arrayYAMLConversionDataProvider
     *
     * @return void
     */
    public function testToArray($arrayData, $YAMLData, $type)
    {
        $parser = new YamlParser();
        $this->assertEquals($arrayData, $parser->toArray($YAMLData), 'YamlParser::toArray() converts to ' . $type . ' correctly');
    }

    /**
     * @dataProvider arrayYAMLConversionDataProvider
     *
     * @return void
     */
    public function testFromYAML($arrayData, $YAMLData, $type)
    {
        $parser = new YamlParser();
        $this->assertEquals($arrayData, $parser->fromYAML($YAMLData), 'YamlParser::fromYAML() converts to ' . $type . ' correctly');
    }

    public static function listToYAMLDataProvider()
    {
        $list = [
            'book0' => ['Id' => 123, 'Title' => 'Pride and Prejudice', 'AuthorId' => 456, 'ISBN' => '0553213105', 'Author' => ['Id' => 456, 'FirstName' => 'Jane', 'LastName' => 'Austen']],
            'book1' => ['Id' => 82, 'Title' => 'Anna Karenina', 'AuthorId' => 543, 'ISBN' => '0143035002', 'Author' => ['Id' => 543, 'FirstName' => 'Leo', 'LastName' => 'Tolstoi']],
            'book2' => ['Id' => 567, 'Title' => 'War and Peace', 'AuthorId' => 543, 'ISBN' => '067003469X', 'Author' => ['Id' => 543, 'FirstName' => 'Leo', 'LastName' => 'Tolstoi']],
        ];
        $yaml = <<<EOF
book0:
    Id: 123
    Title: 'Pride and Prejudice'
    AuthorId: 456
    ISBN: '0553213105'
    Author:
        Id: 456
        FirstName: Jane
        LastName: Austen
book1:
    Id: 82
    Title: 'Anna Karenina'
    AuthorId: 543
    ISBN: '0143035002'
    Author:
        Id: 543
        FirstName: Leo
        LastName: Tolstoi
book2:
    Id: 567
    Title: 'War and Peace'
    AuthorId: 543
    ISBN: 067003469X
    Author:
        Id: 543
        FirstName: Leo
        LastName: Tolstoi

EOF;

        return [[$list, $yaml]];
    }

    /**
     * @dataProvider listToYAMLDataProvider
     *
     * @return void
     */
    public function testListToYAML($list, $yaml)
    {
        $parser = new YamlParser();
        $this->assertEquals($yaml, $parser->toYAML($list));
    }

    /**
     * @dataProvider listToYAMLDataProvider
     *
     * @return void
     */
    public function testYAMLToList($list, $yaml)
    {
        $parser = new YamlParser();
        $this->assertEquals($list, $parser->fromYAML($yaml));
    }
}
