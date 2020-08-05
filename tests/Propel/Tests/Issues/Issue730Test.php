<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Issue730DepartmentGroup;
use Issue730DepartmentGroupQuery;
use Issue730Group;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCaseFixtures;

class Issue730Test extends TestCaseFixtures
{
    /**
     * @return void
     */
    public function testNamespace()
    {
        $schema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="default" defaultIdMethod="native">
    <table name="issue730_group" idMethod="native">
        <column name="id" type="INTEGER" primaryKey="true" required="true"/>
        <column name="name" type="VARCHAR" size="100" required="true"/>
    </table>
    <table name="issue730_department_group" idMethod="native">
        <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="name" type="VARCHAR" size="100" required="true"/>
        <column name="group_id" type="INTEGER"/>

        <foreign-key foreignTable="issue730_group">
            <reference local="group_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
EOF;

        $quickBuilder = new QuickBuilder();
        $quickBuilder->setSchema($schema);
        $quickBuilder->setIdentifierQuoting(true);
        $platform = new SqlitePlatform();
        $quickBuilder->setPlatform($platform);

        $quickBuilder->build();

        $groupA = new Issue730Group();
        $groupA->setName('groupA');

        $departmentGroup = new Issue730DepartmentGroup();
        $departmentGroup->setName('my department');
        $departmentGroup->setIssue730Group($groupA);

        $this->assertEquals($groupA, $departmentGroup->getIssue730Group());

        $departmentGroups = $groupA->getIssue730DepartmentGroups();
        $this->assertCount(1, $departmentGroups);
        $this->assertEquals($departmentGroup, $departmentGroups->getFirst());

        $groupA->save();

        $departmentGroups = Issue730DepartmentGroupQuery::create()->filterByIssue730Group($groupA)->find();
        $this->assertCount(1, $departmentGroups);
        $this->assertEquals($departmentGroup, $departmentGroups->getFirst());
        $this->assertEquals('my department', $departmentGroups->getFirst()->getName());
    }
}
