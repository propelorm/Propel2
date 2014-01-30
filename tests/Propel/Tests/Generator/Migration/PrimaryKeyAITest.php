<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class PrimaryKeyAITest extends MigrationTestCase
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
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
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
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
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
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true" />
        <column name="uri" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true"/>
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
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="new_id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @group mysql
     */
    public function testChangeSize()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" size="1" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" size="5" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

}
