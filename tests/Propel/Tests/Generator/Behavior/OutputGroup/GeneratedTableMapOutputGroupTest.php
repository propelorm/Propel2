<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\OutputGroup;

use Map\GeneratedTableMapOutputGroupTestTableTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

class GeneratedTableMapOutputGroupTest extends TestCase
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!class_exists('GeneratedTableMapOutputGroupTestTable')) {
            static::buildLocalSchemaClasses();
        }
    }

    public static function buildLocalSchemaClasses(): void
    {
        $tableName = 'generated_table_map_output_group_test_table';
        $fkTableName = 'generated_table_map_output_group_test_foreign_table';
        $schema = <<<EOF
<database>

    <behavior name="output_group"/>

    <table name="$tableName">
        <column name="fkGroup1" type="integer" />
        <column name="fkGroup2" type="integer" />
        <column name="colGroup1" type="integer" outputGroup="group1"/>
        <column name="colGroup2" type="integer" outputGroup="group2"/>
        <column name="nogroupCol" type="integer" />
        <foreign-key foreignTable="$fkTableName" phpName="LocalFk0" outputGroup="group1">
            <reference local="fkGroup1" foreign="ft_col0"/>
        </foreign-key>
        <foreign-key foreignTable="$fkTableName" phpName="LocalFk1" outputGroup="group2">
            <reference local="fkGroup2" foreign="ft_col1"/>
        </foreign-key>
    </table>
    <table name="$fkTableName">
        <column name="ft_col0" type="integer" />
        <column name="ft_col1" type="integer" />
        <column name="ft_fkGroup1" type="integer" />
        <column name="ft_fkGroup2" type="integer" />

        <foreign-key foreignTable="$tableName" phpName="RefFk2" refOutputGroup="group1,group2">
            <reference local="ft_fkGroup1" foreign="colGroup1"/>
        </foreign-key>
        <foreign-key foreignTable="$tableName" phpName="RefFk3">
            <reference local="ft_fkGroup2" foreign="colGroup2"/>
        </foreign-key>
    </table>
</database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    public function groupDataProvider(): array
    {
        $group1Data = [
            'column_index' => [2],
            'relation' => [
                'LocalFk0' => 1,
                'GeneratedTableMapOutputGroupTestForeignTableRelatedByFtFkgroup1' => 1,
            ],
        ];

        $unknownGroupData = [
            'column_index' => [0, 1, 2, 3, 4],
            'relation' => null,
        ];

        return [
            [
                'group1',
                $group1Data,
            ],
            [
                'group2',
                [
                    'column_index' => [3],
                    'relation' => [
                        'LocalFk1' => 1,
                        'GeneratedTableMapOutputGroupTestForeignTableRelatedByFtFkgroup1' => 1,
                    ],
                ],
            ],
            [
                'unknown_group',
                $unknownGroupData,
            ],
            [
                ['group1'],
                $group1Data,
            ],
            [
                ['group1', 'group2'],
                [
                    'column_index' => [2, 3],
                    'relation' => [
                        'LocalFk0' => 1,
                        'LocalFk1' => 1,
                        'GeneratedTableMapOutputGroupTestForeignTableRelatedByFtFkgroup1' => 1,
                    ],
                ],
            ],
            [
                ['group1', 'unknown_group'],
                $group1Data,
            ],
            [
                [],
                $unknownGroupData,
            ],
            [
                ['unknown_group'],
                $unknownGroupData,
            ],
        ];
    }

    /**
     * @dataProvider groupDataProvider
     *
     * @return void
     */
    public function testGetOutputGroupData($groupName, array $expectedGroupData)
    {
        $groupData = GeneratedTableMapOutputGroupTestTableTableMap::getOutputGroupData($groupName);
        $this->assertEquals($expectedGroupData, $groupData);
    }
}
