<?php

namespace Propel\Tests\Issues;

use Propel\Tests\Generator\Migration\MigrationTestCase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/1115.
 *
 * @group mysql
 * @group database
 */
class Issue1115Test extends MigrationTestCase
{

    /**
     * Test if an error is caused by attempting to add an index that includes a
     * foreign-keyed column while also making another change to the same table.
     */
    public function testChange() {
        $originXml = '
<database>
    <table name="foo">
        <column name="bar_id" type="integer"/>
        <column name="other_column" type="integer"/>
        <foreign-key foreignTable="bar">
            <reference local="bar_id" foreign="id"/>
        </foreign-key>
    </table>
    <table name="bar">
        <column name="id" type="integer" primaryKey="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="foo">
        <column name="bar_id" type="integer"/>
        <column name="other_column" type="integer"/>
        <column name="new_column" type="integer"/>
        <foreign-key foreignTable="bar">
            <reference local="bar_id" foreign="id"/>
        </foreign-key>
        <index>
            <index-column name="bar_id"/>
            <index-column name="other_column"/>
        </index>
    </table>
    <table name="bar">
        <column name="id" type="integer" primaryKey="true"/>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

}
