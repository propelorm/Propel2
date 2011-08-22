<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/behavior/ArchivableBehavior.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';

/**
 * Tests for ArchivableBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior
 */
class ArchivableBehaviorTest extends PHPUnit_Framework_TestCase
{
	protected static $generatedSQL;

	public function setUp()
	{
		if (!class_exists('ArchivableTest1')) {
			$schema = <<<EOF
<database name="archivable_behavior_test_0">

	<table name="archivable_test_1">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="title" type="VARCHAR" size="100" primaryString="true" />
		<column name="age" type="INTEGER" />
		<column name="foo_id" type="INTEGER" />
		<foreign-key foreignTable="archivable_test_2">
			<reference local="foo_id" foreign="id" />
		</foreign-key>
		<index>
			<index-column name="title" />
			<index-column name="age" />
		</index>
		<behavior name="archivable" />
	</table>

	<table name="archivable_test_2">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="title" type="VARCHAR" size="100" primaryString="true" />
	</table>

	<table name="archivable_test_3">
		<column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
		<column name="title" type="VARCHAR" size="100" primaryString="true" />
		<column name="age" type="INTEGER" />
		<column name="foo_id" type="INTEGER" />
		<behavior name="archivable">
			<parameter name="log_archived_at" value="false" />
			<parameter name="archive_table" value="my_old_archivable_test_3" />
			<parameter name="archive_on_insert" value="true" />
			<parameter name="archive_on_update" value="true" />
			<parameter name="archive_on_delete" value="false" />
		</behavior>
	</table>

</database>
EOF;
			$builder = new PropelQuickBuilder();
			$builder->setSchema($schema);
			self::$generatedSQL = $builder->getSQL();
			$builder->build();
		}
	}

	public function testModifyTableCreatesArchiveTable()
	{
		$table = ArchivableTest1Peer::getTableMap();
		$this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_1_archive'));
	}

	public function testModifyTableCanCreateCustomArchiveTableName()
	{
		$table = ArchivableTest3Peer::getTableMap();
		$this->assertTrue($table->getDatabaseMap()->hasTable('my_old_archivable_test_3'));
	}

	public function testModifyTableCopiesColumnsToArchiveTable()
	{
		$table = ArchivableTest1ArchivePeer::getTableMap();
		$this->assertTrue($table->hasColumn('id')); 
		$this->assertContains('[id] INTEGER NOT NULL,', self::$generatedSQL, 'copied columns are not autoincremented');
		$this->assertTrue($table->hasColumn('title'));
		$this->assertTrue($table->hasColumn('age'));
		$this->assertTrue($table->hasColumn('foo_id'));
	}

	public function testModifyTableDoesNotCopyForeignKeys()
	{
		$table = ArchivableTest1ArchivePeer::getTableMap();
		$this->assertEquals(array(), $table->getRelations());
	}

	public function testModifyTableCopiesIndices()
	{
		$table = ArchivableTest1ArchivePeer::getTableMap();
		$expected = "CREATE INDEX [archivable_test_1_archive_I_1] ON [archivable_test_1_archive] ([title],[age]);";
		$this->assertContains($expected, self::$generatedSQL);
	}

	public function testModifyTableAddsArchivedAtColumnToArchiveTableByDefault()
	{
		$table = ArchivableTest1ArchivePeer::getTableMap();
		$this->assertTrue($table->hasColumn('archived_at'));
	}

	public function testModifyTableDoesNotAddArchivedAtColumnToArchiveTableIfSpecified()
	{
		$table = MyOldArchivableTest3Peer::getTableMap();
		$this->assertFalse($table->hasColumn('archived_at'));
	}

	public function testActiveRecordHasArchiveMethod()
	{
		$this->assertTrue(method_exists('ArchivableTest1', 'archive'));
	}

	public function testActiveRecordArchiveCreatesACopyByDefault()
	{
		ArchivableTest1ArchiveQuery::create()->deleteAll();
		$a = new ArchivableTest1();
		$a->setTitle('foo');
		$a->setAge(12);
		$a->save();
		$a->archive();
		$archive = ArchivableTest1ArchiveQuery::create()
			->filterById($a->getId())
			->findOne();
		$this->assertInstanceOf('ArchivableTest1Archive', $archive);
		$this->assertEquals('foo', $archive->getTitle());
		$this->assertEquals(12, $archive->getAge());
	}

	public function testActiveRecordArchiveUpdatesExistingArchive()
	{
		ArchivableTest1ArchiveQuery::create()->deleteAll();
		$a = new ArchivableTest1();
		$a->setTitle('foo');
		$a->setAge(12);
		$a->save();
		$b = new ArchivableTest1Archive();
		$b->setId($a->getId());
		$b->setTitle('bar');
		$b->save();
		$a->archive();
		$this->assertEquals(1, ArchivableTest1ArchiveQuery::create()->count());
		$this->assertEquals('foo', $b->getTitle());
	}

	public function testActiveRecordArchiveReturnsCurrentObject()
	{
		$a = new ArchivableTest1();
		$ret = $a->archive();
		$this->assertSame($ret, $a);
	}

	public function testActiveRecordInsertDoesNotCreateArchiveByDefault()
	{
		$a = new ArchivableTest1();
		$a->setTitle('foo');
		$a->setAge(12);
		ArchivableTest1ArchiveQuery::create()->deleteAll();
		$a->save();
		$this->assertEquals(0, ArchivableTest1ArchiveQuery::create()->count());
	}

	public function testActiveRecordInsertCreatesArchiveIfSpecified()
	{
		$a = new ArchivableTest3();
		$a->setTitle('foo');
		$a->setAge(12);
		MyOldArchivableTest3Query::create()->deleteAll();
		$a->save();
		$this->assertEquals(1, MyOldArchivableTest3Query::create()->count());
		$archive = MyOldArchivableTest3Query::create()
			->filterById($a->getId())
			->findOne();
		$this->assertInstanceOf('MyOldArchivableTest3', $archive);
		$this->assertEquals('foo', $archive->getTitle());
		$this->assertEquals(12, $archive->getAge());
	}

	public function testActiveRecordUpdateDoesNotCreateArchiveByDefault()
	{
		$a = new ArchivableTest1();
		$a->setTitle('foo');
		$a->setAge(12);
		$a->save();
		$a->setTitle('bar');
		ArchivableTest1ArchiveQuery::create()->deleteAll();
		$a->save();
		$this->assertEquals(0, ArchivableTest1ArchiveQuery::create()->count());
	}

	public function testActiveRecordUpdateCreatesArchiveIfSpecified()
	{
		$a = new ArchivableTest3();
		$a->setTitle('foo');
		$a->setAge(12);
		$a->save();
		$a->setTitle('bar');
		MyOldArchivableTest3Query::create()->deleteAll();
		$a->save();
		$this->assertEquals(1, MyOldArchivableTest3Query::create()->count());
		$archive = MyOldArchivableTest3Query::create()
			->filterById($a->getId())
			->findOne();
		$this->assertInstanceOf('MyOldArchivableTest3', $archive);
		$this->assertEquals('bar', $archive->getTitle());
		$this->assertEquals(12, $archive->getAge());
	}

	public function testActiveRecordDeleteCreatesArchiveByDefault()
	{
		$a = new ArchivableTest1();
		$a->setTitle('foo');
		$a->setAge(12);
		$a->save();
		ArchivableTest1ArchiveQuery::create()->deleteAll();
		$a->delete();
		$this->assertEquals(1, ArchivableTest1ArchiveQuery::create()->count());
		$archive = ArchivableTest1ArchiveQuery::create()
			->filterById($a->getId())
			->findOne();
		$this->assertInstanceOf('ArchivableTest1Archive', $archive);
		$this->assertEquals('foo', $archive->getTitle());
		$this->assertEquals(12, $archive->getAge());
	}

	public function testActiveRecordDeleteDoesNotCreateArchiveIfSpecified()
	{
		$a = new ArchivableTest3();
		$a->setTitle('foo');
		$a->setAge(12);
		$a->save();
		MyOldArchivableTest3Query::create()->deleteAll();
		$a->delete();
		$this->assertEquals(0, MyOldArchivableTest3Query::create()->count());
	}

	public function testActiveRecordHasPopulateFromArchiveMethod()
	{
		$this->assertTrue(method_exists('ArchivableTest1', 'populateFromArchive'));
	}

	public function testActiveRecordPopulateFromArchiveReturnsCurrentObject()
	{
		$archive = new ArchivableTest1Archive();
		$a = new ArchivableTest1();
		$ret = $a->populateFromArchive($archive);
		$this->assertSame($ret, $a);
	}

	public function testActiveRecordPopulateFromArchive()
	{
		ArchivableTest1ArchiveQuery::create()->deleteAll();
		ArchivableTest1Query::create()->deleteAll();
		$archive = new ArchivableTest1Archive();
		$archive->setId(123); // not autoincremented
		$archive->setTitle('foo');
		$archive->setAge(12);
		$archive->save();
		$a = new ArchivableTest1();
		$a->populateFromArchive($archive);
		$this->assertNotEquals(123, $a->getId());
		$this->assertEquals('foo', $a->getTitle());
		$this->assertEquals(12, $a->getAge());
		$b = new ArchivableTest1();
		$b->populateFromArchive($archive, true);
		$this->assertEquals(123, $b->getId());
	}

}
