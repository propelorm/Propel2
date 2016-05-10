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
 * Tests for SortableBehavior repository class
 *
 * @author Cristiano Cinotti
 */
class SortableBehaviorObjectBuilderModificationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->populateEntity11();
    }

    public function testPreInsert()
    {
        $repository = $this->getRepository('\SortableEntity11');
        $repository->deleteAll();

        $t1 = new \SortableEntity11();
        $repository->save($t1);
        $this->assertEquals(1, $t1->getRank(), 'Sortable inserts new line in first position if no row present');
        $t2 = new \SortableEntity11();
        $t2->setTitle('row2');
        $repository->save($t2);
        $this->assertEquals(2, $t2->getRank(), 'Sortable inserts new line in last position');
    }

    public function testPreDelete()
    {
        $repository = $this->getRepository('\SortableEntity11');

        $max = $repository->createQuery()->getMaxRank();
        $t3 = $repository->createQuery()->findOneByRank(3);
        $repository->remove($t3);
        $this->assertEquals($max - 1, $repository->createQuery()->getMaxRank(), 'Sortable rearrange subsequent rows on delete');
        $t4 = $repository->createQuery()->filterByTitle('row4')->findOne();
        $this->assertEquals(3, $t4->getRank(), 'Sortable rearrange subsequent rows on delete');
    }

    public function testPreInsertWithScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $repository->deleteAll();

        $t1 = new \SortableEntity12();
        $t1->setScopeValue(1);
        $repository->save($t1);
        $this->assertEquals($t1->getRank(), 1, 'Sortable inserts new line in first position if no row present');
        $t2 = new \SortableEntity12();
        $t2->setScopeValue(1);
        $repository->save($t2);
        $this->assertEquals($t2->getRank(), 2, 'Sortable inserts new line in last position');
        $t2 = new \SortableEntity12();
        $t2->setScopeValue(2);
        $repository->save($t2);
        $this->assertEquals($t2->getRank(), 1, 'Sortable inserts new line in last position');
    }

    public function testPreDeleteWithScope()
    {
        $repository = $this->getRepository('\SortableEntity12');
        $this->populateEntity12();

        $max = $repository->createQuery()->getMaxRank(1);
        $t3 = $repository->createQuery()->findOneByRank(3, 1);
        $repository->remove($t3);
        $this->assertEquals($max - 1, $repository->createQuery()->getMaxRank(1), 'Sortable rearrange subsequent rows on delete');
        $t4 = $repository->createQuery()->filterByTitle('row4')->findOne();
        $this->assertEquals(3, $t4->getRank(), 'Sortable rearrange subsequent rows on delete');
        $expected = array(1 => 'row5', 2 => 'row6');
        $this->assertEquals($expected, $this->getFixturesArrayWithScope(2), 'delete() leaves other suites unchanged');
    }
}
