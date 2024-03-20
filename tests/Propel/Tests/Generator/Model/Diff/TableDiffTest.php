<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model\Diff;

use PHPUnit\Framework\TestCase;
use Propel\Generator\Exception\DiffException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\DefaultPlatform;

class TableDiffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDefaultObjectState()
    {
        $fromTable = new Table('article');
        $toTable = new Table('article');

        $diff = $this->createTableDiff($fromTable, $toTable);

        $this->assertSame($fromTable, $diff->getFromTable());
        $this->assertSame($toTable, $diff->getToTable());
        $this->assertFalse($diff->hasAddedColumns());
        $this->assertFalse($diff->hasAddedFks());
        $this->assertFalse($diff->hasAddedIndices());
        $this->assertFalse($diff->hasAddedPkColumns());
        $this->assertFalse($diff->hasModifiedColumns());
        $this->assertFalse($diff->hasModifiedFks());
        $this->assertFalse($diff->hasModifiedIndices());
        $this->assertFalse($diff->hasModifiedPk());
        $this->assertFalse($diff->hasRemovedColumns());
        $this->assertFalse($diff->hasRemovedFks());
        $this->assertFalse($diff->hasRemovedIndices());
        $this->assertFalse($diff->hasRemovedPkColumns());
        $this->assertFalse($diff->hasRenamedColumns());
        $this->assertFalse($diff->hasRenamedPkColumns());
    }

    /**
     * @return void
     */
    public function testSetAddedColumns()
    {
        $column = new Column('is_published', 'boolean');

        $diff = $this->createTableDiff();
        $diff->setAddedColumns([ $column ]);

        $this->assertCount(1, $diff->getAddedColumns());
        $this->assertSame($column, $diff->getAddedColumn('is_published'));
        $this->assertTrue($diff->hasAddedColumns());
    }

    /**
     * @return void
     */
    public function testRemoveAddedColumn()
    {
        $diff = $this->createTableDiff();
        $diff->addAddedColumn('is_published', new Column('is_published'));
        $diff->removeAddedColumn('is_published');

        $this->assertEmpty($diff->getAddedColumns());
        $this->assertNull($diff->getAddedColumn('is_published'));
        $this->assertFalse($diff->hasAddedColumns());
    }

    /**
     * @return void
     */
    public function testSetRemovedColumns()
    {
        $column = new Column('is_active');

        $diff = $this->createTableDiff();
        $diff->setRemovedColumns([ $column ]);

        $this->assertCount(1, $diff->getRemovedColumns());
        $this->assertSame($column, $diff->getRemovedColumn('is_active'));
        $this->assertTrue($diff->hasRemovedColumns());
    }

    /**
     * @return void
     */
    public function testSetRemoveRemovedColumn()
    {
        $diff = $this->createTableDiff();

        $this->assertNull($diff->getRemovedColumn('is_active'));

        $diff->addRemovedColumn('is_active', new Column('is_active'));
        $diff->removeRemovedColumn('is_active');

        $this->assertFalse($diff->hasRemovedColumns());
    }

    /**
     * @return void
     */
    public function testSetModifiedColumns()
    {
        $columnDiff = new ColumnDiff();

        $diff = $this->createTableDiff();
        $diff->setModifiedColumns([ 'title' => $columnDiff ]);

        $this->assertCount(1, $diff->getModifiedColumns());
        $this->assertTrue($diff->hasModifiedColumns());
    }

    /**
     * @return void
     */
    public function testAddRenamedColumn()
    {
        $fromColumn = new Column('is_published', 'boolean');
        $toColumn = new Column('is_active', 'boolean');

        $diff = $this->createTableDiff();
        $diff->setRenamedColumns([ [ $fromColumn, $toColumn ] ]);

        $this->assertCount(1, $diff->getRenamedColumns());
        $this->assertTrue($diff->hasRenamedColumns());
    }

    /**
     * @return void
     */
    public function testSetAddedPkColumns()
    {
        $column = new Column('id', 'integer', 7);
        $column->setPrimaryKey(true);

        $diff = $this->createTableDiff();
        $diff->setAddedPkColumns([ $column ]);

        $this->assertCount(1, $diff->getAddedPkColumns());
        $this->assertTrue($diff->hasAddedPkColumns());
        $this->assertTrue($diff->hasModifiedPk());
    }

    /**
     * @return void
     */
    public function testRemoveAddedPkColumn()
    {
        $column = new Column('id', 'integer', 7);
        $column->setPrimaryKey(true);

        $diff = $this->createTableDiff();
        $diff->setAddedPkColumns([ $column ]);
        $diff->removeAddedPkColumn('id');

        $this->assertEmpty($diff->getRemovedPkColumns());
        $this->assertFalse($diff->hasAddedPkColumns());
    }

    /**
     * @return void
     */
    public function testCantAddNonPrimaryKeyColumn()
    {
        $this->expectException(DiffException::class);

        $diff = $this->createTableDiff();
        $diff->addAddedPkColumn('id', new Column('id', 'integer'));
    }

    /**
     * @return void
     */
    public function testSetRemovedPkColumns()
    {
        $column = new Column('id', 'integer');
        $column->setPrimaryKey(true);

        $diff = $this->createTableDiff();
        $diff->setRemovedPkColumns([ $column ]);

        $this->assertCount(1, $diff->getRemovedPkColumns());
        $this->assertTrue($diff->hasModifiedPk());
    }

    /**
     * @return void
     */
    public function testRemoveRemovedPkColumn()
    {
        $diff = $this->createTableDiff();
        $diff->addRemovedPkColumn('id', new Column('id', 'integer'));
        $diff->removeRemovedPkColumn('id');

        $this->assertEmpty($diff->getRemovedPkColumns());
    }

    /**
     * @return void
     */
    public function testSetRenamedPkColumns()
    {
        $diff = $this->createTableDiff();
        $diff->setRenamedPkColumns([ [ new Column('id', 'integer'), new Column('post_id', 'integer') ] ]);

        $this->assertCount(1, $diff->getRenamedPkColumns());
        $this->assertTrue($diff->hasModifiedPk());
    }

    /**
     * @return void
     */
    public function testSetAddedIndices()
    {
        $table = new Table('');
        $table->setDatabase(new Database('foo', new DefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->setAddedIndices([ $index ]);

        $this->assertCount(1, $diff->getAddedIndices());
        $this->assertTrue($diff->hasAddedIndices());
    }

    /**
     * @return void
     */
    public function testSetRemovedIndices()
    {
        $table = new Table('');
        $table->setDatabase(new Database('foo', new DefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->setRemovedIndices([ $index ]);

        $this->assertCount(1, $diff->getRemovedIndices());
        $this->assertTrue($diff->hasRemovedIndices());
    }

    /**
     * @return void
     */
    public function testSetModifiedIndices()
    {
        $table = new Table('users');
        $table->setDatabase(new Database('foo', new DefaultPlatform()));

        $fromIndex = new Index('username_unique_idx');
        $fromIndex->setTable($table);
        $fromIndex->setColumns([ new Column('username') ]);

        $toIndex = new Index('username_unique_idx');
        $toIndex->setTable($table);
        $toIndex->setColumns([ new Column('client_id'), new Column('username') ]);

        $diff = $this->createTableDiff();
        $diff->setModifiedIndices([ [ $fromIndex, $toIndex ]]);

        $this->assertCount(1, $diff->getModifiedIndices());
        $this->assertTrue($diff->hasModifiedIndices());
    }

    /**
     * @return void
     */
    public function testSetAddedFks()
    {
        $fk = new ForeignKey('fk_blog_author');

        $diff = $this->createTableDiff();
        $diff->setAddedFks([ $fk ]);

        $this->assertCount(1, $diff->getAddedFks());
        $this->assertTrue($diff->hasAddedFks());
    }

    /**
     * @return void
     */
    public function testRemoveAddedFk()
    {
        $diff = $this->createTableDiff();
        $diff->addAddedFk('fk_blog_author', new ForeignKey('fk_blog_author'));
        $diff->removeAddedFk('fk_blog_author');

        $this->assertEmpty($diff->getAddedFks());
        $this->assertFalse($diff->hasAddedFks());
    }

    /**
     * @return void
     */
    public function testSetRemovedFk()
    {
        $diff = $this->createTableDiff();
        $diff->setRemovedFks([ new ForeignKey('fk_blog_post_author') ]);

        $this->assertCount(1, $diff->getRemovedFks());
        $this->assertTrue($diff->hasRemovedFks());
    }

    /**
     * @return void
     */
    public function testRemoveRemovedFk()
    {
        $diff = $this->createTableDiff();
        $diff->addRemovedFk('blog_post_author', new ForeignKey('blog_post_author'));
        $diff->removeRemovedFk('blog_post_author');

        $this->assertEmpty($diff->getRemovedFks());
        $this->assertFalse($diff->hasRemovedFks());
    }

    /**
     * @return void
     */
    public function testSetModifiedFks()
    {
        $diff = $this->createTableDiff();
        $diff->setModifiedFks([ [ new ForeignKey('blog_post_author'), new ForeignKey('blog_post_has_author') ] ]);

        $this->assertCount(1, $diff->getModifiedFks());
        $this->assertTrue($diff->hasModifiedFks());
    }

    /**
     * @return void
     */
    public function testGetSimpleReverseDiff()
    {
        $tableA = new Table('users');
        $tableB = new Table('users');

        $diff = $this->createTableDiff($tableA, $tableB);
        $reverseDiff = $diff->getReverseDiff();

        $this->assertInstanceOf('Propel\Generator\Model\Diff\TableDiff', $reverseDiff);
        $this->assertSame($tableA, $reverseDiff->getToTable());
        $this->assertSame($tableB, $reverseDiff->getFromTable());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasModifiedColumns()
    {
        $c1 = new Column('title', 'varchar', 50);
        $c2 = new Column('title', 'varchar', 100);

        $columnDiff = new ColumnDiff($c1, $c2);
        $reverseColumnDiff = $columnDiff->getReverseDiff();

        $diff = $this->createTableDiff();
        $diff->addModifiedColumn('title', $columnDiff);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedColumns());
        $this->assertEquals([ 'title' => $reverseColumnDiff ], $reverseDiff->getModifiedColumns());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasRemovedColumns()
    {
        $column = new Column('slug', 'varchar', 100);

        $diff = $this->createTableDiff();
        $diff->addAddedColumn('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ 'slug' => $column], $reverseDiff->getRemovedColumns());
        $this->assertSame($column, $reverseDiff->getRemovedColumn('slug'));
    }

    /**
     * @return void
     */
    public function testReverseDiffHasAddedColumns()
    {
        $column = new Column('slug', 'varchar', 100);

        $diff = $this->createTableDiff();
        $diff->addRemovedColumn('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ 'slug' => $column], $reverseDiff->getAddedColumns());
        $this->assertSame($column, $reverseDiff->getAddedColumn('slug'));
    }

    /**
     * @return void
     */
    public function testReverseDiffHasRenamedColumns()
    {
        $columnA = new Column('login', 'varchar', 15);
        $columnB = new Column('username', 'varchar', 15);

        $diff = $this->createTableDiff();
        $diff->addRenamedColumn($columnA, $columnB);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ [ $columnB, $columnA ] ], $reverseDiff->getRenamedColumns());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasAddedPkColumns()
    {
        $column = new Column('client_id', 'integer');
        $column->setPrimaryKey(true);

        $diff = $this->createTableDiff();
        $diff->addRemovedPkColumn('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertCount(1, $reverseDiff->getAddedPkColumns());
        $this->assertTrue($reverseDiff->hasAddedPkColumns());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasRemovedPkColumns()
    {
        $column = new Column('client_id', 'integer');
        $column->setPrimaryKey(true);

        $diff = $this->createTableDiff();
        $diff->addAddedPkColumn('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertCount(1, $reverseDiff->getRemovedPkColumns());
        $this->assertTrue($reverseDiff->hasRemovedPkColumns());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasRenamedPkColumn()
    {
        $fromColumn = new Column('post_id', 'integer');
        $fromColumn->setPrimaryKey(true);

        $toColumn = new Column('id', 'integer');
        $toColumn->setPrimaryKey(true);

        $diff = $this->createTableDiff();
        $diff->addRenamedPkColumn($fromColumn, $toColumn);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRenamedPkColumns());
        $this->assertSame([[ $toColumn, $fromColumn ]], $reverseDiff->getRenamedPkColumns());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasAddedIndices()
    {
        $table = new Table('');
        $table->setDatabase(new Database('foo', new DefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->addRemovedIndex('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedIndices());
        $this->assertCount(1, $reverseDiff->getAddedIndices());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasRemovedIndices()
    {
        $table = new Table('');
        $table->setDatabase(new Database('foo', new DefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->addAddedIndex('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedIndices());
        $this->assertCount(1, $reverseDiff->getRemovedIndices());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasModifiedIndices()
    {
        $table = new Table('');
        $table->setDatabase(new Database('foo', new DefaultPlatform()));

        $fromIndex = new Index('i1');
        $fromIndex->setTable($table);

        $toIndex = new Index('i1');
        $toIndex->setTable($table);

        $diff = $this->createTableDiff();
        $diff->addModifiedIndex('i1', $fromIndex, $toIndex);

        $reverseDiff = $diff->getReverseDiff();

        $this->assertTrue($reverseDiff->hasModifiedIndices());
        $this->assertSame([ 'i1' => [ $toIndex, $fromIndex ]], $reverseDiff->getModifiedIndices());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasRemovedFks()
    {
        $diff = $this->createTableDiff();
        $diff->addAddedFk('fk_post_author', new ForeignKey('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedFks());
        $this->assertCount(1, $reverseDiff->getRemovedFks());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasAddedFks()
    {
        $diff = $this->createTableDiff();
        $diff->addRemovedFk('fk_post_author', new ForeignKey('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedFks());
        $this->assertCount(1, $reverseDiff->getAddedFks());
    }

    /**
     * @return void
     */
    public function testReverseDiffHasModifiedFks()
    {
        $fromFk = new ForeignKey('fk_1');
        $toFk = new ForeignKey('fk_1');

        $diff = $this->createTableDiff();
        $diff->addModifiedFk('fk_1', $fromFk, $toFk);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFks());
        $this->assertSame([ 'fk_1' => [ $toFk, $fromFk ]], $reverseDiff->getModifiedFks());
    }

    private function createTableDiff(?Table $fromTable = null, ?Table $toTable = null)
    {
        if (null === $fromTable) {
            $fromTable = new Table('users');
        }

        if (null === $toTable) {
            $toTable = new Table('users');
        }

        return new TableDiff($fromTable, $toTable);
    }

    /**
     * @return void
     */
    public function testToString()
    {
        $tableA = new Table('A');
        $tableB = new Table('B');

        $diff = new TableDiff($tableA, $tableB);
        $diff->addAddedColumn('id', new Column('id', 'integer'));
        $diff->addRemovedColumn('category_id', new Column('category_id', 'integer'));

        $colFoo = new Column('foo', 'integer');
        $colBar = new Column('bar', 'integer');
        $tableA->addColumn($colFoo);
        $tableA->addColumn($colBar);

        $diff->addRenamedColumn($colFoo, $colBar);
        $columnDiff = new ColumnDiff($colFoo, $colBar);
        $diff->addModifiedColumn('foo', $columnDiff);

        $fk = new ForeignKey('category');
        $fk->setTable($tableA);
        $fk->setForeignTableCommonName('B');
        $fk->addReference('category_id', 'id');
        $fkChanged = clone $fk;
        $fkChanged->setForeignTableCommonName('C');
        $fkChanged->addReference('bla', 'id2');
        $fkChanged->setOnDelete('cascade');
        $fkChanged->setOnUpdate('cascade');

        $diff->addAddedFk('category', $fk);
        $diff->addModifiedFk('category', $fk, $fkChanged);
        $diff->addRemovedFk('category', $fk);

        $index = new Index('test_index');
        $index->setTable($tableA);
        $index->setColumns([$colFoo]);

        $indexChanged = clone $index;
        $indexChanged->setColumns([$colBar]);

        $diff->addAddedIndex('test_index', $index);
        $diff->addModifiedIndex('test_index', $index, $indexChanged);
        $diff->addRemovedIndex('test_index', $index);

        $string = (string)$diff;

        $expected = '  A:
    addedColumns:
      - id
    removedColumns:
      - category_id
    modifiedColumns:
      A.FOO:
        modifiedProperties:
    renamedColumns:
      foo: bar
    addedIndices:
      - test_index
    removedIndices:
      - test_index
    modifiedIndices:
      - test_index
    addedFks:
      - category
    removedFks:
      - category
    modifiedFks:
      category:
          localColumns: from ["category_id"] to ["category_id","bla"]
          foreignColumns: from ["id"] to ["id","id2"]
          onUpdate: from  to CASCADE
          onDelete: from  to CASCADE
';

        $this->assertEquals($expected, $string);
    }

    /**
     * @return void
     */
    public function testMagicClone()
    {
        $diff = new TableDiff(new Table('A'), new Table('B'));

        $clonedDiff = clone $diff;

        $this->assertNotSame($clonedDiff, $diff);
        $this->assertNotSame($clonedDiff->getFromTable(), $diff->getFromTable());
        $this->assertNotSame($clonedDiff->getToTable(), $diff->getToTable());
    }
}
