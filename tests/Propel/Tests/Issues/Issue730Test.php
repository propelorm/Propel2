<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Issues;

use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCaseFixtures;

/**
 */
class Issue730Test extends TestCaseFixtures
{
    public function testNamespace()
    {
        $schema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="default" defaultIdMethod="native" namespace="Tests\\Issue730\\">
    <table name="issue730_group" idMethod="native" phpName="Group">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" required="true"/>
        <column name="name" phpName="Name" type="VARCHAR" size="100" required="true"/>
    </table>
    <table name="issue730_department_group" idMethod="native" phpName="Group" namespace="\Tests\Issue730\Department">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="name" type="VARCHAR" size="100" required="true"/>
        <column name="group_id" phpName="GroupId" type="INTEGER"/>

        <foreign-key foreignTable="issue730_group" phpName="Group" refPhpName="DepartmentGroup">
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

        $groupA = new \Tests\Issue730\Group();
        $groupA->setName('groupA');

        $departmentGroup = new \Tests\Issue730\Department\Group();
        $departmentGroup->setName('my department');
        $departmentGroup->setGroup($groupA);

        $this->assertEquals($groupA, $departmentGroup->getGroup());

        $departmentGroups = $groupA->getDepartmentGroups();
        $this->assertCount(1, $departmentGroups);
        $this->assertEquals($departmentGroup, $departmentGroups->getFirst());

        $groupA->save();

        $departmentGroups = \Tests\Issue730\Department\GroupQuery::create()->filterByGroup($groupA)->find();
        $this->assertCount(1, $departmentGroups);
        $this->assertEquals($departmentGroup, $departmentGroups->getFirst());
        $this->assertEquals('my department', $departmentGroups->getFirst()->getName());
    }
}