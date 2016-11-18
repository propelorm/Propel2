<?php


namespace Propel\Tests\Generator\Model\Diff;

/*
 *	$Id: EntityTest.php 1891 2010-08-09 15:03:18Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\RelationComparator;
use \Propel\Tests\TestCase;

/**
 * Tests for the FieldComparator service class.
 *
 */
class RelationComparatorTest extends TestCase
{
    public function testCompareNoDifference()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertFalse(RelationComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareCaseInsensitive()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('fOO');
        $c4 = new Field('bAR');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $t2 = new Entity('bAZ');
        $t2->addRelation($fk2);
        $this->assertFalse(RelationComparator::computeDiff($fk1, $fk2, true));
    }

    public function testCompareLocalField()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('Foo2');
        $c4 = new Field('Bar');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertTrue(RelationComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareForeignField()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar2');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertTrue(RelationComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareFieldMappings()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar');
        $c5 = new Field('Foo2');
        $c6 = new Field('Bar2');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $fk2->addReference($c5, $c6);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertTrue(RelationComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareOnUpdate()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $fk1->setOnUpdate(Relation::SETNULL);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $fk2->setOnUpdate(Relation::RESTRICT);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertTrue(RelationComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareOnDelete()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $fk1->setOnDelete(Relation::SETNULL);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $fk2->setOnDelete(Relation::RESTRICT);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertTrue(RelationComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareSort()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $c3 = new Field('Baz');
        $c4 = new Field('Faz');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c3);
        $fk1->addReference($c2, $c4);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $fk2 = new Relation();
        $fk2->addReference($c2, $c4);
        $fk2->addReference($c1, $c3);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $this->assertFalse(RelationComparator::computeDiff($fk1, $fk2));
    }
}
