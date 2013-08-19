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
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the Table method of the DatabaseComparator service class.
 *
 */
class PropelDatabaseTableComparatorTest extends TestCase
{
    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameTables()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $t2 = new Table('Bar');
        $d1->addTable($t2);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);
        $t4 = new Table('Bar');
        $d2->addTable($t4);

        $this->assertFalse(DatabaseComparator::computeDiff($d1, $d2));
    }

    public function testCompareNotSameTables()
    {
        $d1 = new Database();
        $t1 = new Table('Foo');
        $d1->addTable($t1);
        $d2 = new Database();
        $t2 = new Table('Bar');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2);
        $this->assertTrue($diff instanceof DatabaseDiff);
    }

    public function testCompareCaseInsensitive()
    {
        $d1 = new Database();
        $t1 = new Table('Foo');
        $d1->addTable($t1);
        $d2 = new Database();
        $t2 = new Table('fOO');
        $d2->addTable($t2);

        $this->assertFalse(DatabaseComparator::computeDiff($d1, $d2, true));
    }

    public function testCompareAddedTable()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);
        $t4 = new Table('Bar');
        $d2->addTable($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($databaseDiff->getAddedTables()));
        $this->assertEquals(array('Bar' => $t4), $databaseDiff->getAddedTables());
    }

    public function testCompareAddedTableSkipSql()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);
        $t4 = new Table('Bar');
        $t4->setSkipSql(true);
        $d2->addTable($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(0, $nbDiffs);
    }

    public function testCompareRemovedTable()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $t2 = new Table('Bar');
        $d1->addTable($t2);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($databaseDiff->getRemovedTables()));
        $this->assertEquals(array('Bar' => $t2), $databaseDiff->getRemovedTables());
    }

    public function testCompareRemovedTableSkipSql()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $t2 = new Table('Bar');
        $t2->setSkipSql(true);
        $d1->addTable($t2);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(0, $nbDiffs);
    }

    public function testCompareModifiedTable()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $c2 = new Column('Foo2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t1->addColumn($c2);
        $d1->addTable($t1);
        $t2 = new Table('Bar');
        $d1->addTable($t2);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);
        $t4 = new Table('Bar');
        $d2->addTable($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($databaseDiff->getModifiedTables()));
        $tableDiff = TableComparator::computeDiff($t1, $t3);
        $this->assertEquals(array('Foo_Table' => $tableDiff), $databaseDiff->getModifiedTables());
    }

    public function testCompareRenamedTable()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $t2 = new Table('Bar');
        $d1->addTable($t2);

        $d2 = new Database();
        $t3 = new Table('Foo_Table2');
        $c3 = new Column('Foo');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);
        $t4 = new Table('Bar');
        $d2->addTable($t4);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($databaseDiff->getRenamedTables()));
        $this->assertEquals(array('Foo_Table' => 'Foo_Table2'), $databaseDiff->getRenamedTables());
        $this->assertEquals(array(), $databaseDiff->getAddedTables());
        $this->assertEquals(array(), $databaseDiff->getRemovedTables());
    }

    public function testCompareSeveralTableDifferences()
    {
        $d1 = new Database();
        $t1 = new Table('Foo_Table');
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $t2 = new Table('Bar');
        $c2 = new Column('Bar_Column');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $t2->addColumn($c2);
        $d1->addTable($t2);
        $t11 = new Table('Baz');
        $d1->addTable($t11);

        $d2 = new Database();
        $t3 = new Table('Foo_Table');
        $c3 = new Column('Foo1');
        $c3->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceScale(2);
        $c3->getDomain()->replaceSize(3);
        $c3->setNotNull(true);
        $c3->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t3->addColumn($c3);
        $d2->addTable($t3);
        $t4 = new Table('Bar2');
        $c4 = new Column('Bar_Column');
        $c4->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $t4->addColumn($c4);
        $d2->addTable($t4);
        $t5 = new Table('Biz');
        $c5 = new Column('Biz_Column');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t5->addColumn($c5);
        $d2->addTable($t5);

        // Foo_Table was modified, Bar was renamed, Baz was removed, Biz was added
        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(4, $nbDiffs);
        $this->assertEquals(array('Bar' => 'Bar2'), $databaseDiff->getRenamedTables());
        $this->assertEquals(array('Biz' => $t5), $databaseDiff->getAddedTables());
        $this->assertEquals(array('Baz' => $t11), $databaseDiff->getRemovedTables());
        $tableDiff = TableComparator::computeDiff($t1, $t3);
        $this->assertEquals(array('Foo_Table' => $tableDiff), $databaseDiff->getModifiedTables());
    }

}
