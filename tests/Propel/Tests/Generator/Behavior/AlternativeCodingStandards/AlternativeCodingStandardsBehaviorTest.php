<?php

/*
 *	$Id: TimestampableBehaviorTest.php 2035 2010-11-14 17:54:27Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\AlternativeCodingStandards;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Behavior\AlternativeCodingStandards\AlternativeCodingStandardsBehavior;

/**
 * Tests for AlternativeCodingStandardsBehavior class
 *
 * @author François Zaninotto
 */
class AlternativeCodingStandardsBehaviorTest extends \PHPUnit_Framework_TestCase
{
    public function convertBracketsNewlineDataProvider()
    {
        return array(
            array("class Foo {
}", "class Foo
{
}"),
            array("if (true) {
}", "if (true)
{
}"),
            array("} else {
}", "}
else
{
}"),
            array("foreach (\$i as \$j) {
}", "foreach (\$i as \$j)
{
}"),
        );
    }

    /**
     * @dataProvider convertBracketsNewlineDataProvider
     */
    public function testConvertBracketsNewline($input, $output)
    {
        $b = new TestableAlternativeCodingStandardsBehavior();
        $b->filter($input);
        $this->assertEquals($output, $input);
    }
}

class TestableAlternativeCodingStandardsBehavior extends AlternativeCodingStandardsBehavior {
    public function filter(&$script)
    {
        return parent::filter($script);
    }
}
