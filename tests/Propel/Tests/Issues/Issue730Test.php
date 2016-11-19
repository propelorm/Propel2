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
<database name="default" defaultIdMethod="native" namespace="Tests\\Issue730\\" activeRecord="true">
    <entity name="Group" tableName="issue730_group">
        <field name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <field name="name" phpName="Name" type="VARCHAR" size="100" required="true"/>
    </entity>
    <entity tableName="issue730_department_group" idMethod="native" name="Group" namespace="\Tests\Issue730\Department">
        <field name="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <field name="name" type="VARCHAR" size="100" required="true"/>
        <field name="groupId" type="INTEGER"/>

        <relation target="Tests\\Issue730\\Group" field="group" refField="departmentGroup">
            <reference local="groupId" foreign="id"/>
        </relation>
    </entity>
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