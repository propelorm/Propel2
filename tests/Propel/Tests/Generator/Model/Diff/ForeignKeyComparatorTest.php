<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ForeignKeyComparator;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Tests\TestCase;

/**
 * Tests for the ColumnComparator service class.
 */
class ForeignKeyComparatorTest extends TestCase
{

    public static function createForeignKey(array $columns, string $refTableName = 'RefTableName', string $fkTableName = 'FkTableName'): ForeignKey
    {

        $fkTable = new Table($fkTableName);
        $refTable = new Table($refTableName);

        $database = new Database();
        $database->addTable($refTable);
        $database->addTable($fkTable);
        $fk = new ForeignKey();
        $fk->setForeignTableCommonName($refTableName);
        $fkTable->addForeignKey($fk);

    
        foreach($columns as $fkColumnName => $refColumnName){
            $refCol = static::createColumn($refColumnName);
            $refTable->addColumn($refCol);

            $fkCol = static::createColumn($fkColumnName);
            $fkTable->addColumn($fkCol);

            $fk->addReference($fkCol, $refCol);
        }
        
        return $fk;
    }

    public static function createColumn(string $columnName, string $columnType = 'Le type'): Column
    {
        $col = new Column($columnName);
        $col->setName($columnName);
        $col->getDomain()->setSqlType($columnType);

        return $col;
    }

    /**
     * @return void
     */
    public function testCompareNoDifference()
    {
        $fk1 = static::createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = static::createForeignKey(['FkCol' => 'RefCol']);

        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareCaseInsensitive()
    {
        $fk1 = static::createForeignKey(['fkcol' => 'refcol'], 'reftable', 'fktable');
        $fk2 = static::createForeignKey(['FKCOL' => 'REFCOL'], 'REFTABLE', 'FKTABLE');

        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2, true));
    }

    /**
     * @return void
     */
    public function testCompareLocalColumn()
    {
        $fk1 = static::createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = static::createForeignKey(['FkCol' => 'NotRefCol']);
       
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareForeignColumn()
    {
        $fk1 = static::createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = static::createForeignKey(['NotFkCol' => 'RefCol']);

        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareColumnMappings()
    {

        $fk1 = static::createForeignKey(['FkCol1' => 'RefCol1']);
        $fk2 = static::createForeignKey(['FkCol1' => 'RefCol1', 'FkCol2' => 'RefCol2']);

        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareOnUpdate()
    {
        $fk1 = static::createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = static::createForeignKey(['FkCol' => 'RefCol']);

        $fk1->setOnUpdate(ForeignKey::SETNULL);
        $fk2->setOnUpdate(ForeignKey::RESTRICT);

        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareOnDelete()
    {
        $fk1 = static::createForeignKey(['FkCol' => 'RefCol']);
        $fk2 = static::createForeignKey(['FkCol' => 'RefCol']);

        $fk1->setOnDelete(ForeignKey::SETNULL);
        $fk2->setOnDelete(ForeignKey::RESTRICT);

        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareSort()
    {
        $fk1 = static::createForeignKey(['FkCol1' => 'RefCol1', 'FkCol2' => 'RefCol2']);
        $fk2 = static::createForeignKey(['FkCol2' => 'RefCol2', 'FkCol1' => 'RefCol1']);

        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    /**
     * @return void
     */
    public function testCompareColumnType()
    {
        $fk1 = static::createForeignKey(['FkCol1' => 'RefCol1', 'FkCol2' => 'RefCol2']);
        $fk2 = static::createForeignKey(['FkCol1' => 'RefCol1', 'FkCol2' => 'RefCol2']);

        $fk2->getForeignColumnObjects()[1]->getDomain()->setSqlType('Le updated type');

        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }
}
