<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class PrimaryKeyTest extends MigrationTestCase
{

    public function testAdd()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer"/>
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true"/>
        <column name="title" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testRemove()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true"/>
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer"/>
        <column name="title" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChange()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true"/>
        <column name="title" required="true" />
        <column name="uri" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="varchar" primaryKey="true"/>
        <column name="title" required="true" />
        <column name="uri" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChangeName()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true"/>
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="new_id" type="integer" primaryKey="true"/>
        <column name="title" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testChangeSize()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="varchar" size="50" primaryKey="true"/>
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="varchar" size="150" primaryKey="true"/>
        <column name="title" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

}
