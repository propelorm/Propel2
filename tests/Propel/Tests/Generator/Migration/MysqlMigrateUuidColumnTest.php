<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Migration;

use Propel\Generator\Exception\BuildException;
use Propel\Tests\Helpers\CheckMysql8Trait;

/**
 * @group mysql
 * @group database
 */
class MysqlMigrateUuidColumnTest extends MigrationTestCase
{
    use CheckMysql8Trait;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!$this->checkMysqlVersionAtLeast8('migration')) {
            $this->markTestSkipped('Test can only be run on MySQL version >= 8');

            return;
        }
        $this->con->exec('DROP TABLE IF EXISTS migration.table_with_uuid;');
    }

    /**
     * @dataProvider migrationDataProvider
     *
     * @param string $description
     * @param string $fromColumns
     * @param string $toColumns
     * @param array|null $values
     *
     * @return void
     */
    public function testMigrations(string $description, string $fromColumns, string $toColumns, ?array $values)
    {
        $this->applyWithFail($description . ' - failed to apply initial schema', $fromColumns, false);
        $values && $this->insertValues($values['in']);

        $this->applyWithFail($description . ' - failed to apply migration', $toColumns, true);
        $values && $this->assertTableDataMatches($values['out'], $description);
    }

    /**
     * @param array $values
     *
     * @return void
     */
    protected function insertValues(array $values)
    {
        $valueS = "'" . implode("','", $values) . "'";
        $this->con->exec("INSERT INTO migration.table_with_uuid VALUES($valueS);");
    }

    /**
     * @param array $values
     * @param string $description
     *
     * @return void
     */
    protected function assertTableDataMatches(array $values, string $description)
    {
        $row = $this->con->query('SELECT * FROM migration.table_with_uuid;')->fetch();
        //var_dump($values, $row);
        $this->assertEquals($values, $row, $description);
    }

    /**
     * @param string $description
     * @param string $columns
     * @param bool $changeRequired
     *
     * @return void
     */
    protected function applyWithFail(string $description, string $columns, bool $changeRequired)
    {
        $databaseXml = $this->buildDatabaseTableXml($columns);

        try {
            $this->applyXmlAndTest($databaseXml, $changeRequired);
        } catch (BuildException $e) {
            $this->fail($description . "\n\n" . $e->getMessage());
        }
    }

    /**
     * @param string $columnDef
     *
     * @return string
     */
    protected function buildDatabaseTableXml(string $columnDef): string
    {
        return <<< EOF
<database>
    <table name="table_with_uuid">
        $columnDef
    </table>
</database>
EOF;
    }

    /**
     * @return array
     */
    public function migrationDataProvider(): array
    {
        $uuid = '6cb1a126-2b34-4856-9a39-455d8b5efd29';
        $bin = hex2bin('48562b346cb1a1269a39455d8b5efd29');

        $uuid2 = 'a3b98dbb-2bb3-4319-a010-21e2301e7d3c';
        $bin2 = hex2bin('43192bb3a3b98dbba01021e2301e7d3c');

        return [
        [
            'From varchar to uuid',
                '<column name="id" type="varchar" size="100" />',
                '<column name="id" type="UUID" />',
            ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'From char to uuid',
                '<column name="id" type="char" size="100" />',
                '<column name="id" type="UUID" />',
            ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'From uuid to varchar',
                '<column name="id" type="UUID" />',
                '<column name="id" type="varchar" size="36" content="UUID" />',
            ['in' => [$bin], 'out' => [$uuid]],
        ], [
            'From uuid to char',
                '<column name="id" type="UUID" />',
                '<column name="id" type="char" size="36" content="UUID" />',
            ['in' => [$bin], 'out' => [$uuid]],
        ], [
            'Preserve column order',
            '
                <column name="col1" type="integer" />
                <column name="id" type="UUID" />
                <column name="col2" type="integer" />
            ', '
                <column name="col1" type="integer" />
                <column name="id" type="varchar" size="36" content="UUID" />
                <column name="col2" type="integer" />
            ',
            null,
        ], [
            'Change column order',
            '
                <column name="col1" type="integer" />
                <column name="id" type="varchar" size="36" />
                <column name="col2" type="integer" />
                <column name="col3" type="integer" />
            ', '
                <column name="col1" type="integer" />
                <column name="col2" type="integer" />
                <column name="id" type="UUID" />
                <column name="col3" type="integer" />
            ',
            null,
        ], [
            'Drop primary key',
                '<column name="id" type="varchar" size="36" primaryKey="true" />',
                '<column name="id" type="UUID" />',
            ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'Add primary key',
                '<column name="id" type="varchar" size="36" />',
                '<column name="id" type="UUID" primaryKey="true" />',
            ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'Swap uuid and varchar in complex primary key',
            '
                <column name="id1" type="varchar" size="36" primaryKey="true" />
                <column name="id2" type="UUID" primaryKey="true" />
            ', '
                <column name="id1" type="UUID" primaryKey="true" />
                <column name="id2" type="varchar" size="36" primaryKey="true" content="UUID" />
            ', [
                'in' => [$uuid, $bin2], 'out' => [$bin, $uuid2],
            ],
        ], [
            'Swap uuid and varchar and drop complex primary key',
            '
                <column name="id1" type="varchar" size="36" primaryKey="true" />
                <column name="id2" type="UUID" primaryKey="true" />
            ', '
                <column name="id1" type="UUID" />
                <column name="id2" type="varchar" size="36" content="UUID" />
            ', ['in' => [$uuid, $bin2], 'out' => [$bin, $uuid2]],
        ], [
            'Swap uuid and varchar and add complex primary key',
            '
                <column name="id1" type="varchar" size="36" />
                <column name="id2" type="UUID" />
            ', '
                <column name="id1" type="UUID" primaryKey="true" />
                <column name="id2" type="varchar" size="36" content="UUID" primaryKey="true" />
            ', ['in' => [$uuid, $bin2], 'out' => [$bin, $uuid2]],
        ], [
            'Preserve complex PK',
            '
                <column name="id" type="varchar" size="36" primaryKey="true" />
                <column name="title" primaryKey="true" />
            ', '
                <column name="id" type="UUID" primaryKey="true" />
                <column name="title" primaryKey="true" />
            ', ['in' => [$uuid, 'le title'], 'out' => [$bin, 'le title']],
        ], [
            'Preserve index',
            '
                <column name="id" type="varchar" size="36" />
                <index name="le_id_index">
                    <index-column name="id" />
                </index>
            ', '
                <column name="id" type="UUID" />
                <index name="le_id_index">
                    <index-column name="id" />
                </index>
            ', ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'Drop index',
            '
                <column name="id" type="varchar" size="36" />
                <index name="le_id_index">
                    <index-column name="id" />
                </index>
            ', '
                <column name="id" type="UUID" />
            ', ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'Add index',
            '
                <column name="id" type="varchar" size="36" />
            ', '
                <column name="id" type="UUID" />
                <index name="le_id_index">
                    <index-column name="id" />
                </index>
            ', ['in' => [$uuid], 'out' => [$bin]],
        ], [
            'Preserve index on multiple columns',
            '
                <column name="id" type="varchar" size="36" />
                <column name="title" type="varchar" size="64" />
                <index name="le_joined_index">
                    <index-column name="id" />
                    <index-column name="title" />
                </index>
            ', '
                <column name="id" type="UUID" />
                <column name="title" type="varchar" size="64" />
                <index name="le_joined_index">
                    <index-column name="id" />
                    <index-column name="title" />
                </index>
            ', ['in' => [$uuid, 'le title'], 'out' => [$bin, 'le title']],
        ], [
            'Preserve FK',
            '
                <column name="id" type="varchar" size="36" primaryKey="true"  />
                <column name="fk" type="varchar" size="36" />
                <foreign-key foreignTable="table_with_uuid">
                    <reference local="fk" foreign="id" />
                </foreign-key>
            ', '
                <column name="id" type="UUID" primaryKey="true" />
                <column name="fk" type="UUID" />
                <foreign-key foreignTable="table_with_uuid">
                    <reference local="fk" foreign="id" />
                </foreign-key>
            ', ['in' => [$uuid, $uuid], 'out' => [$bin, $bin]],
        ], [
            'Add FK',
            '
                <column name="id" type="varchar" size="36" primaryKey="true"  />
                <column name="fk" type="varchar" size="36" />
            ', '
                <column name="id" type="UUID" primaryKey="true" />
                <column name="fk" type="UUID" />
                <foreign-key foreignTable="table_with_uuid">
                    <reference local="fk" foreign="id" />
                </foreign-key>
            ', ['in' => [$uuid, $uuid], 'out' => [$bin, $bin]],
        ], [
            'Remove FK',
            '
                <column name="id" type="varchar" size="36" primaryKey="true"  />
                <column name="fk" type="varchar" size="36" />
                <foreign-key foreignTable="table_with_uuid">
                    <reference local="fk" foreign="id" />
                </foreign-key>
            ', '
                <column name="id" type="UUID" primaryKey="true" />
                <column name="fk" type="UUID" />
            ', ['in' => [$uuid, $uuid], 'out' => [$bin, $bin]],
        ],
        ];
    }
}
