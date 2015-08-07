<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Tests\Bookstore\Behavior\SortableTable13Query;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable13TableMap;
use Propel\Tests\Bookstore\Behavior\SortableTable13 as Table13;

/**
 * Tests for SortableBehavior class
 *
 * @author Arnaud Lejosne
 *
 * @group database
 */
class SortableBehaviorWithEnumScopeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateTable13();
    }

    public function testEnumRank()
    {
        $entries = SortableTable13Query::create()->find();
        $this->assertEquals($entries[0]->getRank(), 1);
        $this->assertEquals($entries[1]->getRank(), 2);
        $this->assertEquals($entries[2]->getRank(), 1);
        $this->assertEquals($entries[3]->getRank(), 2);
    }
}
