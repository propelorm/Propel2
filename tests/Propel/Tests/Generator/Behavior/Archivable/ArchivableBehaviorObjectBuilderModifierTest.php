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

use Base\BaseArchivableTest10Repository;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests for ArchivableBehavior class
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorObjectBuilderModifierTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $schema = <<<EOF
<database name="archivable_behavior_test_10" activeRecord="true">

    <entity name="ArchivableTest10">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="age" type="INTEGER" />
        <relation field="foo" target="ArchivableTest20" />
        <index>
            <index-field name="title" />
            <index-field name="age" />
        </index>
        <behavior name="archivable" />
    </entity>

    <entity name="ArchivableTest20">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <behavior name="archivable" />
    </entity>

    <entity name="ArchivableTest20Archive">
        <field name="id" required="true" primaryKey="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="ArchivableTest30">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="age" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="log_archived_at" value="false" />
            <parameter name="archive_entity" value="MyOldArchivableTest30" />
            <parameter name="archive_on_insert" value="true" />
            <parameter name="archive_on_update" value="true" />
            <parameter name="archive_on_delete" value="false" />
        </behavior>
    </entity>

    <entity name="ArchivableTest40">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="age" type="INTEGER" />
        <behavior name="archivable">
            <parameter name="archive_class" value="\Propel\Tests\Generator\Behavior\Archivable\FooArchive" />
        </behavior>
    </entity>

</database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    public function testHasGetArchiveMethod()
    {
        $this->assertTrue(method_exists('\Base\BaseArchivableTest10Repository', 'getArchive'));
    }


    public function testGetArchiveReturnsNullOnNewObjects()
    {
        $repo = QuickBuilder::$configuration->getRepository('ArchivableTest10');
        $a = new \ArchivableTest10();
        $this->assertNull($repo->getArchive($a));
    }

    public function testGetArchiveReturnsNullWhenNoArchiveIsFound()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $this->assertNull($a->getArchive());
    }

    public function testGetArchiveReturnsExistingArchive()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();

        $archive = new \ArchivableTest10Archive();
        $archive->setId($a->getId());
        $archive->setTitle('bar');
        $archive->save();
        $this->assertSame($archive, $a->getArchive());
    }

    public function testHasArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'archive'));
    }

    public function testArchiveCreatesACopyByDefault()
    {
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->archive();
        $archive = \ArchivableTest10ArchiveQuery::create()
            ->filterById($a->getId())
            ->findOne();

        $this->assertInstanceOf('\ArchivableTest10Archive', $archive);
        $this->assertEquals('foo', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    public function testArchiveUpdatesExistingArchive()
    {
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $b = new \ArchivableTest10Archive();
        $b->setId($a->getId());
        $b->setTitle('bar');
        $b->save();
        $a->archive();
        $this->assertEquals(1, \ArchivableTest10ArchiveQuery::create()->count());
        $this->assertEquals('foo', $b->getTitle());
    }

    public function testArchiveReturnsArchivedObject()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->save();
        $ret = $a->archive();
        $this->assertInstanceOf('\ArchivableTest10Archive', $ret);
        $this->assertEquals($a->getPrimaryKey(), $ret->getPrimaryKey());
        $this->assertEquals($a->getTitle(), $ret->getTitle());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testArchiveThrowsExceptionOnNewObjects()
    {
        $a = new \ArchivableTest10();
        $a->archive();
    }

    public function testHasRestoreFromArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'restoreFromArchive'));
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testRestoreFromArchiveThrowsExceptionOnUnarchivedObjects()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->restoreFromArchive();
    }

    public function testRestoreFromArchiveChangesStateToTheArchiveState()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $archive = new \ArchivableTest10Archive();
        $archive->setId($a->getId());
        $archive->setTitle('bar');
        $archive->setAge(15);
        $archive->save();
        $a->restoreFromArchive();
        $this->assertEquals('bar', $a->getTitle());
        $this->assertEquals(15, $a->getAge());
    }

    public function testHasPopulateFromArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'populateFromArchive'));
    }

    public function testPopulateFromArchive()
    {
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        \ArchivableTest10Query::create()->deleteAll();
        $archive = new \ArchivableTest10Archive();
        $archive->setId(123); // not autoincremented
        $archive->setTitle('foo');
        $archive->setAge(12);
        $archive->save();
        $a = new \ArchivableTest10();
        $a->populateFromArchive($archive);
        $this->assertEquals(123, $a->getId());
        $this->assertEquals('foo', $a->getTitle());
        $this->assertEquals(12, $a->getAge());
    }

    public function testInsertDoesNotCreateArchiveByDefault()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->save();
        $this->assertEquals(0, \ArchivableTest10ArchiveQuery::create()->count());
    }

    public function testInsertCreatesArchiveIfSpecified()
    {
        $a = new \ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        \MyOldArchivableTest30Query::create()->deleteAll();
        $a->save();
        $this->assertGreaterThan(0, $a->getId());

        $this->assertEquals(1, \MyOldArchivableTest30Query::create()->count());
        $archive = \MyOldArchivableTest30Query::create()
            ->filterById($a->getId())
            ->findOne();

        $this->assertInstanceOf('\MyOldArchivableTest30', $archive);
        $this->assertEquals('foo', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
        $this->assertEquals($a->getId(), $archive->getId());
    }

    public function testUpdateDoesNotCreateArchiveByDefault()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->setTitle('bar');
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->save();
        $this->assertEquals(0, \ArchivableTest10ArchiveQuery::create()->count());
    }

    public function testUpdateCreatesArchiveIfSpecified()
    {
        \MyOldArchivableTest30Query::create()->deleteAll();
        $a = new \ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();

        $a->setTitle('bar');
        $this->assertGreaterThan(0, $a->getId());
        $this->assertEquals(1, \MyOldArchivableTest30Query::create()->count());
        \MyOldArchivableTest30Query::create()->deleteAll();

        $a->save();

        $this->assertGreaterThan(0, $a->getId());
        $this->assertEquals(1, \MyOldArchivableTest30Query::create()->count());


        $archive = \MyOldArchivableTest30Query::create()
            ->filterById($a->getId())
            ->findOne();
        $this->assertInstanceOf('\MyOldArchivableTest30', $archive);
        $this->assertEquals('bar', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    public function testDeleteCreatesArchiveByDefault()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->delete();
        $this->assertEquals(1, \ArchivableTest10ArchiveQuery::create()->count());
        $archive = \ArchivableTest10ArchiveQuery::create()
            ->filterById($a->getId())
            ->findOne();
        $this->assertInstanceOf('\ArchivableTest10Archive', $archive);
        $this->assertEquals('foo', $archive->getTitle());
        $this->assertEquals(12, $archive->getAge());
    }

    public function testDeleteDoesNotCreateArchiveIfSpecified()
    {
        $a = new \ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        \MyOldArchivableTest30Query::create()->deleteAll();
        $a->delete();
        $this->assertEquals(0, \MyOldArchivableTest30Query::create()->count());
    }

    public function testHasSaveWithoutArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest30', 'saveWithoutArchive'));
    }

    public function testSaveWithoutArchiveDoesNotCreateArchiveOnInsert()
    {
        $a = new \ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        \MyOldArchivableTest30Query::create()->deleteAll();
        $a->saveWithoutArchive();
        $this->assertEquals(0, \MyOldArchivableTest30Query::create()->count());
    }

    public function testSaveWithoutArchiveDoesNotCreateArchiveOnUpdate()
    {
        $a = new \ArchivableTest30();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        $a->setTitle('bar');
        \MyOldArchivableTest30Query::create()->deleteAll();
        $this->assertEquals(0, \MyOldArchivableTest30Query::create()->count());
        $a->saveWithoutArchive();
        $this->assertEquals(0, \MyOldArchivableTest30Query::create()->count());
    }

    public function testHasDeleteWithoutArchiveMethod()
    {
        $this->assertTrue(method_exists('\ArchivableTest10', 'deleteWithoutArchive'));
    }

    public function testDeleteWithoutArchiveDoesNotCreateArchive()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->setAge(12);
        $a->save();
        \ArchivableTest10ArchiveQuery::create()->deleteAll();
        $a->deleteWithoutArchive();
        $this->assertEquals(0, \ArchivableTest10ArchiveQuery::create()->count());
    }

    public function testArchiveSetArchivedAtToTheCurrentTime()
    {
        $a = new \ArchivableTest10();
        $a->setTitle('foo');
        $a->save();
        $ret = $a->archive();
        // time without seconds
        $this->assertEquals(date('Y-m-d H:i'), $ret->getArchivedAt('Y-m-d H:i'));
    }

}
