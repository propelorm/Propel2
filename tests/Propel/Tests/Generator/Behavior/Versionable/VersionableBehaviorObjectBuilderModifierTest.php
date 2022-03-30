<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Versionable;

use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\PropelException;
use VersionableBehaviorTest1;
use VersionableBehaviorTest10;
use VersionableBehaviorTest11;
use VersionableBehaviorTest12;
use VersionableBehaviorTest1Query;
use VersionableBehaviorTest1Version;
use VersionableBehaviorTest1VersionQuery;
use VersionableBehaviorTest2Query;
use VersionableBehaviorTest3;
use VersionableBehaviorTest3Query;
use VersionableBehaviorTest3VersionQuery;
use VersionableBehaviorTest4;
use VersionableBehaviorTest4VersionQuery;
use VersionableBehaviorTest5;
use VersionableBehaviorTest6;
use VersionableBehaviorTest7;
use VersionableBehaviorTest8Foo;
use Versionablebehaviortest8Version;
use VersionableBehaviorTestCustomField;
use VersionableBehaviorTestCustomFieldKey;
use VersionableBehaviorTestCustomFieldKeyQuery;
use VersionableBehaviorTestCustomFieldKeyVersionQuery;
use VersionableBehaviorTestCustomFieldQuery;
use VersionableBehaviorTestCustomFieldVersionQuery;
use VersionableBehaviorTestOneToOne;
use VersionableBehaviorTestOneToOneKey;
use VersionableBehaviorTestOneToOneKeyQuery;
use VersionableBehaviorTestOneToOneKeyVersionQuery;
use VersionableBehaviorTestOneToOneQuery;
use VersionableBehaviorTestOneToOneVersionQuery;

/**
 * Tests for VersionableBehavior class
 *
 * @author François Zaninotto
 */
class VersionableBehaviorObjectBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_1">
    <table name="versionable_behavior_test_1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
    <table name="versionable_behavior_test_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable">
            <parameter name="version_column" value="foo_ver"/>
        </behavior>
    </table>
    <table name="versionable_behavior_test_3">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable">
            <parameter name="version_table" value="foo_ver"/>
        </behavior>
    </table>
    <table name="versionable_behavior_test_4">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable">
            <parameter name="log_created_at" value="true"/>
            <parameter name="log_created_by" value="true"/>
            <parameter name="log_comment" value="true"/>
        </behavior>
    </table>
    <table name="versionable_behavior_test_5">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="VARCHAR" size="100"/>
        <column name="foreign_id" type="INTEGER"/>
        <foreign-key foreignTable="versionable_behavior_test_4">
            <reference local="foreign_id" foreign="id"/>
        </foreign-key>
        <behavior name="versionable"/>
    </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema);

        $schema2 = <<<EOF
<database name="versionable_behavior_test_2" defaultPhpNamingMethod="nochange">
    <table name="VersionableBehaviorTest6">
        <column name="Id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="FooBar" type="VARCHAR" size="100"/>
        <behavior name="versionable">
            <parameter name="log_created_at" value="true"/>
            <parameter name="log_created_by" value="true"/>
            <parameter name="log_comment" value="true"/>
        </behavior>
    </table>

    <table name="VersionableBehaviorTest7">
        <column name="Id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="FooBar" type="VARCHAR" size="100"/>

        <column name="Style" type="ENUM" valueSet="novel, essay, poetry"/>
        <column name="Style2" type="SET" valueSet="novel, essay, poetry"/>

        <behavior name="versionable">
            <parameter name="log_created_at" value="true"/>
            <parameter name="log_created_by" value="true"/>
            <parameter name="log_comment" value="true"/>

            <parameter name="version_created_by_column" value="VersionCreatedBy"/>
            <parameter name="version_created_at_column" value="VersionCreatedAt"/>
            <parameter name="version_comment_column" value="MyComment"/>
        </behavior>
    </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema2);

        $schema3 = <<<EOF
<database name="versionable_behavior_test_3" defaultPhpNamingMethod="nochange">
    <table name="VersionableBehaviorTest8">
        <column name="Id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="FooBar" type="VARCHAR" size="100"/>
        <column name="class_key" type="INTEGER" required="true" default="1" inheritance="single">
            <inheritance key="1" class="VersionableBehaviorTest8"/>
            <inheritance key="2" class="VersionableBehaviorTest8Foo" extends="VersionableBehaviorTest8"/>
            <inheritance key="3" class="VersionableBehaviorTest8Bar" extends="VersionableBehaviorTest8Foo"/>
        </column>

        <behavior name="versionable"/>
    </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema3);

        $schema4 = <<<EOF
<database name="versionable_behavior_test_4">
    <table name="VersionableBehaviorTest10">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>

        <behavior name="versionable"/>
    </table>

    <table name="VersionableBehaviorTest11">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="VARCHAR" size="100"/>
    </table>

    <table name="VersionableBehaviorTest12">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar_id" type="INTEGER"/>
        <column name="foo_id" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="25"/>

        <behavior name="versionable"/>

        <foreign-key foreignTable="VersionableBehaviorTest10">
            <reference local="bar_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="VersionableBehaviorTest11">
            <reference local="foo_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema4);

    /**
     *  Schema to test relation 1:1 versionable
     */
        $schema5 = <<<XML
<database name="versionable_behavior_test_one_to_one_database">
    <table name="versionable_behavior_test_one_to_one">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="bar" type="varchar" size="32"/>
        <behavior name="versionable"/>
    </table>

    <table name="versionable_behavior_test_one_to_one_key">
        <column name="foo_id" type="integer" primaryKey="true"/>
        <column name="bar" type="varchar" size="32"/>
        <foreign-key foreignTable="versionable_behavior_test_one_to_one">
            <reference local="foo_id" foreign="id"/>
        </foreign-key>
        <behavior name="versionable"/>
    </table>
</database>
XML;
        QuickBuilder::buildSchema($schema5);

        $schemaCustomName = <<<EOF
<database name="versionable_behavior_test_custom_field_database">

    <table name="versionable_behavior_test_custom_field">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>

        <behavior name="versionable">
            <parameter name="version_column" value="CustomVersion"/>
      	</behavior>
    </table>

    <table name="versionable_behavior_test_custom_field_key">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar_id" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="25"/>

        <behavior name="versionable">
            <parameter name="version_column" value="CustomVersion"/>
      	</behavior>

        <foreign-key foreignTable="versionable_behavior_test_custom_field">
            <reference local="bar_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
EOF;
        QuickBuilder::buildSchema($schemaCustomName);
    }

    /**
     * @group testGetVersionExists
     *
     * @return void
     */
    public function testGetVersionExists()
    {
        $this->assertTrue(method_exists('VersionableBehaviorTest1', 'getVersion'));
        $this->assertTrue(method_exists('VersionableBehaviorTest2', 'getVersion'));
        $this->assertTrue(method_exists('VersionableBehaviorTestCustomField', 'getCustomVersion'));
    }

    /**
     * @return void
     */
    public function testSetVersionExists()
    {
        $this->assertTrue(method_exists('VersionableBehaviorTest1', 'setVersion'));
        $this->assertTrue(method_exists('VersionableBehaviorTest2', 'setVersion'));
        $this->assertTrue(method_exists('VersionableBehaviorTestCustomField', 'setCustomVersion'));
    }

    /**
     * @return void
     */
    public function testMethodsExistsNoChangeNaming()
    {
        $this->assertTrue(method_exists('VersionableBehaviorTest6', 'setFooBar'));
        $this->assertTrue(method_exists('VersionableBehaviorTest6', 'setversion_created_at'));
        $this->assertTrue(method_exists('VersionableBehaviorTest6', 'setversion_created_by'));
        $this->assertTrue(method_exists('VersionableBehaviorTest6', 'setversion_comment'));

        $this->assertTrue(method_exists('VersionableBehaviorTest7', 'setFooBar'));
        $this->assertTrue(method_exists('VersionableBehaviorTest7', 'setVersionCreatedAt'));
        $this->assertTrue(method_exists('VersionableBehaviorTest7', 'setVersionCreatedBy'));
        $this->assertTrue(method_exists('VersionableBehaviorTest7', 'setMyComment'));
    }

    public function providerForNewActiveRecordTests()
    {
        return [
            ['\VersionableBehaviorTest1'],
            ['VersionableBehaviorTest2'],
            ['VersionableBehaviorTestCustomField'],
            ['VersionableBehaviorTestOneToOne'],
        ];
    }

    /**
     * @dataProvider providerForNewActiveRecordTests
     *
     * @return void
     */
    public function testVersionGetterAndSetter($class)
    {
        $o = new $class();
        $o->setVersion(1234);
        $this->assertEquals(1234, $o->getVersion());
    }

    /**
     * @dataProvider providerForNewActiveRecordTests
     *
     * @return void
     */
    public function testVersionDefaultValue($class)
    {
        $o = new $class();
        $this->assertEquals(0, $o->getVersion());
    }

    /**
     * @dataProvider providerForNewActiveRecordTests
     *
     * @return void
     */
    public function testVersionValueInitializesOnInsert($class)
    {
        $o = new $class();
        $o->save();
        $this->assertEquals(1, $o->getVersion());
    }

    /**
     * @dataProvider providerForNewActiveRecordTests
     *
     * @return void
     */
    public function testVersionValueIncrementsOnUpdate($class)
    {
        $o = new $class();
        $o->save();
        $this->assertEquals(1, $o->getVersion());
        $o->setBar(12);
        $o->save();
        $this->assertEquals(2, $o->getVersion());
        $o->setBar(13);
        $o->save();
        $this->assertEquals(3, $o->getVersion());
        $o->setBar(12);
        $o->save();
        $this->assertEquals(4, $o->getVersion());
    }

    /**
     * @return void
     */
    public function testVersionValueIncrementsOnDeleteManyToMany()
    {
        $bar = new VersionableBehaviorTest10();
        $bar->setBar(42);
        $bar->save();

        $foo = new VersionableBehaviorTest11();
        $foo->setFoo('Marvin');
        $foo->save();

        $baz = new VersionableBehaviorTest12();
        $baz->setVersionablebehaviortest11($foo);
        $baz->setBaz('So long and thanks for all the fish');

        $bar->addVersionablebehaviortest12($baz);
        $bar->save();

        $this->assertEquals(1, $baz->getVersion());
        $this->assertEquals(2, $bar->getVersion());

        $baz->delete();
        $bar->save();

        $this->assertEquals(3, $bar->getVersion());
    }

    /**
     * @dataProvider providerForNewActiveRecordTests
     *
     * @return void
     */
    public function testVersionDoesNotIncrementOnUpdateWithNoChange($class)
    {
        $o = new $class();
        $o->setBar(12);
        $o->save();
        $this->assertEquals(1, $o->getVersion());
        $o->setBar(12);
        $o->save();
        $this->assertEquals(1, $o->getVersion());
    }

    /**
     * @dataProvider providerForNewActiveRecordTests
     *
     * @return void
     */
    public function testVersionDoesNotIncrementWhenVersioningIsDisabled($class)
    {
        $o = new $class();

        VersionableBehaviorTest1Query::disableVersioning();
        VersionableBehaviorTest2Query::disableVersioning();
        VersionableBehaviorTestCustomFieldQuery::disableVersioning();
        VersionableBehaviorTestOneToOneQuery::disableVersioning();
        $o->setBar(12);
        $o->save();
        $this->assertEquals(0, $o->getVersion());
        $o->setBar(13);
        $o->save();
        $this->assertEquals(0, $o->getVersion());
        VersionableBehaviorTest1Query::enableVersioning();
        VersionableBehaviorTest1Query::enableVersioning();
        VersionableBehaviorTestCustomFieldQuery::enableVersioning();
        VersionableBehaviorTestOneToOneQuery::enableVersioning();
    }

    /**
     * @return void
     */
    public function testNewVersionCreatesRecordInVersionTable()
    {
        VersionableBehaviorTest1Query::create()->deleteAll();
        VersionableBehaviorTest1VersionQuery::create()->deleteAll();
        $o = new VersionableBehaviorTest1();
        $o->save();
        $versions = VersionableBehaviorTest1VersionQuery::create()->find();
        $this->assertEquals(1, $versions->count());
        $this->assertEquals($o, $versions[0]->getVersionableBehaviorTest1());
        $o->save();
        $versions = VersionableBehaviorTest1VersionQuery::create()->find();
        $this->assertEquals(1, $versions->count());
        $o->setBar(123);
        $o->save();
        $versions = VersionableBehaviorTest1VersionQuery::create()->orderByVersion()->find();
        $this->assertEquals(2, $versions->count());
        $this->assertEquals($o->getId(), $versions[0]->getId());
        $this->assertNull($versions[0]->getBar());
        $this->assertEquals($o->getId(), $versions[1]->getId());
        $this->assertEquals(123, $versions[1]->getBar());
    }

    /**
     * @return void
     */
    public function testNewVersionCreatesRecordInVersionTableWithCustomName()
    {
        VersionableBehaviorTest3Query::create()->deleteAll();
        VersionableBehaviorTest3VersionQuery::create()->deleteAll();
        $o = new VersionableBehaviorTest3();
        $o->save();
        $versions = VersionableBehaviorTest3VersionQuery::create()->find();
        $this->assertEquals(1, $versions->count());
        $this->assertEquals($o, $versions[0]->getVersionableBehaviorTest3());
        $o->save();
        $versions = VersionableBehaviorTest3VersionQuery::create()->find();
        $this->assertEquals(1, $versions->count());
        $o->setBar(123);
        $o->save();
        $versions = VersionableBehaviorTest3VersionQuery::create()->orderByVersion()->find();
        $this->assertEquals(2, $versions->count());
        $this->assertEquals($o->getId(), $versions[0]->getId());
        $this->assertNull($versions[0]->getBar());
        $this->assertEquals($o->getId(), $versions[1]->getId());
        $this->assertEquals(123, $versions[1]->getBar());
    }

    /**
     * @return void
     */
    public function testNewVersionCreatesRecordInVersionTableWithFieldCustomName()
    {
        VersionableBehaviorTestCustomFieldQuery::create()->deleteAll();
        VersionableBehaviorTestCustomFieldVersionQuery::create()->deleteAll();
        VersionableBehaviorTestCustomFieldKeyQuery::create()->deleteAll();
        VersionableBehaviorTestCustomFieldKeyVersionQuery::create()->deleteAll();

        $o = new VersionableBehaviorTestCustomField();
        $o->setBar(150);
        $o->save();

        $k = new VersionableBehaviorTestCustomFieldKey();
        $k->setVersionableBehaviorTestCustomField($o);
        $k->save();

        $versions = VersionableBehaviorTestCustomFieldVersionQuery::create()->find();
        $versionsKeys = VersionableBehaviorTestCustomFieldKeyVersionQuery::create()->find();

        $this->assertEquals(1, $versions->count());
        $this->assertEquals(1, $versionsKeys->count());
        $this->assertEquals($o, $versions[0]->getVersionableBehaviorTestCustomField());
        $this->assertEquals($k, $versionsKeys[0]->getVersionableBehaviorTestCustomFieldKey());

        $o->setBar(150);
        $o->save();

        $versions = VersionableBehaviorTestCustomFieldVersionQuery::create()->find();
        $this->assertEquals(1, $versions->count());
        $o->setBar(123);
        $o->save();

        $versions = VersionableBehaviorTestCustomFieldVersionQuery::create()->orderByCustomVersion()->find();

        $this->assertEquals(2, $versions->count());
        $this->assertEquals($o->getId(), $versions[0]->getId());
        $this->assertNotNull($versions[0]->getBar());
        $this->assertEquals($o->getId(), $versions[1]->getId());
        $this->assertEquals(123, $versions[1]->getBar());
        $this->assertEquals(2, $o->getVersion());

        $o->toVersion(1);

        $this->assertEquals(1, $o->getVersion());
        $this->assertEquals($o->getId(), $versions[0]->getId());
    }

    /**
     * @return void
     */
    public function testNewVersionDoesNotCreateRecordInVersionTableWhenVersioningIsDisabled()
    {
        VersionableBehaviorTest1Query::create()->deleteAll();
        VersionableBehaviorTest1VersionQuery::create()->deleteAll();
        VersionableBehaviorTest1Query::disableVersioning();
        $o = new VersionableBehaviorTest1();
        $o->save();
        $versions = VersionableBehaviorTest1VersionQuery::create()->find();
        $this->assertEquals(0, $versions->count());
        VersionableBehaviorTest1Query::enableVersioning();
    }

    /**
     * @return void
     */
    public function testDeleteObjectDeletesRecordInVersionTable()
    {
        VersionableBehaviorTest1Query::create()->deleteAll();
        VersionableBehaviorTest1VersionQuery::create()->deleteAll();
        $o = new VersionableBehaviorTest1();
        $o->save();
        $o->setBar(123);
        $o->save();
        $nbVersions = VersionableBehaviorTest1VersionQuery::create()->count();
        $this->assertEquals(2, $nbVersions);
        $o->delete();
        $nbVersions = VersionableBehaviorTest1VersionQuery::create()->count();
        $this->assertEquals(0, $nbVersions);
    }

    /**
     * @return void
     */
    public function testDeleteObjectDeletesRecordInVersionTableWithCustomName()
    {
        VersionableBehaviorTest3Query::create()->deleteAll();
        VersionableBehaviorTest3VersionQuery::create()->deleteAll();
        $o = new VersionableBehaviorTest3();
        $o->save();
        $o->setBar(123);
        $o->save();
        $nbVersions = VersionableBehaviorTest3VersionQuery::create()->count();
        $this->assertEquals(2, $nbVersions);
        $o->delete();
        $nbVersions = VersionableBehaviorTest3VersionQuery::create()->count();
        $this->assertEquals(0, $nbVersions);
    }

    /**
     * @return void
     */
    public function testToVersion()
    {
        $o = new VersionableBehaviorTest1();
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $o->toVersion(1);
        $this->assertEquals(123, $o->getBar());
        $o->toVersion(2);
        $this->assertEquals(456, $o->getBar());
    }

    /**
     * @return void
     */
    public function testToVersionAllowsFurtherSave()
    {
        $o = new VersionableBehaviorTest1();
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $o->toVersion(1);
        $this->assertTrue($o->isModified());
        $o->save();
        $this->assertEquals(3, $o->getVersion());
    }

    /**
     * @return void
     */
    public function testToVersionThrowsExceptionOnIncorrectVersion()
    {
        $this->expectException(PropelException::class);

        $o = new VersionableBehaviorTest1();
        $o->setBar(123); // version 1
        $o->save();
        $o->toVersion(2);
    }

    /**
     * @return void
     */
    public function testToVersionPreservesVersionedFkObjects()
    {
        $a = new VersionableBehaviorTest4();
        $a->setBar(123); // a1
        $b = new VersionableBehaviorTest5();
        $b->setFoo('Hello');
        $b->setVersionableBehaviorTest4($a);
        $b->save(); //b1
        $a->setBar(456); //a2
        $b->save(); // b2
        $b->setFoo('World');
        $b->save(); // b3
        $b->toVersion(2);
        $this->assertEquals($b->getVersion(), 2);
        $this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
        $b->toVersion(1);
        $this->assertEquals($b->getVersion(), 1);
        $this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 1);
        $b->toVersion(3);
        $this->assertEquals($b->getVersion(), 3);
        $this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
    }

    /**
     * @return void
     */
    public function testToVersionPreservesVersionedReferrerObjects()
    {
        $b1 = new VersionableBehaviorTest5();
        $b1->setFoo('Hello');
        $b2 = new VersionableBehaviorTest5();
        $b2->setFoo('World');
        $a = new VersionableBehaviorTest4();
        $a->setBar(123); // a1
        $a->addVersionableBehaviorTest5($b1);
        $a->addVersionableBehaviorTest5($b2);
        $a->save(); //b1
        $this->assertEquals(1, $a->getVersion());
        $bs = $a->getVersionableBehaviorTest5s();
        $this->assertEquals(1, $bs[0]->getVersion());
        $this->assertEquals(1, $bs[1]->getVersion());
        $b1->setFoo('Heloo');
        $a->save();
        $this->assertEquals(2, $a->getVersion());
        $bs = $a->getVersionableBehaviorTest5s();
        $this->assertEquals(2, $bs[0]->getVersion());
        $this->assertEquals(1, $bs[1]->getVersion());
        $b3 = new VersionableBehaviorTest5();
        $b3->setFoo('Yep');
        $a->clearVersionableBehaviorTest5s();
        $a->addVersionableBehaviorTest5($b3);
        $a->save();
        $a->clearVersionableBehaviorTest5s();
        $this->assertEquals(3, $a->getVersion());
        $bs = $a->getVersionableBehaviorTest5s();
        $this->assertEquals(2, $bs[0]->getVersion());
        $this->assertEquals(1, $bs[1]->getVersion());
        $this->assertEquals(1, $bs[2]->getVersion());

        $a->toVersion(1);
        $bs = $a->getVersionableBehaviorTest5s();
        $this->assertEquals(1, $bs[0]->getVersion());
        $this->assertEquals(1, $bs[1]->getVersion());

        $bs[0]->toVersion(2);
        $a = $bs[0]->getVersionableBehaviorTest4();
        $this->assertEquals(2, $a->getVersion());
    }

    /**
     * @return void
     */
    public function testGetLastVersionNumber()
    {
        $o = new VersionableBehaviorTest1();
        $this->assertEquals(0, $o->getLastVersionNumber());
        $o->setBar(123); // version 1
        $o->save();
        $this->assertEquals(1, $o->getLastVersionNumber());
        $o->setBar(456); // version 2
        $o->save();
        $this->assertEquals(2, $o->getLastVersionNumber());
        $o->toVersion(1);
        $o->save();
        $this->assertEquals(3, $o->getLastVersionNumber());
    }

    /**
     * @return void
     */
    public function testIsLastVersion()
    {
        $o = new VersionableBehaviorTest1();
        $this->assertTrue($o->isLastVersion());
        $o->setBar(123); // version 1
        $o->save();
        $this->assertTrue($o->isLastVersion());
        $o->setBar(456); // version 2
        $o->save();
        $this->assertTrue($o->isLastVersion());
        $o->toVersion(1);
        $this->assertFalse($o->isLastVersion());
        $o->save();
        $this->assertTrue($o->isLastVersion());
    }

    /**
     * @return void
     */
    public function testIsVersioningNecessary()
    {
        $o = new VersionableBehaviorTest1();
        $this->assertTrue($o->isVersioningNecessary());
        $o->save();
        $this->assertFalse($o->isVersioningNecessary());
        $o->setBar(123);
        $this->assertTrue($o->isVersioningNecessary());
        $o->save();
        $this->assertFalse($o->isVersioningNecessary());

        VersionableBehaviorTest1Query::disableVersioning();
        $o = new VersionableBehaviorTest1();
        $this->assertFalse($o->isVersioningNecessary());
        $o->save();
        $this->assertFalse($o->isVersioningNecessary());
        $o->setBar(123);
        $this->assertFalse($o->isVersioningNecessary());
        $o->save();
        $this->assertFalse($o->isVersioningNecessary());
        VersionableBehaviorTest1Query::enableVersioning();

        $b1 = new VersionableBehaviorTest5();
        $b1->setFoo('Hello');
        $b2 = new VersionableBehaviorTest5();
        $b2->setFoo('World');
        $a = new VersionableBehaviorTest4();
        $a->setBar(123); // a1
        $this->assertTrue($a->isVersioningNecessary());
        $a->save();
        $this->assertFalse($a->isVersioningNecessary());
        $a->addVersionableBehaviorTest5($b1);
        $this->assertTrue($a->isVersioningNecessary());
        $a->save();
        $this->assertFalse($a->isVersioningNecessary());
        $a->addVersionableBehaviorTest5($b2);
        $this->assertTrue($a->isVersioningNecessary());
        $a->save();
        $this->assertFalse($a->isVersioningNecessary());
        $b2->setFoo('World !');
        $this->assertTrue($b2->isVersioningNecessary());
        $this->assertTrue($a->isVersioningNecessary());
        $a->save();
        $this->assertFalse($b2->isVersioningNecessary());
        $this->assertFalse($a->isVersioningNecessary());
    }

    /**
     * @return void
     */
    public function testIsVersioningNecessaryWithNullFk()
    {
        // the purpose of this tests is to highlight a bug with FK
        // and isVersioningNecessary()
        $b1 = new VersionableBehaviorTest5();
        $b1->setNew(false);

        // this time, the object isn't modified, so the
        // isVersioningNecessary() method is called on FK objects...
        // which can be null.
        $b1->isVersioningNecessary();

        $this->assertTrue(true, 'getting here means that nothing went wrong');
    }

    /**
     * @return void
     */
    public function testAddVersionNewObject()
    {
        VersionableBehaviorTest1Query::disableVersioning();
        VersionableBehaviorTest1Query::create()->deleteAll();
        VersionableBehaviorTest1VersionQuery::create()->deleteAll();
        $o = new VersionableBehaviorTest1();
        $o->addVersion();
        $o->save();
        $versions = VersionableBehaviorTest1VersionQuery::create()->find();
        $this->assertEquals(1, $versions->count());
        $this->assertEquals($o, $versions[0]->getVersionableBehaviorTest1());
        VersionableBehaviorTest1Query::enableVersioning();
    }

    /**
     * @return void
     */
    public function testVersionCreatedAt()
    {
        $o = new VersionableBehaviorTest4();
        $t = time();
        $o->save();
        $version = VersionableBehaviorTest4VersionQuery::create()
            ->filterByVersionableBehaviorTest4($o)
            ->findOne();
        $this->assertEquals($t, $version->getVersionCreatedAt('U'));

        $o = new VersionableBehaviorTest4();
        $inThePast = time() - 123456;
        $o->setVersionCreatedAt($inThePast);
        $o->save();
        $this->assertEquals($inThePast, $o->getVersionCreatedAt('U'));
        $version = VersionableBehaviorTest4VersionQuery::create()
            ->filterByVersionableBehaviorTest4($o)
            ->findOne();
        $this->assertEquals($o->getVersionCreatedAt(), $version->getVersionCreatedAt());
    }

    /**
     * @return void
     */
    public function testVersionCreatedBy()
    {
        $o = new VersionableBehaviorTest4();
        $o->setVersionCreatedBy('me me me');
        $o->save();
        $version = VersionableBehaviorTest4VersionQuery::create()
            ->filterByVersionableBehaviorTest4($o)
            ->findOne();
        $this->assertEquals('me me me', $version->getVersionCreatedBy());
    }

    /**
     * @return void
     */
    public function testSaveAndModifyWithNoChangeSchema()
    {
        $o = new VersionableBehaviorTest7();
        //$o->setVersionCreatedBy('You and I');
        $o->save();
        $this->assertEquals(1, $o->getVersion());
        $o->setFooBar('Something');
        $o->save();
        $this->assertEquals(2, $o->getVersion());

        $o = new VersionableBehaviorTest6();
        //$o->setVersionCreatedBy('You and I');
        $o->save();
        $this->assertEquals(1, $o->getVersion());
        $o->setFooBar('Something');
        $o->save();
        $this->assertEquals(2, $o->getVersion());
    }

    /**
     * @return void
     */
    public function testVersionComment()
    {
        $o = new VersionableBehaviorTest4();

        $o->setVersionComment('Because you deserve it');
        $o->save();
        $version = VersionableBehaviorTest4VersionQuery::create()
            ->filterByVersionableBehaviorTest4($o)
            ->findOne();
        $this->assertEquals('Because you deserve it', $version->getVersionComment());
    }

    /**
     * @return void
     */
    public function testToVersionWorksWithComments()
    {
        $o = new VersionableBehaviorTest4();
        $o->setVersionComment('Because you deserve it');
        $o->setBar(123); // version 1
        $o->save();
        $o->setVersionComment('Unless I change my mind');
        $o->setBar(456); // version 2
        $o->save();
        $o->toVersion(1);
        $this->assertEquals('Because you deserve it', $o->getVersionComment());
        $o->toVersion(2);
        $this->assertEquals('Unless I change my mind', $o->getVersionComment());
    }

    /**
     * @return void
     */
    public function testGetOneVersion()
    {
        $o = new VersionableBehaviorTest1();
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $version = $o->getOneVersion(1);
        $this->assertTrue($version instanceof VersionableBehaviorTest1Version);
        $this->assertEquals(1, $version->getVersion());
        $this->assertEquals(123, $version->getBar());
        $version = $o->getOneVersion(2);
        $this->assertEquals(2, $version->getVersion());
        $this->assertEquals(456, $version->getBar());
    }

    /**
     * @return void
     */
    public function testGetAllVersions()
    {
        $o = new VersionableBehaviorTest1();
        $versions = $o->getAllVersions();
        $this->assertTrue($versions->isEmpty());
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $versions = $o->getAllVersions();
        $this->assertTrue($versions instanceof ObjectCollection);
        $this->assertEquals(2, $versions->count());
        $this->assertEquals(1, $versions[0]->getVersion());
        $this->assertEquals(123, $versions[0]->getBar());
        $this->assertEquals(2, $versions[1]->getVersion());
        $this->assertEquals(456, $versions[1]->getBar());
    }

    /**
     * @return void
     */
    public function testGetLastVersions()
    {
        $o = new VersionableBehaviorTest1();
        $versions = $o->getAllVersions();
        $this->assertTrue($versions->isEmpty());
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $o->setBar(789); // version 3
        $o->save();
        $o->setBar(101112); // version 4
        $o->save();

        $versions = $o->getLastVersions();
        $this->assertTrue($versions instanceof ObjectCollection);
        $this->assertEquals(4, $versions->count());
        $this->assertEquals(4, $versions[0]->getVersion());
        $this->assertEquals(101112, $versions[0]->getBar());
        $this->assertEquals(3, $versions[1]->getVersion());
        $this->assertEquals(789, $versions[1]->getBar());
        $this->assertEquals(2, $versions[2]->getVersion());
        $this->assertEquals(456, $versions[2]->getBar());
        $this->assertEquals(1, $versions[3]->getVersion());
        $this->assertEquals(123, $versions[3]->getBar());

        $versions = $o->getLastVersions(2);
        $this->assertTrue($versions instanceof ObjectCollection);
        $this->assertEquals(2, $versions->count());
        $this->assertEquals(4, $versions[0]->getVersion());
        $this->assertEquals(101112, $versions[0]->getBar());
        $this->assertEquals(3, $versions[1]->getVersion());
        $this->assertEquals(789, $versions[1]->getBar());
    }

    /**
     * @return void
     */
    public function testCompareVersion()
    {
        $o = new VersionableBehaviorTest4();
        $versions = $o->getAllVersions();
        $this->assertTrue($versions->isEmpty());
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $o->setBar(789); // version 3
        $o->setVersionComment('Foo');
        $o->save();
        $diff = $o->compareVersion(3); // $o is in version 3
        $expected = [];
        $this->assertEquals($expected, $diff);
        $diff = $o->compareVersion(2);
        $expected = [
            'Bar' => [2 => 456, 3 => 789],
        ];
        $this->assertEquals($expected, $diff);

        $diff = $o->compareVersion(1);
        $expected = [
            'Bar' => [1 => 123, 3 => 789],
        ];
        $this->assertEquals($expected, $diff);
    }

    /**
     * @return void
     */
    public function testCompareVersions()
    {
        $o = new VersionableBehaviorTest4();
        $versions = $o->getAllVersions();
        $this->assertTrue($versions->isEmpty());
        $o->setBar(123); // version 1
        $o->save();
        $o->setBar(456); // version 2
        $o->save();
        $o->setBar(789); // version 3
        $o->setVersionComment('Foo');
        $o->save();
        $diff = $o->compareVersions(1, 3);
        $expected = [
            'Bar' => [1 => 123, 3 => 789],
        ];
        $this->assertEquals($expected, $diff);
        $diff = $o->compareVersions(1, 3, 'versions');
        $expected = [
            1 => ['Bar' => 123],
            3 => ['Bar' => 789],
        ];
        $this->assertEquals($expected, $diff);
    }

    /**
     * @return void
     */
    public function testForeignKeyVersion()
    {
        $a = new VersionableBehaviorTest4();
        $a->setBar(123); // a1
        $b = new VersionableBehaviorTest5();
        $b->setFoo('Hello');
        $b->setVersionableBehaviorTest4($a);
        $b->save(); //b1
        $this->assertEquals($b->getVersion(), 1);
        $this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 1);
        $a->setBar(456); //a2
        $b->save(); // b2
        $this->assertEquals($b->getVersion(), 2);
        $this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
        $b->setFoo('World');
        $b->save(); // b3
        $this->assertEquals($b->getVersion(), 3);
        $this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
    }

    /**
     * @return void
     */
    public function testReferrerVersion()
    {
        $b1 = new VersionableBehaviorTest5();
        $b1->setFoo('Hello');
        $b2 = new VersionableBehaviorTest5();
        $b2->setFoo('World');
        $a = new VersionableBehaviorTest4();
        $a->setBar(123); // a1
        $a->addVersionableBehaviorTest5($b1);
        $a->addVersionableBehaviorTest5($b2);
        $a->save(); //b1
        $this->assertEquals(1, $a->getVersion());
        $this->assertEquals([1, 1], $a->getOneVersion(1)->getVersionableBehaviorTest5Versions());
        $b1->setFoo('Heloo');
        $a->save();
        $this->assertEquals(2, $a->getVersion());
        $this->assertEquals([2, 1], $a->getOneVersion(2)->getVersionableBehaviorTest5Versions());
        $b3 = new VersionableBehaviorTest5();
        $b3->setFoo('Yep');
        $a->clearVersionableBehaviorTest5s();
        $a->addVersionableBehaviorTest5($b3);
        $a->save();
        $a->clearVersionableBehaviorTest5s();
        $this->assertEquals(3, $a->getVersion());
        $this->assertEquals([2, 1, 1], $a->getOneVersion(3)->getVersionableBehaviorTest5Versions());
    }

    /**
     * @return void
     */
    public function testEnumField()
    {
        $o = new VersionableBehaviorTest7();
        $o->setStyle('novel');
        $o->save();

        $this->assertEquals('novel', $o->getStyle(), 'Set style to novel');
        $this->assertEquals(1, $o->getVersion(), '');

        $o->setStyle('essay');
        $o->save();

        $this->assertEquals('essay', $o->getStyle(), 'Set style to essay');
        $this->assertEquals(2, $o->getVersion(), '');

        $this->assertEquals('novel', $o->getOneVersion(1)->getStyle(), 'First version is a novel');
        $this->assertEquals('essay', $o->getOneVersion(2)->getStyle(), 'Second version is an essay');
    }

    /**
     * @return void
     */
    public function testSetField()
    {
        $o = new VersionableBehaviorTest7();
        $o->setStyle2(['novel', 'essay']);
        $o->save();

        $this->assertEquals(['novel', 'essay'], $o->getStyle2(), 'Set style to novel');
        $this->assertEquals(1, $o->getVersion(), '');

        $o->setStyle2(['essay']);
        $o->save();

        $this->assertEquals(['essay'], $o->getStyle2(), 'Set style to essay');
        $this->assertEquals(2, $o->getVersion(), '');

        $this->assertEquals(['novel', 'essay'], $o->getOneVersion(1)->getStyle2(), 'First version is a novel');
        $this->assertEquals(['essay'], $o->getOneVersion(2)->getStyle2(), 'Second version is an essay');
    }

    /**
     * @return void
     */
    public function testWithInheritance()
    {
        $b1 = new VersionableBehaviorTest8Foo();
        $b1->save();

        $b1->setFoobar('name');
        $b1->save();

        $object = $b1->getOneVersion($b1->getVersion());
        $this->assertTrue($object instanceof Versionablebehaviortest8Version);
    }

    /**
     * @return void
     */
    public function testEnforceVersioning()
    {
        $bar = new VersionableBehaviorTest10();
        $bar->setBar(42);
        $bar->save();

        $this->assertEquals(1, $bar->getVersion());
        $this->assertFalse($bar->isVersioningNecessary());

        $bar->enforceVersioning();
        $this->assertTrue($bar->isVersioningNecessary());

        $bar->save();
        $this->assertEquals(2, $bar->getVersion());
    }

    /**
     * @return void
     */
    public function testOneToOneCreatesValidRecord()
    {
        VersionableBehaviorTestOneToOneQuery::create()->deleteAll();
        VersionableBehaviorTestOneToOneKeyQuery::create()->deleteAll();
        VersionableBehaviorTestOneToOneVersionQuery::create()->deleteAll();
        VersionableBehaviorTestOneToOneKeyVersionQuery::create()->deleteAll();

        $x = new VersionableBehaviorTestOneToOne();
        $x->setBar('One To....');
        $x->save();

        $y = new VersionableBehaviorTestOneToOneKey();
        $y->setVersionableBehaviorTestOneToOne($x);
        $y->setBar('One');
        $y->save();

        $this->assertEquals(1, $x->getVersion());
        $this->assertEquals(1, $y->getVersion());

        $newX = VersionableBehaviorTestOneToOneQuery::create()->findOne();
        $this->assertInstanceOf('VersionableBehaviorTestOneToOne', $x);
        $this->assertInstanceOf('VersionableBehaviorTestOneToOne', $newX);
        $this->assertEquals($x, $newX);

        $newY = $x->getVersionableBehaviorTestOneToOneKey();
        $this->assertInstanceOf('VersionableBehaviorTestOneToOneKey', $y);
        $this->assertInstanceOf('VersionableBehaviorTestOneToOneKey', $newY);
        $this->assertEquals($y, $newY);

        $this->assertFalse($x->isVersioningNecessary());
        $x->setBar('Two');
        $this->assertTrue($x->isVersioningNecessary());
        $x->save();

        $this->assertEquals(2, $x->getVersion());
        $x->toVersion(1);
        $x->save();

        $this->assertEquals('One To....', $x->getBar());
        $this->assertEquals('One', $x->getVersionableBehaviorTestOneToOneKey()->getBar());//$y
    }
}
