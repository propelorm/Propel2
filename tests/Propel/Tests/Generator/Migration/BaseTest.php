<?php

namespace Propel\Tests\Generator\Migration;

/**
 * @group database
 */
class BaseTest extends MigrationTestCase
{
    public function testSimpleAdd()
    {
        $originXml = '
<database>
    <table name="migration_test_0">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_0">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="charfield" type="CHAR" size="1" />
    </table>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    public function testSimpleSize()
    {
        $originXml = '
<database>
    <table name="migration_test_0">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" type="VARCHAR" size="50" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_0">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" type="VARCHAR" size="250" />
    </table>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    public function testCharToChar()
    {
        $originXml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="charfield" type="CHAR" size="1" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="charfield" type="CHAR" size="1" />
    </table>
</database>
';

        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    public function testScale()
    {
        $originXml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="credits" phpName="Credits" type="DECIMAL" size="9" scale="2" required="true"/>

    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="credits" phpName="Credits" type="DECIMAL" scale="2" required="true"/>

    </table>
</database>
';

        $target2Xml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="credits" phpName="Credits" type="DECIMAL" size="10" scale="2" required="true"/>

    </table>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
        $this->applyXmlAndTest($target2Xml);
    }

    public function testColumnRequireChange()
    {
        $originXml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_1">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" />
    </table>
</database>
';

        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnTypeChangeSimple()
    {
        $originXml = '
<database>
    <table name="migration_test_2">
        <column name="field1" type="VARCHAR" />
        <column name="field2" type="INTEGER" />
        <column name="field3" type="BOOLEAN" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_2">
        <column name="field1" type="INTEGER" />
        <column name="field2" type="VARCHAR" />
        <column name="field3" type="VARCHAR" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnTypeChangeComplex()
    {
        $originXml = '
<database>
    <table name="migration_test_complex">
        <column name="field1" type="CHAR" />
        <column name="field2" type="LONGVARCHAR" />
        <column name="field3" type="CLOB" />

        <column name="field4" type="NUMERIC" />
        <column name="field5" type="DECIMAL" />
        <column name="field6" type="TINYINT" />
        <column name="field7" type="SMALLINT" />

        <column name="field_object" type="object" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_complex">
        <column name="field1" type="LONGVARCHAR" />

        <column name="field4" type="DECIMAL" />
        <column name="field5" type="TINYINT" />
        <column name="field6" type="SMALLINT" />

        <column name="field_object" type="object" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnTypeChangeMoreComplex()
    {
        $originXml = '
<database>
    <table name="migration_test_3">
        <column name="field1" type="CHAR" size="5" />

        <column name="field2" type="INTEGER" size="6" />
        <column name="field3" type="BIGINT" />
        <column name="field4" type="REAL" />
        <column name="field5" type="FLOAT" />
        <column name="field6" type="DOUBLE" />

        <column name="field7" type="BINARY" />
        <column name="field8" type="VARBINARY" />
        <column name="field9" type="LONGVARBINARY" />
        <column name="field10" type="BLOB" />

        <column name="field11" type="DATE" />
        <column name="field12" type="TIME" />
        <column name="field13" type="TIMESTAMP" />

        <column name="field14" type="ENUM" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_3">
        <column name="field1" type="CHAR" size="5" />

        <column name="field2" type="INTEGER" size="12" />
        <column name="field3" type="REAL" />
        <column name="field4" type="FLOAT" />
        <column name="field5" type="DOUBLE" />
        <column name="field6" type="BIGINT" />

        <column name="field7" type="VARBINARY" />
        <column name="field8" type="LONGVARBINARY" />
        <column name="field9" type="BLOB" />
        <column name="field10" type="BINARY" />

        <column name="field11" type="TIME" />
        <column name="field12" type="TIMESTAMP" />
        <column name="field13" type="DATE" />

        <column name="field14" type="VARCHAR" size="200" />
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    public function testColumnChangePrimaryKey()
    {
        $originXml = '
<database>
    <table name="migration_test_5">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true" />
        <column name="title" required="true" />
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_5">
        <column name="id" type="integer" />
        <column name="title" />
    </table>
</database>
';

        $target2Xml = '
<database>
    <table name="migration_test_5">
        <column name="id" type="integer" primaryKey="true" />
        <column name="title" />
    </table>
</database>
';

        $target3Xml = '
<database>
    <table name="migration_test_5">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"  />
        <column name="title" />
    </table>
</database>
';

        $target4Xml = '
<database>
    <table name="migration_test_5">
        <column name="id" type="integer" primaryKey="true" />
        <column name="title" />
    </table>
</database>
';

        $target5Xml = '
<database>
    <table name="migration_test_5">
        <column name="id" type="varchar" size="200" primaryKey="true" />
        <column name="title" required="true" type="integer" />
    </table>
</database>
';
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
        $this->applyXmlAndTest($target2Xml);
        $this->applyXmlAndTest($target3Xml);
        $this->applyXmlAndTest($target4Xml);
        $this->applyXmlAndTest($target5Xml);
    }

}
