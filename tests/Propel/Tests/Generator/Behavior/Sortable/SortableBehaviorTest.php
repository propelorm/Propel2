<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Behavior\Table11Peer;
use Propel\Tests\Bookstore\Behavior\Table12Peer;

/**
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 * @version		$Revision$
 * @package		generator.behavior.sortable
 */
class SortableBehaviorTest extends BookstoreTestBase
{

    public function testParameters()
    {
        $table11 = Table11Peer::getTableMap();
        $this->assertEquals(count($table11->getColumns()), 3, 'Sortable adds one columns by default');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table11', 'getRank'), 'Sortable adds a rank column by default');
        $table12 = Table12Peer::getTableMap();
        $this->assertEquals(count($table12->getColumns()), 4, 'Sortable does not add a column when it already exists');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table12', 'getPosition'), 'Sortable allows customization of rank_column name');
    }

}
