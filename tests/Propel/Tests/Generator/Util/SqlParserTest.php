<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Util;

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Util\SqlParser;
use Propel\Tests\TestCase;

class SqlParserTest extends TestCase
{
    public function stripSqlCommentsDataProvider()
    {
        return [
            ['', ''],
            ['foo with no comments', 'foo with no comments'],
            ['foo with // inline comments', 'foo with // inline comments'],
            ["foo with\n// comments", "foo with\n"],
            [" // comments preceded by blank\nfoo", 'foo'],
            ["// slash-style comments\nfoo", 'foo'],
            ["-- dash-style comments\nfoo", 'foo'],
            ["# hash-style comments\nfoo", 'foo'],
            ["/* c-style comments*/\nfoo", "\nfoo"],
            ["foo with\n// comments\nwith foo", "foo with\nwith foo"],
            ["// comments with\nfoo with\n// comments\nwith foo", "foo with\nwith foo"],
        ];
    }

    /**
     * @dataProvider stripSqlCommentsDataProvider
     *
     * @return void
     */
    public function testStripSQLComments($input, $output)
    {
        $parser = new SqlParser();
        $parser->setSQL($input);
        $parser->stripSQLCommentLines();
        $this->assertEquals($output, $parser->getSQL());
    }

    public function convertLineFeedsToUnixStyleDataProvider()
    {
        return [
            ['', ''],
            ['foo bar', 'foo bar'],
            ["foo\nbar", "foo\nbar"],
            ["foo\rbar", "foo\nbar"],
            ["foo\r\nbar", "foo\nbar"],
            ["foo\r\nbar\rbaz\nbiz\r\n", "foo\nbar\nbaz\nbiz\n"],
        ];
    }

    /**
     * @dataProvider convertLineFeedsToUnixStyleDataProvider
     *
     * @return void
     */
    public function testConvertLineFeedsToUnixStyle($input, $output)
    {
        $parser = new SqlParser();
        $parser->setSQL($input);
        $parser->convertLineFeedsToUnixStyle();
        $this->assertEquals($output, $parser->getSQL());
    }

    public function explodeIntoStatementsDataProvider()
    {
        return [
            ['', []],
            ['foo', ['foo']],
            ['foo;', ['foo']],
            ['foo; ', ['foo']],
            ['foo;bar', ['foo', 'bar']],
            ['foo;bar;', ['foo', 'bar']],
            ["f\no\no;\nb\nar\n;", ["f\no\no", "b\nar"]],
            ['foo";"bar;baz', ['foo";"bar', 'baz']],
            ['foo\';\'bar;baz', ['foo\';\'bar', 'baz']],
            ['foo"\";"bar;', ['foo"\";"bar']],
        ];
    }

    /**
     * @dataProvider explodeIntoStatementsDataProvider
     *
     * @return void
     */
    public function testExplodeIntoStatements($input, $output)
    {
        $parser = new SqlParser();
        $parser->setSQL($input);
        $this->assertEquals($output, $parser->explodeIntoStatements());
    }
}
