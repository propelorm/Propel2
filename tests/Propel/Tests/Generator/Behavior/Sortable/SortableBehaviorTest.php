<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

/**
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 *
 */
class SortableBehaviorTest extends TestCase
{
    public function testParameters()
    {
        $entityMap11 = $this->getConfiguration()->getEntityMap('\SortableEntity11');
        $this->assertEquals(count($entityMap11->getFields()), 3, 'Sortable adds one columns by default');
        $this->assertTrue(method_exists('\SortableEntity11', 'getRank'), 'Sortable adds a rank column by default');

        $entityMap12 = $this->getConfiguration()->getEntityMap('\SortableEntity12');
        $this->assertEquals(count($entityMap12->getFields()), 4, 'Sortable does not add a column when it already exists');
        $this->assertTrue(method_exists('\SortableEntity12', 'getPosition'), 'Sortable allows customization of rank_column name');
    }

    public function testStaticAttributes()
    {
        $this->assertEquals('SortableEntity11.sortable_rank', \Map\SortableEntity11EntityMap::RANK_COL);
        $this->assertEquals('SortableEntity12.position', \Map\SortableEntity12EntityMap::RANK_COL);
        $this->assertEquals('SortableEntity12.my_scope_field', \Map\SortableEntity12EntityMap::SCOPE_COL);
    }
}
