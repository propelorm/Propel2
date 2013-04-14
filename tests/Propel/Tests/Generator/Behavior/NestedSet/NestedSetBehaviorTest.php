<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

/**
 * Tests for NestedSetBehavior class
 *
 * @author François Zaninotto
 */
class NestedSetBehaviorTest extends TestCase
{
    public function testDefault()
    {
        $table9 = \Map\NestedSetTable9TableMap::getTableMap();
        $this->assertEquals(count($table9->getColumns()), 5, 'nested_set adds three column by default');

        $this->assertTrue(method_exists('NestedSetTable9', 'getTreeLeft'), 'nested_set adds a tree_left column by default');
        $this->assertTrue(method_exists('NestedSetTable9', 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left column');
        $this->assertTrue(method_exists('NestedSetTable9', 'getTreeRight'), 'nested_set adds a tree_right column by default');
        $this->assertTrue(method_exists('NestedSetTable9', 'getRightValue'), 'nested_set maps the right_value getter with the tree_right column');
        $this->assertTrue(method_exists('NestedSetTable9', 'getTreeLevel'), 'nested_set adds a tree_level column by default');
        $this->assertTrue(method_exists('NestedSetTable9', 'getLevel'), 'nested_set maps the level getter with the tree_level column');
        $this->assertFalse(method_exists('NestedSetTable9', 'getTreeScope'), 'nested_set does not add a tree_scope column by default');
        $this->assertFalse(method_exists('NestedSetTable9', 'getScopeValue'), 'nested_set does not map the scope_value getter with the tree_scope column by default');

    }

    public function testParameters()
    {
        $table10 = \Map\NestedSetTable10TableMap::getTableMap();
        $this->assertEquals(count($table10->getColumns()), 6, 'nested_set does not add columns when they already exist');

        $this->assertTrue(method_exists('NestedSetTable10', 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left column');
        $this->assertTrue(method_exists('NestedSetTable10', 'getRightValue'), 'nested_set maps the right_value getter with the tree_right column');
        $this->assertTrue(method_exists('NestedSetTable10', 'getLevel'), 'nested_set maps the level getter with the tree_level column');
        $this->assertTrue(method_exists('NestedSetTable10', 'getScopeValue'), 'nested_set maps the scope_value getter with the tree_scope column when the use_scope parameter is true');
    }
}
