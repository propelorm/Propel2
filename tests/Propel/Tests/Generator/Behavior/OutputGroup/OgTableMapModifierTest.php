<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\OutputGroup;

use Propel\Generator\Behavior\OutputGroup\OgTableMapModifier;
use Propel\Generator\Behavior\OutputGroup\OutputGroupBehavior;
use Propel\Generator\Builder\Om\TableMapBuilder;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Tests\TestCase;


/**
 */
class OgTableMapModifierTest extends TestCase
{
   
    public function buildTableMapBuilderForSchema(string $databaseXml, string $tableName): TableMapBuilder
    {
        $reader = new SchemaReader();
        $schema = $reader->parseString($databaseXml);
        $table = $schema->getDatabase()->getTable($tableName);

        $tableMapBuilder = new TableMapBuilder($table);
        $tableMapBuilder->setGeneratorConfig(new QuickGeneratorConfig());

        return $tableMapBuilder;
    }

    /**
     * @return void
     */
    public function testCollectColumnIndexesByOutputGroup()
    {
        $databaseXml = '
        <database>
            <behavior name="output_group"/>

            <table name="table">
                <column name="col0" />
                <column name="col1" outputGroup="group2"/>
                <column name="col2" outputGroup="group1"/>
                <column name="col3" outputGroup="group1,group2"/>
            </table>
        </database>
        ';

        $tableMapBuilder = $this->buildTableMapBuilderForSchema($databaseXml, 'table');
        $modifier = new OgTableMapModifier(new OutputGroupBehavior());

        $outputColumns = [];
        $this->callMethod($modifier, 'collectColumnIndexesByOutputGroup', [$tableMapBuilder->getTable(), &$outputColumns]);

        $expected = [
            'group1' => ['column_index' => [2,3]],
            'group2' => ['column_index' => [1,3]],
        ];
        
        $this->assertEquals($expected, $outputColumns);
    }



    /**
     * @return void
     */
    public function testCollectForeignKeysByOutputGroup()
    {
        $databaseXml = '
<database>
    <behavior name="output_group"/>

    <table name="local_table">
        <column name="col0" />
        <column name="col1" />
        <column name="col2" />
        <column name="col3" />
        <foreign-key foreignTable="foreign_table" phpName="LocalFk0" outputGroup="group1">
            <reference local="col0" foreign="ft_col0"/>
        </foreign-key>
        <foreign-key foreignTable="foreign_table" phpName="LocalFk1" outputGroup="group1,group2">
            <reference local="col1" foreign="ft_col1"/>
        </foreign-key>
    </table>
    <table name="foreign_table">
        <column name="ft_col0" />
        <column name="ft_col1" />
        <column name="ft_col2" />
        <column name="ft_col3" />

        <foreign-key foreignTable="local_table" phpName="RefFk2" refOutputGroup="group1">
            <reference local="ft_col2" foreign="col2"/>
        </foreign-key>
        <foreign-key foreignTable="local_table" phpName="RefFk3" refOutputGroup="group1,group2">
            <reference local="ft_col3" foreign="col3"/>
        </foreign-key>
    </table>
</database>
';
        $tableMapBuilder = $this->buildTableMapBuilderForSchema($databaseXml, 'local_table');
        $modifier = new OgTableMapModifier(new OutputGroupBehavior());

        $outputColumns = [];
        $this->callMethod($modifier, 'collectForeignKeysByOutputGroup', [$tableMapBuilder, &$outputColumns]);

        $expected = [
            'group1' => ['relation' => ['LocalFk0', 'LocalFk1', 'ForeignTableRelatedByFtCol2', 'ForeignTableRelatedByFtCol3']],
            'group2' => ['relation' => ['LocalFk1', 'ForeignTableRelatedByFtCol3']],
        ];
        
        $this->assertEquals($expected, $outputColumns);
    }
}
