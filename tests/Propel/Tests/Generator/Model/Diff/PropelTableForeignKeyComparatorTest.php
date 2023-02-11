<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\TestCase;

/**
 * Tests for the Column methods of the TableComparator service class.
 */
class PropelTableForeignKeyComparatorTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->platform = new MysqlPlatform();
    }


    public function createForeignKey(array $columns, string $refTableName = 'RefTableName', string $fkTableName = 'FkTableName'): ForeignKey
    {
        $fk = ForeignKeyComparatorTest::createForeignKey($columns, $refTableName, $fkTableName);
        $fk->getTable()->getDatabase()->setPlatform($this->platform);
        
        return $fk;
    }

    /**
     * @return void
     */
    public function testCompareSameFks()
    {
        $fk1 = $this->createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = $this->createForeignKey(['FkCol' => 'RefCol']);

        $this->assertFalse(TableComparator::computeDiff($fk1->getTable(), $fk2->getTable()));
    }

    /**
     * @return void
     */
    public function testCompareNotSameFks()
    {
        $fk1 = $this->createForeignKey(['FkCol' => 'RefCol'], 'RefTable', 'FkTable');
        $t2 = new Table('FkTable');

        $diff = TableComparator::computeDiff($fk1->getTable(), $t2);
        $this->assertTrue($diff instanceof TableDiff);
    }

    /**
     * @return void
     */
    public function testCaseInsensitive()
    {
        $fk1 = $this->createForeignKey(['fkcol' => 'refcol'], 'reftable', 'fktable');
        $fk2 = $this->createForeignKey(['FKCOL' => 'REFCOL'], 'REFTABLE', 'FKTABLE');

        $diff = TableComparator::computeDiff($fk1->getTable(), $fk2->getTable(), true);
        $this->assertFalse($diff);
    }

    /**
     * @return void
     */
    public function testCompareAddedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $t1 = new Table('FkTable');
        $db1->addTable($t1);

        $fk2 = $this->createForeignKey(['FkCol' => 'RefCol'], 'RefTable', 'FkTable');
        $t2 = $fk2->getTable();

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareForeignKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getAddedFks()));
        $this->assertEquals(['FkTable_fk_77b9fa' => $fk2], $tableDiff->getAddedFks());
    }

    /**
     * @return void
     */
    public function testCompareRemovedFks()
    {
        
        $fk1 = $this->createForeignKey(['FkCol' => 'RefCol'], 'RefTable', 'FkTable');
        $t1 = $fk1->getTable();

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $t2 = new Table('FkTable');
        $db2->addTable($t2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareForeignKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getRemovedFks()));
        $this->assertEquals(['FkTable_fk_77b9fa' => $fk1], $tableDiff->getRemovedFks());
    }

    /**
     * @return void
     */
    public function testCompareModifiedFks()
    {
        $fk1 = $this->createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = $this->createForeignKey(['FkCol' => 'NotRefCol']);

        $fk1->setName('my_foreign_key');
        $fk2->setName('my_foreign_key');

        $t1 = $fk1->getTable();
        $t2 = $fk2->getTable();

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareForeignKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertCount(1, $tableDiff->getModifiedFks());
        $this->assertEquals(['my_foreign_key' => [$fk1, $fk2]], $tableDiff->getModifiedFks());
    }
}
