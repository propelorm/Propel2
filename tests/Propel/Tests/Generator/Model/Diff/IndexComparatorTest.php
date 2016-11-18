<?php

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\IndexComparator;
use \Propel\Tests\TestCase;

/**
 * Tests for the FieldComparator service class.
 *
 */
class IndexComparatorTest extends TestCase
{
    public function testCompareNoDifference()
    {
        $c1 = new Field('Foo');
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $c2 = new Field('Foo');
        $i2 = new Index('Foo_Index');
        $i2->addField($c2);
        $this->assertFalse(IndexComparator::computeDiff($i1, $i2));

        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $i1 = new Index('Foo_Bar_Index');
        $i1->addField($c1);
        $i1->addField($c2);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar');
        $i2 = new Index('Foo_Bar_Index');
        $i2->addField($c3);
        $i2->addField($c4);
        $this->assertFalse(IndexComparator::computeDiff($i1, $i2));
    }

    public function testCompareCaseInsensitive()
    {
        $c1 = new Field('Foo');
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $c2 = new Field('fOO');
        $i2 = new Index('fOO_iNDEX');
        $i2->addField($c2);
        $this->assertFalse(IndexComparator::computeDiff($i1, $i2, true));
    }

    public function testCompareType()
    {
        $c1 = new Field('Foo');
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $c2 = new Field('Foo');
        $i2 = new Unique('Foo_Index');
        $i2->addField($c2);
        $this->assertTrue(IndexComparator::computeDiff($i1, $i2));
    }

    public function testCompareDifferentFields()
    {
        $c1 = new Field('Foo');
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $c2 = new Field('Bar');
        $i2 = new Unique('Foo_Index');
        $this->assertTrue(IndexComparator::computeDiff($i1, $i2));
    }

    public function testCompareDifferentOrder()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $i1 = new Index('Foo_Bar_Index');
        $i1->addField($c1);
        $i1->addField($c2);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar');
        $i2 = new Index('Foo_Bar_Index');
        $i2->addField($c4);
        $i2->addField($c3);
        $this->assertTrue(IndexComparator::computeDiff($i1, $i2));
    }

}
