<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Migration;

use PDO;

/**
 * @group database
 */
class PrimaryKeyAITest extends MigrationTestCase
{
    /**
     * @return void
     */
    public function testAdd()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer"/>
        <column name="title" required="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
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
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer"/>
        <column name="title" required="true"/>
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
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
        <column name="uri" required="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true"/>
        <column name="title" required="true"/>
        <column name="uri" required="true"/>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @return void
     */
    public function testChangeName()
    {
        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="new_id" type="integer" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }

    /**
     * @group mysql
     *
     * @return void
     */
    public function testChangeSize()
    {
        if ($this->con->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $this->markTestSkipped('MySQL removed support of integer display width in version 8.0.19');
        }

        $originXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" size="1" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
</database>
';

        $targetXml = '
<database>
    <table name="migration_test_9">
        <column name="id" type="integer" size="5" primaryKey="true" autoIncrement="true"/>
        <column name="title" required="true"/>
    </table>
</database>
';
        $this->migrateAndTest($originXml, $targetXml);
    }
}
