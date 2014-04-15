<?php

/*
 *	$Id: TableTest.php 1891 2010-08-09 15:03:18Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Model\Database;
use \Propel\Tests\TestCase;

/**
 * Tests for the Column methods of the TableComparator service class.
 *
 */
class PropelTableForeignKeyComparatorTest extends TestCase
{
    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameFks()
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

        $this->assertFalse(TableComparator::computeDiff($t1, $t2));
    }

    public function testCompareNotSameFks()
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);

        $t2 = new Table('Baz');

        $diff = TableComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof TableDiff);
    }

    public function testCaseInsensitive()
    {
        $t1 = new Table('Baz');
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1->addForeignKey($fk1);

        $t2 = new Table('bAZ');
        $c3 = new Column('fOO');
        $c4 = new Column('bAR');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2->addForeignKey($fk2);

        $diff = TableComparator::computeDiff($t1, $t2, true);
        $this->assertFalse($diff);
    }

    public function testCompareAddedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $t1 = new Table('Baz');
        $db1->addTable($t1);

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $db2->addTable($t2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareForeignKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getAddedFks()));
        $this->assertEquals(array('Baz_fk_9c94ed' => $fk2), $tableDiff->getAddedFks());
    }

    public function testCompareRemovedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $db1->addTable($t1);

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $t2 = new Table('Baz');
        $db2->addTable($t2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareForeignKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getRemovedFks()));
        $this->assertEquals(array('Baz_fk_9c94ed' => $fk1), $tableDiff->getRemovedFks());
    }

    public function testCompareModifiedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey('my_foreign_key');
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $db1->addTable($t1);

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar2');
        $fk2 = new ForeignKey('my_foreign_key');
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $db2->addTable($t2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareForeignKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getModifiedFks()));
        $this->assertEquals(array('my_foreign_key' => array($fk1, $fk2)), $tableDiff->getModifiedFks());
    }
}
