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

namespace Propel\Tests\Generator\Behavior\SyncedTable;

use Exception;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 */
class SyncedTableBehaviorTest extends TestCase
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
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="id" required="true" primaryKey="true" type="INTEGER"/>
                <column name="fk_column" type="INTEGER"/>
                <column name="string_column" type="VARCHAR" size="42"/>
                ',
            ],
            [
                // description
                'Cannot override columns declared on synced table',
                //additional behavior parameters
                '',
                // source table columns: column with size 8
                '<column name="string_column" type="VARCHAR" size="8"/>',
                // synced table input columns: column with size 999
                '<column name="string_column" type="VARCHAR" size="999"/>',
                // auxiliary schema data
                '',
                // synced output columns
                '<column name="string_column" type="VARCHAR" size="999"/>',
            ],
            [
                // description
                'Should sync index if requested',
                //additional behavior parameters
                '<parameter name="sync_indexes" value="true"/>',
                // source table columns: column with index
                '
                <column name="string_column" type="VARCHAR" size="42"/>
                <index>
                    <index-column name="string_column" />
                </index>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="string_column" type="VARCHAR" size="42"/>
                <index>
                    <index-column name="string_column" />
                </index>
                ',
            ],
            [
                // description
                'Syncing index can be enabled',
                //additional behavior parameters
                '<parameter name="sync_indexes" value="true"/>',
                // source table columns: column with index
                '
                <column name="string_column" type="VARCHAR" size="42"/>
                <index>
                    <index-column name="string_column" />
                </index>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="string_column" type="VARCHAR" size="42"/>
                <index name="synced_table_i_811f1f">
                    <index-column name="string_column" />
                </index>
                ',
            ],
            [
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
                // synced table input columns
                '',
                // auxiliary schema data
                '
                <table name="fk_table">
                    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
                </table>
                ',
                // synced output columns
                '<column name="fk_column" type="INTEGER"/>',
            ],
            [
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
                // synced table input columns
                '',
                // auxiliary schema data
                '
                <table name="fk_table">
                    <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
                </table>
                ',
                // synced output columns
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="fk_table" name="LeFk">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
            ],
            [
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
                // synced table input columns
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
                // synced output columns
                '
                <column name="fk_column" type="INTEGER"/>
                <foreign-key foreignTable="new_table" name="LeName">
                    <reference local="fk_column" foreign="id"/>
                </foreign-key>
                ',
            ],
            [
                // description
                'Behavior cannot override FKs declared on synced table',
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
                // synced table input columns: fk conflicting with behavior fk
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
                    // synced output columns: expect exception
                EngineException::class,
            ],
            [
                // description
                'Behavior does not sync unique indexes by default',
                //additional behavior parameters
                '',
                // source table columns: column with uniques
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <unique>
                    <unique-column name="col1" />
                </unique>
                <unique>
                    <unique-column name="col2" />
                    <unique-column name="col3" />
                </unique>

                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                ',
            ],
            [
                // description
                'Behavior syncs unique indexes as regular indexes if requested',
                //additional behavior parameters
                '<parameter name="sync_unique_as" value="index"/>',
                // source table columns: column with uniques
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <unique>
                    <unique-column name="col1" />
                </unique>
                <unique>
                    <unique-column name="col2" />
                    <unique-column name="col3" />
                </unique>

                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <index>
                    <index-column name="col1" />
                </index>
                <index>
                    <index-column name="col2" />
                    <index-column name="col3" />
                </index>
                ',
            ],
            [
                // description
                'Behavior syncs unique indexes as unique indexes if requested',
                //additional behavior parameters
                '<parameter name="sync_unique_as" value="unique"/>',
                // source table columns: column with uniques
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <unique>
                    <unique-column name="col1" />
                </unique>
                <unique>
                    <unique-column name="col2" />
                    <unique-column name="col3" />
                </unique>

                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <unique>
                    <unique-column name="col1" />
                </unique>
                <unique>
                    <unique-column name="col2" />
                    <unique-column name="col3" />
                </unique>
                ',
            ],
            [
                // description
                'Behavior can add pk',
                //additional behavior parameters
                '<parameter name="add_pk" value="true"/>',
                // source table columns: column with index
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <column name="col2" type="INTEGER" primaryKey="true"/>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" required="true"/>
                <column name="col2" type="INTEGER" required="true"/>
                <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
                ',
            ],
            [
                // description
                'Behavior can add renamed pk',
                //additional behavior parameters
                '<parameter name="add_pk" value="lePk"/>',
                // source table columns: column with index
                '',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="lePk" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
                ',
            ],
            [
                // description
                'Behavior can add FK by parameter',
                //additional behavior parameters
                '
                <parameter name="relation" value="true"/>
                ',
                // source table columns: column with index
                '<column name="col1" type="INTEGER" primaryKey="true"/>',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <foreign-key foreignTable="source_table">
                    <reference local="col1" foreign="col1"/>
                </foreign-key>
                ',
            ],
            [
                // description
                'Behavior can add FK by array',
                //additional behavior parameters
                '
                <parameter-list name="relation">
                    <parameter-list-item>
                        <parameter name="onDelete" value="cascade" />
                    </parameter-list-item>
                </parameter-list>
                ',
                // source table columns: column with index
                '<column name="col1" type="INTEGER" primaryKey="true"/>',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <foreign-key foreignTable="source_table" onDelete="cascade">
                    <reference local="col1" foreign="col1"/>
                </foreign-key>
                ',
            ],
            [
                // description
                'Behavior can add cascading FK when changing id',
                //additional behavior parameters
                '
                <parameter-list name="relation" value="true"/>
                <parameter name="add_pk" value="lePk"/>
                ',
                // source table columns: column with index
                '<column name="col1" type="INTEGER" primaryKey="true"/>',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" required="true"/>
                <column name="lePk" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
                <foreign-key foreignTable="source_table">
                    <reference local="col1" foreign="col1"/>
                </foreign-key>
                ',
            ],
            [
                // description
                'Behavior ignores marked columns',
                //additional behavior parameters
                '
                <parameter name="ignore_columns" value="col1,col3"/>
                <parameter name="sync_indexes" value="true"/>
                <parameter name="sync_unique_as" value="unique"/>
                <parameter name="inherit_foreign_key_constraints" value="true"/>
                ',
                // source table columns
                '
                <column name="col1" type="INTEGER" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <index>
                    <index-column name="col1" />
                </index>
                <index>
                    <index-column name="col1" />
                    <index-column name="col3" />
                </index>
                <index>
                    <index-column name="col1" />
                    <index-column name="col2" />
                    <index-column name="col3" />
                </index>
                <unique>
                    <unique-column name="col1" />
                </unique>
                <unique>
                    <unique-column name="col2" />
                </unique>
                <unique>
                    <unique-column name="col2" />
                    <unique-column name="col3" />
                </unique>
                <foreign-key foreignTable="fk_table" name="fk12">
                    <reference local="col1" foreign="col1"/>
                    <reference local="col2" foreign="col2"/>
                </foreign-key>
                <foreign-key foreignTable="fk_table" name="fk2">
                    <reference local="col2" foreign="col2"/>
                </foreign-key>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '
                <table name="fk_table">
                    <column name="col1" type="INTEGER"/>
                    <column name="col2" type="INTEGER"/>
                </table>
                ',
                // synced output columns
                '
                <column name="col2" type="INTEGER" />
                <index>
                    <index-column name="col2" />
                </index>
                <unique>
                    <unique-column name="col2" />
                </unique>
                <foreign-key foreignTable="fk_table" name="fk2">
                    <reference local="col2" foreign="col2"/>
                </foreign-key>
                ',
            ],
            [
                // description
                'Behavior can sync only PKs',
                //additional behavior parameters
                '
                <parameter name="sync_pk_only" value="true"/>
                ',
                // source table columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <column name="col2" type="INTEGER" primaryKey="true" />
                <column name="col3" type="INTEGER" />
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <column name="col2" type="INTEGER" primaryKey="true" />
                ',
            ],
            [
                // description
                'Behavior can sync reduced PKs',
                //additional behavior parameters
                '
                <parameter name="sync_pk_only" value="true"/>
                <parameter name="ignore_columns" value="col1"/>
                ',
                // source table columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <column name="col2" type="INTEGER" primaryKey="true" />
                <column name="col3" type="INTEGER" />
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col2" type="INTEGER" primaryKey="true"/>
                ',
            ],
            [
                // description
                'Behavior can add columns',
                //additional behavior parameters
                '
                <parameter-list name="columns">
                    <parameter-list-item>
                        <parameter name="name" value="added_col" />
                        <parameter name="type" value="varchar" />
                        <parameter name="size" value="42" />
                        <parameter name="primaryKey" value="true" />
                    </parameter-list-item>
                </parameter-list>
                ',
                // source table columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="col1" type="INTEGER" primaryKey="true"/>
                <column name="added_col" type="VARCHAR" size="42" primaryKey="true"/>
                ',
            ],
            [
                // description
                'Behavior prefixes synced columns',
                //additional behavior parameters
                '
                <parameter name="column_prefix" value="asdf_"/>
                <parameter name="sync_indexes" value="true"/>
                <parameter name="sync_unique_as" value="unique"/>
                <parameter name="inherit_foreign_key_constraints" value="true"/>
                <parameter name="relation" value="true"/>
                ',
                // source table columns
                '
                <column name="col1" type="INTEGER" primaryKey="true" />
                <column name="col2" type="INTEGER" />
                <column name="col3" type="INTEGER" />
                <index>
                    <index-column name="col2" />
                    <index-column name="col3" />
                </index>
                <unique>
                    <unique-column name="col3" />
                </unique>
                <foreign-key foreignTable="fk_table" name="fk12">
                    <reference local="col1" foreign="col1"/>
                    <reference local="col2" foreign="col2"/>
                </foreign-key>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '
                <table name="fk_table">
                    <column name="col1" type="INTEGER"/>
                    <column name="col2" type="INTEGER"/>
                </table>
                ',
                // synced output columns
                '
                <column name="asdf_col1" type="INTEGER" primaryKey="true" />
                <column name="asdf_col2" type="INTEGER" />
                <column name="asdf_col3" type="INTEGER" />
                <index>
                    <index-column name="asdf_col2" />
                    <index-column name="asdf_col3" />
                </index>
                <unique>
                    <unique-column name="asdf_col3" />
                </unique>
                <foreign-key foreignTable="fk_table" name="fk12">
                    <reference local="asdf_col1" foreign="col1"/>
                    <reference local="asdf_col2" foreign="col2"/>
                </foreign-key>
                <foreign-key foreignTable="source_table">
                    <reference local="asdf_col1" foreign="col1"/>
                </foreign-key>
                ',
            ],
            [
                // description
                'Behavior prefixes synced columns with table name by default',
                //additional behavior parameters
                '
                <parameter name="column_prefix" value="true"/>
                ',
                // source table columns
                '
                <column name="col1"/>
                ',
                // synced table input columns
                '',
                // auxiliary schema data
                '',
                // synced output columns
                '
                <column name="source_table_col1" />
                ',
            ],
            [
                // description
                'Behavior can inherit from other table by table name',
                //additional behavior parameters
                '
                <parameter name="inherit_from" value="base_table"/>
                ',
                // source table columns
                '',
                // synced table input columns
                '
                <column name="prev"/>
                ',
                // auxiliary schema data
                '
                <table name="base_table" skipSql="true">
                    <column name="inherited_col"/>
                </table>
                ',
                // synced output columns
                '
                <column name="prev"/>
                <column name="inherited_col"/>
                ',
            ],
            [
                // description
                'Behavior can inherit from other table by array',
                //additional behavior parameters
                '
                <parameter-list name="inherit_from">
                    <parameter-list-item>
                        <parameter name="source_table" value="base_table" />
                    </parameter-list-item>
                </parameter-list>
                ',
                // source table columns
                '',
                // synced table input columns
                '
                <column name="prev"/>
                ',
                // auxiliary schema data
                '
                <table name="base_table" skipSql="true">
                    <column name="inherited_col"/>
                </table>
                ',
                // synced output columns
                '
                <column name="prev"/>
                <column name="inherited_col"/>
                ',
            ],
            [
                // description
                'Inheritance overrides defaults',
                //additional behavior parameters
                '
                <parameter name="inherit_from" value="base_table"/>
                ',
                // source table columns
                '
                <column name="source_col"/>
                <column name="overridden_col" type="INTEGER"/>
                ',
                // synced table input columns
                '
                ',
                // auxiliary schema data
                '
                <table name="base_table" skipSql="true">
                    <column name="overridden_col" type="JSON"/>
                </table>
                ',
                // synced output columns
                '
                <column name="overridden_col" type="JSON"/>
                <column name="source_col"/>
                ',
            ],
        ];
    }

    /**
     * @dataProvider syncTestDataProvider
     *
     * @param string $message
     * @param string $behaviorAdditions
     * @param string $sourceTableContentTags
     * @param string $syncedTableInputTags
     * @param string $auxiliaryTables
     * @param string $syncedTableOutputTags
     *
     * @return void
     */
    public function testSync(
        string $message,
        string $behaviorAdditions,
        string $sourceTableContentTags,
        string $syncedTableInputTags,
        string $auxiliaryTables,
        string $syncedTableOutputTags
    ) {
        // source table: some columns
        // synced table: empty
        $inputSchemaXml = <<<EOT
<database>
    <table name="source_table">
        <behavior name="synced_table">
            <parameter name="table_name" value="synced_table"/>
            $behaviorAdditions
        </behavior>

        $sourceTableContentTags

    </table>
    
    $auxiliaryTables

    <table name="synced_table">$syncedTableInputTags</table>
</database>
EOT;

        // synced table: all columns
        $expectedTableXml = <<<EOT
<database>
    <table name="source_table">
        $sourceTableContentTags
    </table>

    <table name="synced_table">
        $syncedTableOutputTags
    </table>

    $auxiliaryTables
</database>
EOT;

        if (class_exists($syncedTableOutputTags) && is_subclass_of($syncedTableOutputTags, Exception::class)) {
            $this->expectException($syncedTableOutputTags);
        }
        $this->assertSchemaTableMatches($expectedTableXml, $inputSchemaXml, 'synced_table', $message);
    }

    /**
     * @return void
     */
    public function testNoSyncByDefaultIfParentSkipsSql(): void
    {
        $inputSchemaXml = <<<EOT
<database>
    <table name="source_table1" skipSql="true">
        <behavior name="synced_table"/>
    </table>

    <table name="source_table2" skipSql="true">
        <behavior name="synced_table">
            <parameter name="on_skip_sql" value="omit"/>
        </behavior>
    </table>
</database>
EOT;
        $db = $this->buildSchema($inputSchemaXml);
        $this->assertcount(2, $db->getTables());
        $this->assertNull($db->getTable('source_table1_synced'));
        $this->assertNull($db->getTable('source_table2_synced'));
    }

    /**
     * @return void
     */
    public function testInheritSkipsSql(): void
    {
        $inputSchemaXml = <<<EOT
<database>
    <table name="source_table1" skipSql="true">
        <behavior name="synced_table">
            <parameter name="on_skip_sql" value="inherit"/>
        </behavior>
    </table>
    <table name="source_table2">
        <behavior name="synced_table">
            <parameter name="on_skip_sql" value="inherit"/>
        </behavior>
    </table>
</database>
EOT;
        $db = $this->buildSchema($inputSchemaXml);

        $syncedTable1 = $db->getTable('source_table1_synced');
        $this->assertNotNull($syncedTable1, 'Should create synced table 1');
        $this->assertTrue($syncedTable1->isSkipSql(), 'Should inherit skipSql="true"');

        $syncedTable2 = $db->getTable('source_table2_synced');
        $this->assertNotNull($syncedTable2, 'Should create synced table 2');
        $this->assertFalse($syncedTable2->isSkipSql(), 'Should inherit skipSql="false"');
    }

    /**
     * @return void
     */
    public function testIgnoreSkipsSql(): void
    {
        $inputSchemaXml = <<<EOT
<database>
    <table name="source_table" skipSql="true">
        <behavior name="synced_table">
            <parameter name="on_skip_sql" value="ignore"/>
        </behavior>
    </table>
</database>
EOT;
        $db = $this->buildSchema($inputSchemaXml);

        $syncedTable = $db->getTable('source_table_synced');
        $this->assertNotNull($syncedTable, 'Should create synced table');
        $this->assertFalse($syncedTable->isSkipSql(), 'Should ignore skipSql="true"');
    }

    /**
     * @return void
     */
    public function testSetTableAttributes(): void
    {
        $inputSchemaXml = <<<EOT
<database>
    <table name="source_table">
        <behavior name="synced_table">
            <parameter-list name="table_attributes">
                <parameter-list-item>
                    <parameter name="foo" value="bar"/>
                </parameter-list-item>
            </parameter-list>
        </behavior>
    </table>
</database>
EOT;
        $db = $this->buildSchema($inputSchemaXml);

        $syncedTable = $db->getTable('source_table_synced');
        $this->assertArrayHasKey('foo', $syncedTable->getAttributes());
        $this->assertSame('bar', $syncedTable->getAttribute('foo'));
    }

    /**
     * @return void
     */
    public function testCreatFkRelationWithoutConstraint(): void
    {
        $inputSchemaXml = <<<EOT
<database>
    <table name="source_table">
        <behavior name="synced_table">
            <parameter name="relation" value="skipSql"/>
        </behavior>
    </table>
</database>
EOT;
        $db = $this->buildSchema($inputSchemaXml);

        $syncedTable = $db->getTable('source_table_synced');
        $fks = $syncedTable->getForeignKeys();
        $this->assertCount(1, $fks);
        $relation = reset($fks);
        $this->assertTrue($relation->isSkipSql());
    }


    /**
     * @param string $expectedTableXml
     * @param string $schema
     * @param string $tableName
     * @param string|null $message
     *
     * @return void
     */
    protected function assertSchemaTableMatches(string $expectedTableXml, string $inputSchemaXml, string $tableName, ?string $message = null)
    {
        $expectedDb = $this->buildSchema($expectedTableXml);
        $expectedTable = $expectedDb->getTable($tableName);

        $actualDb = $this->buildSchema($inputSchemaXml);
        $actualTable = $actualDb->getTable($tableName);

        $diff = TableComparator::computeDiff($actualTable, $expectedTable);
        if ($diff !== false) {
            $message = $this->buildTestMessage($message, $diff, $expectedDb, $actualDb);
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
     * @param \Propel\Generator\Model\Database $expectedDb
     * @param \Propel\Generator\Model\Database $actualDb
     *
     * @return string
     */
    protected function buildTestMessage(string $inputMessage, TableDiff $diff, Database $expectedDb, Database $actualDb)
    {
        $inputMessage ??= '';
        $platform = new MysqlPlatform();
        $sql = $platform->getModifyTableDDL($diff);

        return <<<EOT
$inputMessage

Synced table not as expected:
───────────────────────────────────────────────────────
Diff summary:

$diff

───────────────────────────────────────────────────────
DDL (MySQL) to turn actual table into expected table:

$sql

───────────────────────────────────────────────────────
Expected database:

$expectedDb

───────────────────────────────────────────────────────
Actual database:

$actualDb
───────────────────────────────────────────────────────

EOT;
    }
}
