<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

use ArchivableTest10;
use ArchivableTest10Archive;
use ArchivableTest10ArchiveQuery;
use ArchivableTest10Query;
use ArchivableTest30;
use ArchivableTest40;
use MyOldArchivableTest30Query;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Exception\PropelException;
use Propel\Tests\TestCase;

/**
 * Tests for ArchivableBehavior class
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorObjectBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\ArchivableTest10')) {
            $schema = <<<EOF
<database name="archivable_behavior_test_10">

    <table name="archivable_test_10">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="age" type="INTEGER"/>
        <column name="foo_id" type="INTEGER"/>
        <foreign-key foreignTable="archivable_test_20">
            <reference local="foo_id" foreign="id"/>
        </foreign-key>
        <index>
            <index-column name="title"/>
            <index-column name="age"/>
        </index>
        <behavior name="archivable"/>
    </table>

    <table name="archivable_test_20">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <behavior name="archivable"/>
    </table>

    <table name="archivable_test_20_archive">
        <column name="id" required="true" primaryKey="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="archivable_test_30">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="age" type="INTEGER"/>
        <column name="foo_id" type="INTEGER"/>
        <behavior name="archivable">
            <parameter name="log_archived_at" value="false"/>
            <parameter name="archive_table" value="my_old_archivable_test_30"/>
            <parameter name="archive_on_insert" value="true"/>
            <parameter name="archive_on_update" value="true"/>
            <parameter name="archive_on_delete" value="false"/>
        </behavior>
    </table>

    <table name="archivable_test_40">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="age" type="INTEGER"/>
        <behavior name="archivable">
            <parameter name="archive_class" value="\Propel\Tests\Generator\Behavior\Archivable\FooArchive"/>
        </behavior>
    </table>

</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testHasGetArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'getArchive'));
    }

    /**
     * @return void
     */
    public function testGetArchiveReturnsNullOnNewObjects()
    {
        $a = new ArchivableTest10();
        $this->assertNull($a->getArchive());
    }

    /**
     * @return void
     */
    public function testGetArchiveReturnsNullWhenNoArchiveIsFound()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $this->assertNull($a->getArchive());
    }

    /**
     * @return void
     */
    public function testGetArchiveReturnsExistingArchive()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $archive = new ArchivableTest10Archive();
        $archive->setId($a->getId());
        $archive->setTitle('bar');
        $archive->save();
        $this->assertSame($archive, $a->getArchive());
    }

    /**
     * @return void
     */
    public function testHasArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'archive'));
    }

    /**
     * @return void
     */
    public function testArchiveCreatesACopyByDefault()
    {
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->archive();
        $archive = ArchivableTest10ArchiveQuery::create()
            ->filterById($a->getId())
            ->findOne();
        $this->assertInstanceOf('\ArchivableTest10Archive', $archive);
        $this->assertEquals('foo', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    /**
     * @return void
     */
    public function testArchiveUpdatesExistingArchive()
    {
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $b = new ArchivableTest10Archive();
        $b->setId($a->getId());
        $b->setTitle('bar');
        $b->save();
        $a->archive();
        $this->assertEquals(1, ArchivableTest10ArchiveQuery::create()->count());
        $this->assertEquals('foo', $b->getTitle());
    }

    /**
     * @return void
     */
    public function testArchiveUsesArchiveClassIfSpecified()
    {
        $a = new ArchivableTest40();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->archive();
        $archive = FooArchiveCollection::getArchiveSingleton();
        $this->assertEquals($a->getId(), $archive->id);
        $this->assertEquals('foo', $archive->title);
        $this->assertEquals(12, $archive->age);
    }

    /**
     * @return void
     */
    public function testArchiveReturnsArchivedObject()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->save();
        $ret = $a->archive();
        $this->assertInstanceOf('\ArchivableTest10Archive', $ret);
        $this->assertEquals($a->getPrimaryKey(), $ret->getPrimaryKey());
        $this->assertEquals($a->getTitle(), $ret->getTitle());
    }

    /**
     * @return void
     */
    public function testArchiveThrowsExceptionOnNewObjects()
    {
        $this->expectException(PropelException::class);
        
        $a = new ArchivableTest10();
        $a->archive();
    }

    /**
     * @return void
     */
    public function testHasRestoreFromArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'restoreFromArchive'));
    }

    /**
     * @return void
     */
    public function testRestoreFromArchiveThrowsExceptionOnUnarchivedObjects()
    {
        $this->expectException(PropelException::class);
        
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->restoreFromArchive();
    }

    /**
     * @return void
     */
    public function testRestoreFromArchiveChangesStateToTheArchiveState()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $archive = new ArchivableTest10Archive();
        $archive->setId($a->getId());
        $archive->setTitle('bar');
        $archive->setAge(15);
        $archive->save();
        $a->restoreFromArchive();
        $this->assertEquals('bar', $a->getTitle());
        $this->assertEquals(15, $a->getAge());
    }

    /**
     * @return void
     */
    public function testHasPopulateFromArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'populateFromArchive'));
    }

    /**
     * @return void
     */
    public function testPopulateFromArchiveReturnsCurrentObject()
    {
        $archive = new ArchivableTest10Archive();
        $a = new ArchivableTest10();
        $ret = $a->populateFromArchive($archive);
        $this->assertSame($ret, $a);
    }

    /**
     * @return void
     */
    public function testPopulateFromArchive()
    {
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        ArchivableTest10Query::create()->deleteAllWithoutArchive();
        $archive = new ArchivableTest10Archive();
        $archive->setId(123); // not autoincremented
        $archive->setTitle('foo');
        $archive->setAge(12);
        $archive->save();
        $a = new ArchivableTest10();
        $a->populateFromArchive($archive);
        $this->assertNotEquals(123, $a->getId());
        $this->assertEquals('foo', $a->getTitle());
        $this->assertEquals(12, $a->getAge());
        $b = new ArchivableTest10();
        $b->populateFromArchive($archive, true);
        $this->assertEquals(123, $b->getId());
    }

    /**
     * @return void
     */
    public function testInsertDoesNotCreateArchiveByDefault()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->save();
        $this->assertEquals(0, ArchivableTest10ArchiveQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testInsertCreatesArchiveIfSpecified()
    {
        $a = new ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        MyOldArchivableTest30Query::create()->deleteAll();
        $a->save();
        $this->assertEquals(1, MyOldArchivableTest30Query::create()->count());
        $archive = MyOldArchivableTest30Query::create()
            ->filterById($a->getId())
            ->findOne();
        $this->assertInstanceOf('\MyOldArchivableTest30', $archive);
        $this->assertEquals('foo', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    /**
     * @return void
     */
    public function testUpdateDoesNotCreateArchiveByDefault()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->setTitle('bar');
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->save();
        $this->assertEquals(0, ArchivableTest10ArchiveQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testUpdateCreatesArchiveIfSpecified()
    {
        $a = new ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->setTitle('bar');
        MyOldArchivableTest30Query::create()->deleteAll();
        $a->save();
        $this->assertEquals(1, MyOldArchivableTest30Query::create()->count());
        $archive = MyOldArchivableTest30Query::create()
            ->filterById($a->getId())
            ->findOne();
        $this->assertInstanceOf('\MyOldArchivableTest30', $archive);
        $this->assertEquals('bar', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    /**
     * @return void
     */
    public function testDeleteCreatesArchiveByDefault()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->delete();
        $this->assertEquals(1, ArchivableTest10ArchiveQuery::create()->count());
        $archive = ArchivableTest10ArchiveQuery::create()
            ->filterById($a->getId())
            ->findOne();
        $this->assertInstanceOf('\ArchivableTest10Archive', $archive);
        $this->assertEquals('foo', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    /**
     * @return void
     */
    public function testDeleteDoesNotCreateArchiveIfSpecified()
    {
        $a = new ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        MyOldArchivableTest30Query::create()->deleteAll();
        $a->delete();
        $this->assertEquals(0, MyOldArchivableTest30Query::create()->count());
    }

    /**
     * @return void
     */
    public function testHasSaveWithoutArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest30', 'saveWithoutArchive'));
    }

    /**
     * @return void
     */
    public function testSaveWithoutArchiveDoesNotCreateArchiveOnInsert()
    {
        $a = new ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        MyOldArchivableTest30Query::create()->deleteAll();
        $a->saveWithoutArchive();
        $this->assertEquals(0, MyOldArchivableTest30Query::create()->count());
    }

    /**
     * @return void
     */
    public function testSaveWithoutArchiveDoesNotCreateArchiveOnUpdate()
    {
        $a = new ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->setTitle('bar');
        MyOldArchivableTest30Query::create()->deleteAll();
        $this->assertEquals(0, MyOldArchivableTest30Query::create()->count());
        $a->saveWithoutArchive();
        $this->assertEquals(0, MyOldArchivableTest30Query::create()->count());
    }

    /**
     * @return void
     */
    public function testHasDeleteWithoutArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'deleteWithoutArchive'));
    }

    /**
     * @return void
     */
    public function testDeleteWithoutArchiveDoesNotCreateArchive()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->deleteWithoutArchive();
        $this->assertEquals(0, ArchivableTest10ArchiveQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testArchiveSetArchivedAtToTheCurrentTime()
    {
        $a = new ArchivableTest10();
        $a->setTitle('foo');
        $a->save();
        $ret = $a->archive();
        // time without seconds
        $this->assertEquals(date('Y-m-d H:i'), $ret->getArchivedAt('Y-m-d H:i'));
    }
}
