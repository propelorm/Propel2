<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class IndexTest extends MigrationTestCase
{

    public function testAdd()
    {
        $originXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <index>
            <index-column name="title" />
        </index>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testRemove()
    {
        $originXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <index>
            <index-column name="title" />
        </index>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
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
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <column name="uri" required="true" />
        <index>
            <index-column name="title" />
            <index-column name="uri" />
        </index>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
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
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <column name="uri" required="true" />
        <index name="testIndex">
            <index-column name="title" />
            <index-column name="uri" />
        </index>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <column name="uri" required="true" />
        <index name="NewIndexName">
            <index-column name="title" />
            <index-column name="uri" />
        </index>
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
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" size="50" />
        </index>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" size="100" />
        </index>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testSameIndex()
    {
        $originXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" />
        </index>
        <index name="testIndex2">
            <index-column name="title" />
        </index>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_8">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
        <index name="testIndex">
            <index-column name="title" />
        </index>
        <index name="testIndex2">
            <index-column name="title" />
        </index>
        <index name="testIndex3">
            <index-column name="title" />
        </index>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

}
