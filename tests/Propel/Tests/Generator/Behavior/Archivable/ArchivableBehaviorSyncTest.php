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

use Exception;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests the `sync` parameter of ArchivableBehavior, which automatically applies
 * changes to the source table to the archive table.
 */
class ArchivableBehaviorSyncTest extends TestCase
{
 /**
  * @return array
  */
    public function syncTestDataProvider(): array
    {
        return [
            [
                // description
                'Should sync columns',
                //additional behavior parameters
                '',
                // source table columns: some columns
                '
                <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
                <column name="fk_column" type="INTEGER"/>
                <column name="string_column" type="VARCHAR" size="42"/>
                ',
                // archive table input columns
                '',
                // auxiliary schema data
                '',
                // archive output columns
                '
                <column name="id" required="true" primaryKey="true" type="INTEGER"/>
                <column name="fk_column" type="INTEGER"/>
                <column name="string_column" type="VARCHAR" size="42"/>
                ',
            ], [
                // description
                'Cannot override columns declared on archive table',
                //additional behavior parameters
                '',
                // source table columns: column with size 8
                '<column name="string_column" type="VARCHAR" size="8"/>',
                // archive table input columns: column with size 999
                '<column name="string_column" type="VARCHAR" size="999"/>',
                // auxiliary schema data
                '',
                // archive output columns
                '<column name="string_column" type="VARCHAR" size="999"/>',
            ], [
                // description
                'Should sync index',
                //additional behavior parameters
                '',
                // source table columns: column with index
                '
                <column name="string_column" type="VARCHAR" size="42"/>
                <index>
                    <index-column name="string_column" />
                </index>
                ',
                // archive table input columns
                '',
                // auxiliary schema data
                '',
                // archive output columns
                '
                <column name="string_column" type="VARCHAR" size="42"/>
                <index name="archive_table_i_811f1f">
                    <index-column name="string_column" />
                </index>
                ',
            ], [
                // description
                'Should sync fk column without relation',
                //additional behavior parameters
                '',
                // source table columns: column with fk
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="fk_table" name="LeFk">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
                // archive table input columns
                '',
                // auxiliary schema data
                '
                <table name="fk_table">
                    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
                </table>
                ',
                // archive output columns
                '<column name="fk_column" type="INTEGER"/>',
            ], [
                // description
                'Should sync fk column with relation through parameter',
                //additional behavior parameters
                '<parameter name="inherit_foreign_key_constraints" value="true"/>',
                // source table columns: column with fk
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="fk_table" name="LeFk">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
                // archive table input columns
                '',
                // auxiliary schema data
                '
                <table name="fk_table">
                    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
                </table>
                ',
                // archive output columns
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="fk_table" name="LeFk">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
            ], [
                // description
                'Behavior can override synced FKs',
                //additional behavior parameters: inherit fks but override relation "LeName"
                '
                <parameter name="inherit_foreign_key_constraints" value="true"/>
                <parameter-list name="foreign_keys">
                    <parameter-list-item>
                        <parameter name="name" value="LeName" />
                        <parameter name="localColumn" value="fk_column" />
                        <parameter name="foreignTable" value="new_table" />
                        <parameter name="foreignColumn" value="id" />
                    </parameter-list-item>
                </parameter-list>
                ',
                // source table columns: column with fk
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="old_table" name="LeName">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
                // archive table input columns
                '',
                // auxiliary schema data
                '
                <table name="old_table">
                    <column name="id" type="INTEGER"/>
                </table>
                <table name="new_table">
                    <column name="id" type="INTEGER"/>
                </table>
                ',
                // archive output columns
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="new_table" name="LeName">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
            ], [
                // description
                'Behavior cannot override FKs declared on archive table',
                //additional behavior parameters: declare fk
                '
                <parameter-list name="foreign_keys">
                    <parameter-list-item>
                        <parameter name="name" value="LeName" />
                        <parameter name="localColumn" value="fk_column" />
                        <parameter name="foreignTable" value="new_table" />
                        <parameter name="foreignColumn" value="id" />
                    </parameter-list-item>
                </parameter-list>
                ',
                // source table columns
                '<column name="fk_column" type="INTEGER"/>',
                // archive table input columns: fk conflicting with behavior fk
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="old_table" name="LeName">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
                // auxiliary schema data
                '
                <table name="old_table">
                    <column name="id" type="INTEGER"/>
                </table>
                <table name="new_table">
                    <column name="id" type="INTEGER"/>
                </table>
                ',
                // archive output columns: expect exception
                EngineException::class,
            ],
        ];
    }

    /**
     * @dataProvider syncTestDataProvider
     *
     * @param string $message
     * @param string $behaviorAdditions
     * @param string $sourceTableContentTags
     * @param string $archiveTableInputTags
     * @param string $auxiliaryTables
     * @param string $archiveTableOutputTags
     *
     * @return void
     */
    public function testSync(
        string $message,
        string $behaviorAdditions,
        string $sourceTableContentTags,
        string $archiveTableInputTags,
        string $auxiliaryTables,
        string $archiveTableOutputTags
    ) {
        // source table: some columns
        // archive table: empty
        $schema = <<<EOT
<database>
    <table name="source_table">
        <behavior name="archivable">
            <parameter name="archive_table" value="archive_table"/>
            <parameter name="log_archived_at" value="false"/>
            <parameter name="sync" value="true"/>
            $behaviorAdditions
        </behavior>

        $sourceTableContentTags

    </table>
    
    $auxiliaryTables

    <table name="archive_table">$archiveTableInputTags</table>
</database>
EOT;

        // archive table: all columns plus archived_at
        $expected = <<<EOT
<database>
    <table name="archive_table">
    $archiveTableOutputTags
    </table>

    $auxiliaryTables
</database>
EOT;

        if (class_exists($archiveTableOutputTags) && is_subclass_of($archiveTableOutputTags, Exception::class)) {
            $this->expectException($archiveTableOutputTags);
        }
        $this->assertSchemaTableMatches($expected, $schema, 'archive_table', $message);
    }

    /**
     * @param string $expectedTableXml
     * @param string $schema
     * @param string $tableName
     * @param string|null $message
     *
     * @return void
     */
    protected function assertSchemaTableMatches(string $expectedTableXml, string $schema, string $tableName, ?string $message = null)
    {
        $expectedSchema = $this->buildSchema($expectedTableXml);
        $expectedTable = $expectedSchema->getTable($tableName);

        $actualSchema = $this->buildSchema($schema);
        $actualTable = $actualSchema->getTable($tableName);

        $diff = TableComparator::computeDiff($actualTable, $expectedTable);
        if ($diff !== false) {
            $message = $this->buildTestMessage($message, $diff, $expectedSchema, $actualSchema);
            $this->fail($message);
        }
        $this->expectNotToPerformAssertions();
    }

    /**
     * @param string $schema
     *
     * @return \Propel\Generator\Model\Database
     */
    protected function buildSchema(string $schema): Database
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);

        return $builder->getDatabase();
    }

    /**
     * @param string $inputMessage
     * @param \Propel\Generator\Model\Diff\TableDiff $diff
     * @param \Propel\Generator\Model\Database $expectedSchema
     * @param \Propel\Generator\Model\Database $actualSchema
     *
     * @return string
     */
    protected function buildTestMessage(string $inputMessage, TableDiff $diff, Database $expectedSchema, Database $actualSchema)
    {
        $inputMessage ??= '';
        $platform = new MysqlPlatform();
        $sql = $platform->getModifyTableDDL($diff);

        return <<<EOT
$inputMessage

Synced archive table not as expected:
───────────────────────────────────────────────────────
Diff summary:

$diff

───────────────────────────────────────────────────────
DDL (MySQL) to turn actual table into expected table:

$sql

───────────────────────────────────────────────────────
Expected database:

$expectedSchema

───────────────────────────────────────────────────────
Actual database:

$actualSchema
───────────────────────────────────────────────────────

EOT;
    }
}
