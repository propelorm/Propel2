<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Tests\Bookstore\Behavior\Map\NestedSetEntity10EntityMap;
use Propel\Tests\Bookstore\Behavior\Map\NestedSetEntity9EntityMap;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity10;
use Propel\Tests\Bookstore\Behavior\NestedSetEntity9;

/**
 * Tests for NestedSetBehavior class
 *
 * @author François Zaninotto
 * @group database
 */
class NestedSetBehaviorTest extends TestCase
{
    public function testDefault()
    {
        $entity9 = NestedSetEntity9EntityMap::getEntityMap();
        $this->assertEquals(count($entity9->getFields()), 5, 'nested_set adds three field by default');

        $this->assertTrue(method_exists(NestedSetEntity9::class, 'getTreeLeft'), 'nested_set adds a tree_left field by default');
        $this->assertTrue(method_exists(NestedSetEntity9::class, 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left field');
        $this->assertTrue(method_exists(NestedSetEntity9::class, 'getTreeRight'), 'nested_set adds a tree_right field by default');
        $this->assertTrue(method_exists(NestedSetEntity9::class, 'getRightValue'), 'nested_set maps the right_value getter with the tree_right field');
        $this->assertTrue(method_exists(NestedSetEntity9::class, 'getTreeLevel'), 'nested_set adds a tree_level field by default');
        $this->assertTrue(method_exists(NestedSetEntity9::class, 'getLevel'), 'nested_set maps the level getter with the tree_level field');
        $this->assertFalse(method_exists(NestedSetEntity9::class, 'getTreeScope'), 'nested_set does not add a tree_scope field by default');
        $this->assertFalse(method_exists(NestedSetEntity9::class, 'getScopeValue'), 'nested_set does not map the scope_value getter with the tree_scope field by default');

        $t = new NestedSetEntity9();
        $t->setTreeLeft('123');
        $this->assertEquals($t->getLeftValue(), '123', 'nested_set adds a getLeftValue() method');
        $t->setTreeRight('456');
        $this->assertEquals($t->getRightValue(), '456', 'nested_set adds a getRightValue() method');
        $t->setLevel('789');
        $this->assertEquals($t->getLevel(), '789', 'nested_set adds a getLevel() method');
    }

    public function testParameters()
    {
        $entity10 = NestedSetEntity10EntityMap::getEntityMap();
        $this->assertEquals(count($entity10->getFields()), 6, 'nested_set does not add fields when they already exist');

        $this->assertTrue(method_exists(NestedSetEntity10::class, 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left field');
        $this->assertTrue(method_exists(NestedSetEntity10::class, 'getRightValue'), 'nested_set maps the right_value getter with the tree_right field');
        $this->assertTrue(method_exists(NestedSetEntity10::class, 'getLevel'), 'nested_set maps the level getter with the tree_level field');
        $this->assertTrue(method_exists(NestedSetEntity10::class, 'getScopeValue'), 'nested_set maps the scope_value getter with the tree_scope field when the use_scope parameter is true');

        $t = new NestedSetEntity10();
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
        $this->assertEquals(NestedSetEntity9EntityMap::LEFT_COL, 'Propel\Tests\Bookstore\Behavior\NestedSetEntity9.tree_left', 'nested_set adds a LEFT_COL constant');
        $this->assertEquals(NestedSetEntity9EntityMap::RIGHT_COL, 'Propel\Tests\Bookstore\Behavior\NestedSetEntity9.tree_right', 'nested_set adds a RIGHT_COL constant');
        $this->assertEquals(NestedSetEntity9EntityMap::LEVEL_COL, 'Propel\Tests\Bookstore\Behavior\NestedSetEntity9.tree_level', 'nested_set adds a LEVEL_COL constant');
    }

    public function testConstantsWithScope()
    {
        $this->assertEquals(NestedSetEntity10EntityMap::LEFT_COL,  'Propel\Tests\Bookstore\Behavior\NestedSetEntity10.my_left_field');
        $this->assertEquals(NestedSetEntity10EntityMap::RIGHT_COL, 'Propel\Tests\Bookstore\Behavior\NestedSetEntity10.my_right_field');
        $this->assertEquals(NestedSetEntity10EntityMap::LEVEL_COL, 'Propel\Tests\Bookstore\Behavior\NestedSetEntity10.my_level_field');
        $this->assertEquals(NestedSetEntity10EntityMap::SCOPE_COL, 'Propel\Tests\Bookstore\Behavior\NestedSetEntity10.my_scope_field');
    }
}
