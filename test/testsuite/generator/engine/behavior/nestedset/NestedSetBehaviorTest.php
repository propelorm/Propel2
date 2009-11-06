<?php

/*
 *	$Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests for NestedSetBehavior class
 *
 * @author		François Zaninotto
 * @version		$Revision: 1133 $
 * @package		generator.engine.behavior.nestedset
 */
class NestedSetBehaviorTest extends BookstoreTestBase 
{
	public function testDefault()
	{
		$table9 = Table9Peer::getTableMap();
		$this->assertEquals(count($table9->getColumns()), 5, 'nested_set adds three column by default');
		$this->assertTrue(method_exists('Table9', 'getTreeLeft'), 'nested_set adds a tree_left column by default');
		$this->assertTrue(method_exists('Table9', 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left column');
		$this->assertTrue(method_exists('Table9', 'getTreeRight'), 'nested_set adds a tree_right column by default');
		$this->assertTrue(method_exists('Table9', 'getRightValue'), 'nested_set maps the right_value getter with the tree_right column');
		$this->assertTrue(method_exists('Table9', 'getTreeLevel'), 'nested_set adds a tree_level column by default');
		$this->assertTrue(method_exists('Table9', 'getLevel'), 'nested_set maps the level getter with the tree_level column');
		$this->assertFalse(method_exists('Table9', 'getTreeScope'), 'nested_set does not add a tree_scope column by default');
		$this->assertFalse(method_exists('Table9', 'getScopeValue'), 'nested_set does not map the scope_value getter with the tree_scope column by default');

	}
	
	public function testParameters()
	{
		$table10 = Table10Peer::getTableMap();
		$this->assertEquals(count($table10->getColumns()), 6, 'nested_set does not add columns when add_columns parameter is false');
		$this->assertTrue(method_exists('Table10', 'getLeftValue'), 'nested_set maps the left_value getter with the tree_left column');
		$this->assertTrue(method_exists('Table10', 'getRightValue'), 'nested_set maps the right_value getter with the tree_right column');
		$this->assertTrue(method_exists('Table10', 'getLevel'), 'nested_set maps the level getter with the tree_level column');
		$this->assertTrue(method_exists('Table10', 'getScopeValue'), 'nested_set maps the scope_value getter with the tree_scope column when the use_scope parameter is true');
	}

}