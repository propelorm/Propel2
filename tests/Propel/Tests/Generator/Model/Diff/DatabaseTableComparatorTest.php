<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Diff\DatabaseDiff;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\TestCase;

/**
 * Tests for the Table method of the DatabaseComparator service class.
 */
class DatabaseTableComparatorTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->platform = new MysqlPlatform();
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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
        $this->assertEquals(['Bar' => $t4], $databaseDiff->getAddedTables());
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testCompareModifiedTableSkipSql()
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
        $t2 = new Table('Foo_Table');
        $t2->setSkipSql(true);

        $c2 = (new Column('Foo'));
        $c2->setNotNull(false);

        $t2->addColumn($c2);
        $d2->addTable($t2);

        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();

        $this->assertSame(0, $nbDiffs);
    }

    /**
     * @return void
     */
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
        $this->assertEquals(['Bar' => $t2], $databaseDiff->getRemovedTables());
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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
        $this->assertEquals(['Foo_Table' => $tableDiff], $databaseDiff->getModifiedTables());
    }

    /**
     * @return void
     */
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
        $dc->setWithRenaming(true);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($databaseDiff->getRenamedTables()));
        $this->assertEquals(['Foo_Table' => 'Foo_Table2'], $databaseDiff->getRenamedTables());
        $this->assertEquals([], $databaseDiff->getAddedTables());
        $this->assertEquals([], $databaseDiff->getRemovedTables());
    }

    /**
     * @return void
     */
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
        $this->assertEquals(5, $nbDiffs);
        $this->assertEquals([], $databaseDiff->getRenamedTables());
        $this->assertEquals(['Bar2' => $t4, 'Biz' => $t5], $databaseDiff->getAddedTables());
        $this->assertEquals(['Baz' => $t11, 'Bar' => $t2], $databaseDiff->getRemovedTables());
        $tableDiff = TableComparator::computeDiff($t1, $t3);
        $this->assertEquals(['Foo_Table' => $tableDiff], $databaseDiff->getModifiedTables());
    }

    /**
     * @return void
     */
    public function testCompareSeveralRenamedSameTables()
    {
        $d1 = new Database();
        $t1 = new Table('table1');
        $c1 = new Column('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $t2 = new Table('table2');
        $c2 = new Column('col1');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t2->addColumn($c2);
        $d1->addTable($t2);
        $t3 = new Table('table3');
        $c3 = new Column('col1');
        $c3->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t3->addColumn($c3);
        $d1->addTable($t3);

        $d2 = new Database();
        $t4 = new Table('table4');
        $c4 = new Column('col1');
        $c4->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t4->addColumn($c4);
        $d2->addTable($t4);
        $t5 = new Table('table5');
        $c5 = new Column('col1');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t5->addColumn($c5);
        $d2->addTable($t5);
        $t6 = new Table('table3');
        $c6 = new Column('col1');
        $c6->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $t6->addColumn($c6);
        $d2->addTable($t6);

        // table1 and table2 were removed and table4, table5 added with same columns (does not always mean its a rename, hence we
        // can not guarantee it)
        $dc = new DatabaseComparator();
        $dc->setFromDatabase($d1);
        $dc->setToDatabase($d2);
        $nbDiffs = $dc->compareTables();
        $databaseDiff = $dc->getDatabaseDiff();
        $this->assertEquals(4, $nbDiffs);
        $this->assertEquals(0, count($databaseDiff->getRenamedTables()));
        $this->assertEquals(['table4', 'table5'], array_keys($databaseDiff->getAddedTables()));
        $this->assertEquals(['table1', 'table2'], array_keys($databaseDiff->getRemovedTables()));
    }

    /**
     * @return void
     */
    public function testRemoveTable()
    {
        $dc = new DatabaseComparator();
        $this->assertTrue($dc->getRemoveTable());

        $dc->setRemoveTable(false);
        $this->assertFalse($dc->getRemoveTable());

        $dc->setRemoveTable(true);
        $this->assertTrue($dc->getRemoveTable());

        $d1 = new Database();
        $t1 = new Table('Foo');
        $d1->addTable($t1);
        $d2 = new Database();

        // with renaming false and remove false
        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, false);
        $this->assertFalse($diff);

        // with renaming true and remove false
        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, false);
        $this->assertFalse($diff);

        // with renaming false and remove true
        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        // with renaming true and remove true
        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);
    }

    /**
     * @return void
     */
    public function testExcludedTablesWithoutRenaming()
    {
        $dc = new DatabaseComparator();
        $this->assertCount(0, $dc->getExcludedTables());

        $dc->setExcludedTables(['foo']);
        $this->assertCount(1, $dc->getExcludedTables());

        $d1 = new Database();
        $d2 = new Database();
        $t2 = new Table('Bar');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Bar']);
        $this->assertFalse($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Baz']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $d1 = new Database();
        $t1 = new Table('Foo');
        $d1->addTable($t1);
        $d2 = new Database();
        $t2 = new Table('Bar');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Bar', 'Foo']);
        $this->assertFalse($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Foo']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $d1 = new Database();
        $t1 = new Table('Foo');
        $c1 = new Column('col1');
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $d2 = new Database();
        $t2 = new Table('Foo');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Bar', 'Foo']);
        $this->assertFalse($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, false, true, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);
    }

    /**
     * @return void
     */
    public function testExcludedTablesWithRenaming()
    {
        $dc = new DatabaseComparator();
        $this->assertCount(0, $dc->getExcludedTables());

        $dc->setExcludedTables(['foo']);
        $this->assertCount(1, $dc->getExcludedTables());

        $d1 = new Database();
        $d2 = new Database();
        $t2 = new Table('Bar');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Bar']);
        $this->assertFalse($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Baz']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $d1 = new Database();
        $t1 = new Table('Foo');
        $d1->addTable($t1);
        $d2 = new Database();
        $t2 = new Table('Bar');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Bar', 'Foo']);
        $this->assertFalse($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Foo']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);

        $d1 = new Database();
        $t1 = new Table('Foo');
        $c1 = new Column('col1');
        $t1->addColumn($c1);
        $d1->addTable($t1);
        $d2 = new Database();
        $t2 = new Table('Foo');
        $d2->addTable($t2);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Bar', 'Foo']);
        $this->assertFalse($diff);

        $diff = DatabaseComparator::computeDiff($d1, $d2, false, true, true, ['Bar']);
        $this->assertInstanceOf('Propel\Generator\Model\Diff\DatabaseDiff', $diff);
    }
}
