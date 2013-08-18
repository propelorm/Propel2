<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Util\SqlParser;
use \Propel\Tests\TestCase;

/**
 *
 */
class SqlParserTest extends TestCase
{
    public function stripSqlCommentsDataProvider()
    {
        return array(
            array('', ''),
            array('foo with no comments', 'foo with no comments'),
            array('foo with // inline comments', 'foo with // inline comments'),
            array("foo with\n// comments", "foo with\n"),
            array(" // comments preceded by blank\nfoo", "foo"),
            array("// slash-style comments\nfoo", "foo"),
            array("-- dash-style comments\nfoo", "foo"),
            array("# hash-style comments\nfoo", "foo"),
            array("/* c-style comments*/\nfoo", "\nfoo"),
            array("foo with\n// comments\nwith foo", "foo with\nwith foo"),
            array("// comments with\nfoo with\n// comments\nwith foo", "foo with\nwith foo"),
        );
    }

    /**
     * @dataProvider stripSqlCommentsDataProvider
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
        return array(
            array('', ''),
            array("foo bar", "foo bar"),
            array("foo\nbar", "foo\nbar"),
            array("foo\rbar", "foo\nbar"),
            array("foo\r\nbar", "foo\nbar"),
            array("foo\r\nbar\rbaz\nbiz\r\n", "foo\nbar\nbaz\nbiz\n"),
        );
    }

    /**
     * @dataProvider convertLineFeedsToUnixStyleDataProvider
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
        return array(
            array('', array()),
            array('foo', array('foo')),
            array('foo;', array('foo')),
            array('foo; ', array('foo')),
            array('foo;bar', array('foo', 'bar')),
            array('foo;bar;', array('foo', 'bar')),
            array("f\no\no;\nb\nar\n;", array("f\no\no", "b\nar")),
            array('foo";"bar;baz', array('foo";"bar', 'baz')),
            array('foo\';\'bar;baz', array('foo\';\'bar', 'baz')),
            array('foo"\";"bar;', array('foo"\";"bar')),
        );
    }
    /**
     * @dataProvider explodeIntoStatementsDataProvider
     */
    public function testExplodeIntoStatements($input, $output)
    {
        $parser = new SqlParser();
        $parser->setSQL($input);
        $this->assertEquals($output, $parser->explodeIntoStatements());
    }
}
