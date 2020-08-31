<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class ForeignKeyTest extends MigrationTestCase
{
    /**
     * @return void
     */
    public function testAdd()
    {
        $originXml = '
<database>
    <table name="migration_test_6">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_6">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id" type="integer"/>
        <foreign-key foreignTable="migration_test_6" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @return void
     */
    public function testAddNotUnique()
    {
        $originXml = '
<database>
    <table name="migration_test_6_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_6_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="id2" type="integer"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id" type="integer"/>
        <foreign-key foreignTable="migration_test_6_1" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id2"/>
        </foreign-key>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @return void
     */
    public function testRemove()
    {
        $originXml = '
<database>
    <table name="migration_test_6">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id" type="integer"/>
        <foreign-key foreignTable="migration_test_6" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_6">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id"/>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @return void
     */
    public function testChange()
    {
        $originXml = '
<database>
    <table name="migration_test_6">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="id2" type="integer" primaryKey="true"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id" type="integer"/>
        <column name="test_6_id2" type="integer"/>
        <foreign-key foreignTable="migration_test_6" onDelete="cascade" onUpdate="cascade">
            <reference local="test_6_id" foreign="id"/>
            <reference local="test_6_id2" foreign="id2"/>
        </foreign-key>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_6">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="id2" type="integer" primaryKey="true"/>
        <column name="title" required="true"/>
    </table>
    <table name="migration_test_7">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="test_6_id"/>
        <column name="test_6_id2"/>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }
}
