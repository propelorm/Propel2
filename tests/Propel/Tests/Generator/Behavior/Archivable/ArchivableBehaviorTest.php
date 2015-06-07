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
            <parameter name="archive_entity" value="\Propel\Tests\Generator\Behavior\Archivable\FooArchive" />
        </behavior>
    </table>

    <table name="archivable_test_5">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="archive_table" value="archivable_test_5_backup" />
            <parameter name="archive_entity" value="ArchivableTest5MyBackup" />
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
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('ArchivableTest1Archive'));
    }

    public function testDoesNotCreateCustomArchiveTableIfExists()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('ArchivableTest2Archive'));
    }

    public function testCanCreateCustomArchiveTableName()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('MyOldArchivableTest3'));
        $entityMap = QuickBuilder::$configuration->getEntityMap('MyOldArchivableTest3');
        $this->assertEquals('my_old_archivable_test_3', $entityMap->getTableName());
    }

    public function testDoesNotCreateCustomArchiveTableIfArchiveClassIsSpecified()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('Propel\Tests\Generator\Behavior\Archivable\FooArchive'));
        $entityMap = QuickBuilder::$configuration->getEntityMap('Propel\Tests\Generator\Behavior\Archivable\FooArchive');
        $this->assertEquals('foo_archive', $entityMap->getTableName());
    }

    public function testCanCreateCustomArchiveTableNameAndPhpName()
    {
        $this->assertTrue(QuickBuilder::$configuration->hasEntityMap('ArchivableTest5MyBackup'));
        $entityMap = QuickBuilder::$configuration->getEntityMap('ArchivableTest5MyBackup');
        $this->assertEquals('archivable_test_5_backup', $entityMap->getTableName());
    }

    public function testCopiesColumnsToArchiveTable()
    {
        $entity = QuickBuilder::$configuration->getEntityMap('ArchivableTest1Archive');
        $this->assertTrue($entity->hasField('id'));
        $this->assertContains('id INTEGER NOT NULL,', self::$generatedSQL, 'copied columns are not autoincremented');
        $this->assertTrue($entity->hasField('title'));
        $this->assertTrue($entity->hasField('age'));
        $this->assertTrue($entity->hasField('fooId'));
    }

    public function testDoesNotCopyForeignKeys()
    {
        $entityMap = QuickBuilder::$configuration->getEntityMap('ArchivableTest1Archive');
        $this->assertEquals(array(), $entityMap->getRelations());
    }

    public function testCopiesIndices()
    {
        $expected = "CREATE INDEX archivable_test1archive_i_6c947f ON archivable_test1archive (title,age);";
        $this->assertContains($expected, self::$generatedSQL);
    }

    public function testCopiesUniquesToIndices()
    {
        $expected = "CREATE INDEX my_old_archivable_test_3_i_639136 ON my_old_archivable_test_3 (title);";
        $this->assertContains($expected, self::$generatedSQL);
    }

    public function testAddsArchivedAtColumnToArchiveTableByDefault()
    {
        $entityMap = QuickBuilder::$configuration->getEntityMap('ArchivableTest1Archive');
        $this->assertTrue($entityMap->hasField('archivedAt'));
    }

    public function testDoesNotAddArchivedAtColumnToArchiveTableIfSpecified()
    {
        $entityMap = QuickBuilder::$configuration->getEntityMap('MyOldArchivableTest3');
        $this->assertFalse($entityMap->hasField('archivedAt'));
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
