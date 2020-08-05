<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Diff\ForeignKeyComparator;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Tests\TestCase;

/**
 * Tests for the ColumnComparator service class.
 */
class ForeignKeyComparatorTest extends TestCase
{
    /**
     * @return void
     */
    public function testCompareNoDifference()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareCaseInsensitive()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('fOO');
        $c4 = new Column('bAR');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('bAZ');
        $t2->addForeignKey($fk2);
        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2, true));
    }

    /**
     * @return void
     */
    public function testCompareLocalColumn()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo2');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareForeignColumn()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar2');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareColumnMappings()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $c5 = new Column('Foo2');
        $c6 = new Column('Bar2');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $fk2->addReference($c5, $c6);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareOnUpdate()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $fk1->setOnUpdate(ForeignKey::SETNULL);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $fk2->setOnUpdate(ForeignKey::RESTRICT);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareOnDelete()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $fk1->setOnDelete(ForeignKey::SETNULL);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $fk2->setOnDelete(ForeignKey::RESTRICT);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareSort()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $c3 = new Column('Baz');
        $c4 = new Column('Faz');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c3);
        $fk1->addReference($c2, $c4);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $fk2 = new ForeignKey();
        $fk2->addReference($c2, $c4);
        $fk2->addReference($c1, $c3);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }
}
