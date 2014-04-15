<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Behavior\Archivable\ArchivableBehavior;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests for ArchivableBehavior class
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorTest extends TestCase
{
    protected static $generatedSQL;

    public function setUp()
    {
        if (!class_exists('\ArchivableTest1')) {
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
        <behavior name="archivable" />
    </table>

    <table name="archivable_test_2_archive">
        <column name="id" required="true" primaryKey="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
    </table>

    <table name="archivable_test_3">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
        <column name="age" type="INTEGER" />
        <column name="foo_id" type="INTEGER" />
        <unique>
            <unique-column name="title" />
        </unique>
        <behavior name="archivable">
            <parameter name="log_archived_at" value="false" />
            <parameter name="archive_table" value="my_old_archivable_test_3" />
            <parameter name="archive_on_insert" value="true" />
            <parameter name="archive_on_update" value="true" />
            <parameter name="archive_on_delete" value="false" />
        </behavior>
    </table>

    <table name="archivable_test_4">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
        <column name="age" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="archive_class" value="\Propel\Tests\Generator\Behavior\Archivable\FooArchive" />
        </behavior>
    </table>

    <table name="archivable_test_5">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="archive_table" value="archivable_test_5_backup" />
            <parameter name="archive_phpname" value="ArchivableTest5MyBackup" />
        </behavior>
    </table>

</database>
EOF;
            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            self::$generatedSQL = $builder->getSQL();
            $builder->build();
        }
    }

    public function testCreatesArchiveTable()
    {
        $table = \Map\ArchivableTest1TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_1_archive'));
        $this->assertSame("ArchivableTest1Archive", $table->getDatabaseMap()->getTable('archivable_test_1_archive')->getPhpName());
    }

    public function testDoesNotCreateCustomArchiveTableIfExists()
    {
        $table = \Map\ArchivableTest2TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_2_archive'));
    }

    public function testCanCreateCustomArchiveTableName()
    {
        $table = \Map\ArchivableTest3TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('my_old_archivable_test_3'));
        $this->assertSame("MyOldArchivableTest3", $table->getDatabaseMap()->getTable('my_old_archivable_test_3')->getPhpName());
    }

    public function testDoesNotCreateCustomArchiveTableIfArchiveClassIsSpecified()
    {
        $table = \Map\ArchivableTest4TableMap::getTableMap();
        $this->assertFalse($table->getDatabaseMap()->hasTable('archivable_test_4_archive'));
    }

    public function testCanCreateCustomArchiveTableNameAndPhpName()
    {
        $table = \Map\ArchivableTest5TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_5_backup'));
        $this->assertSame("ArchivableTest5MyBackup", $table->getDatabaseMap()->getTable('archivable_test_5_backup')->getPhpName());
    }

    public function testCopiesColumnsToArchiveTable()
    {
        $table = \Map\ArchivableTest1ArchiveTableMap::getTableMap();
        $this->assertTrue($table->hasColumn('id'));
        $this->assertContains('id INTEGER NOT NULL,', self::$generatedSQL, 'copied columns are not autoincremented');
        $this->assertTrue($table->hasColumn('title'));
        $this->assertTrue($table->hasColumn('age'));
        $this->assertTrue($table->hasColumn('foo_id'));
    }

    public function testDoesNotCopyForeignKeys()
    {
        $table = \Map\ArchivableTest1ArchiveTableMap::getTableMap();
        $this->assertEquals(array(), $table->getRelations());
    }

    public function testCopiesIndices()
    {
        $table = \Map\ArchivableTest1ArchiveTableMap::getTableMap();
        $expected = "CREATE INDEX archivable_test_1_archive_i_6c947f ON archivable_test_1_archive (title,age);";
        $this->assertContains($expected, self::$generatedSQL);
    }

    public function testCopiesUniquesToIndices()
    {
        $table = \Map\ArchivableTest2ArchiveTableMap::getTableMap();
        $expected = "CREATE INDEX my_old_archivable_test_3_i_639136 ON my_old_archivable_test_3 (title);";
        $this->assertContains($expected, self::$generatedSQL);
    }

    public function testAddsArchivedAtColumnToArchiveTableByDefault()
    {
        $table = \Map\ArchivableTest1ArchiveTableMap::getTableMap();
        $this->assertTrue($table->hasColumn('archived_at'));
    }

    public function testDoesNotAddArchivedAtColumnToArchiveTableIfSpecified()
    {
        $table = \Map\MyOldArchivableTest3TableMap::getTableMap();
        $this->assertFalse($table->hasColumn('archived_at'));
    }

    public function testDatabaseLevelBehavior()
    {
        $schema = <<<EOF
<database name="archivable_behavior_test_0">
    <behavior name="archivable" />
    <table name="archivable_test_01">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="title" type="VARCHAR" size="100" primaryString="true" />
        <behavior name="archivable" />
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->getSQL();
    }
}
