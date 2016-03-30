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
        $entity9 = \Map\NestedSetEntity9EntityMap::getEntityMap();
        $this->assertEquals(count($entity9->getFields()), 5, 'nested_set adds three field by default');

        $this->assertTrue(method_exists('NestedSetEntity9', 'getTreeLeft'), 'nested_set adds a tree_left field by default');
        $this->assertTrue(method_exists('NestedSetEntity9', 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left field');
        $this->assertTrue(method_exists('NestedSetEntity9', 'getTreeRight'), 'nested_set adds a tree_right field by default');
        $this->assertTrue(method_exists('NestedSetEntity9', 'getRightValue'), 'nested_set maps the right_value getter with the tree_right field');
        $this->assertTrue(method_exists('NestedSetEntity9', 'getTreeLevel'), 'nested_set adds a tree_level field by default');
        $this->assertTrue(method_exists('NestedSetEntity9', 'getLevel'), 'nested_set maps the level getter with the tree_level field');
        $this->assertFalse(method_exists('NestedSetEntity9', 'getTreeScope'), 'nested_set does not add a tree_scope field by default');
        $this->assertFalse(method_exists('NestedSetEntity9', 'getScopeValue'), 'nested_set does not map the scope_value getter with the tree_scope field by default');

        $t = new \NestedSetEntity9();
        $t->setTreeLeft('123');
        $this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
        $t->setTreeRight('456');
        $this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
        $t->setLevel('789');
        $this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
    }

    public function testParameters()
    {
        $entity10 = \Map\NestedSetEntity10EntityMap::getEntityMap();
        $this->assertEquals(count($entity10->getFields()), 6, 'nested_set does not add fields when they already exist');

        $this->assertTrue(method_exists('NestedSetEntity10', 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left field');
        $this->assertTrue(method_exists('NestedSetEntity10', 'getRightValue'), 'nested_set maps the right_value getter with the tree_right field');
        $this->assertTrue(method_exists('NestedSetEntity10', 'getLevel'), 'nested_set maps the level getter with the tree_level field');
        $this->assertTrue(method_exists('NestedSetEntity10', 'getScopeValue'), 'nested_set maps the scope_value getter with the tree_scope field when the use_scope parameter is true');

        $t = new \NestedSetEntity10();
        $t->setMyLeftField('123');
        $this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
        $t->setMyRightField('456');
        $this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
        $t->setMyLevelField('789');
        $this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
        $t->setMyScopeField('012');
        $this->assertEquals($t->getScopeValue(), '012', 'nested_set adds a getScopeValue() method');
    }

    public function testConstants()
    {
        $this->assertEquals(\Map\NestedSetEntity9EntityMap::LEFT_COL, 'NestedSetEntity9.tree_left', 'nested_set adds a LEFT_COL constant');
        $this->assertEquals(\Map\NestedSetEntity9EntityMap::RIGHT_COL, 'NestedSetEntity9.tree_right', 'nested_set adds a RIGHT_COL constant');
        $this->assertEquals(\Map\NestedSetEntity9EntityMap::LEVEL_COL, 'NestedSetEntity9.tree_level', 'nested_set adds a LEVEL_COL constant');
    }

    public function testConstantsWithScope()
    {
        $this->assertEquals(\Map\NestedSetEntity10EntityMap::LEFT_COL,  'NestedSetEntity10.my_left_field');
        $this->assertEquals(\Map\NestedSetEntity10EntityMap::RIGHT_COL, 'NestedSetEntity10.my_right_field');
        $this->assertEquals(\Map\NestedSetEntity10EntityMap::LEVEL_COL, 'NestedSetEntity10.my_level_field');
        $this->assertEquals(\Map\NestedSetEntity10EntityMap::SCOPE_COL, 'NestedSetEntity10.my_scope_field');
    }
}
