<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\OutputGroup;

use Propel\Generator\Behavior\OutputGroup\ObjectCollectionWithOutputGroups;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 */
class OutputGroupBehaviorTest extends TestCase
{
    /** @var \Propel\Tests\Generator\Behavior\OutputGroup\OgEmployeeAccount */
    protected $account;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!class_exists('OgEmployee')) {
            static::buildLocalSchemaClasses();
        }
    }

    public static function buildLocalSchemaClasses(): void
    {
        $schema = <<<EOF
<database schema="output_group_test" namespace="\Propel\Tests\Generator\Behavior\OutputGroup">

    <behavior name="output_group"/>

    <table name="og_employee">
        <column name="id" type="INTEGER" primaryKey="true" outputGroup="short"/>
        <column name="name" type="VARCHAR" size="32" outputGroup="short"/>
        <column name="job_title" type="VARCHAR" size="32"/>
        <column name="supervisor_id" type="INTEGER"/>
        <column name="photo" type="BLOB" lazyLoad="true"/>
    </table>

    <table name="og_account" reloadOnInsert="true" reloadOnUpdate="true">
        <column name="employee_id" type="INTEGER" primaryKey="true" outputGroup="public,short"/>
        <column name="login" type="VARCHAR" size="32" outputGroup="public,short"/>
        <column name="password" type="VARCHAR" size="100" default="'@''34&quot;"/>
        <column name="enabled" type="BOOLEAN" default="true"/>
        <column name="not_enabled" type="BOOLEAN" default="false"/>
        <column name="created" type="TIMESTAMP" defaultExpr="CURRENT_TIMESTAMP" outputGroup="public"/>
        <column name="updated" type="TIMESTAMP" defaultExpr="CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"/>
        <column name="role_id" type="INTEGER" required="false" default="null" outputGroup="public"/>
        <column name="authenticator" type="VARCHAR" size="32" defaultExpr="'Password'" outputGroup="public"/>

        <foreign-key foreignTable="og_employee" onDelete="cascade" outputGroup="public,short" refOutputGroup="short">
            <reference local="employee_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="og_role" onDelete="setnull" outputGroup="public">
            <reference local="role_id" foreign="id"/>
        </foreign-key>
        <unique>
            <unique-column name="login"/>
        </unique>
    </table>

    <table name="og_log">
        <column name="id" type="INTEGER" primaryKey="true" outputGroup="public"/>
        <column name="uid" type="VARCHAR" size="32" required="true"/>
        <column name="message" type="VARCHAR" size="255" outputGroup="public,short"/>
        <foreign-key foreignTable="og_account" onDelete="cascade" refOutputGroup="public">
            <reference local="uid" foreign="login"/>
        </foreign-key>
        <index>
            <index-column name="id"/>
            <index-column name="uid"/>
        </index>
        <unique>
            <unique-column name="uid"/>
            <unique-column name="message"/>
        </unique>
    </table>

    <table name="og_role">
        <column name="id" type="INTEGER" primaryKey="true"/>
        <column name="name" type="VARCHAR" size="25" required="true"/>
    </table>

</database>
EOF;
        QuickBuilder::buildSchema($schema);
    }

    public function getPopulatedAccountObject()
    {
        $employee = (new OgEmployee())->fromArray([
            'Id' => 1,
            'Name' => 'le name',
            'JobTitle' => 'Manger',
        ]);
        $account = (new OgAccount())->fromArray([
            'EmployeeId' => 1,
            'Login' => 'le login',
            'Password' => 'le password',
            'Enabled' => true,
            'NotEnabled' => false,
            'Created' => '2024-04-18 11:52:13.533707',
            'RoleId' => 5,
            'Authenticator' => 'Password',
        ]);
        $role = (new OgRole())->fromArray([
            'Id' => 5,
            'Name' => 'le role name',
        ]);
        $logs = array_map(fn($i) => (new OgLog())->fromArray([
            'Id' => $i,
            'Uid' => 1, // fk to account id
            'Message' => 'le message ' . $i,
        ]), range(1, 2));

        $account->setOgRole($role);
        $account->setOgEmployee($employee);
        array_map(fn($log) => $account->addOgLog($log), $logs);

        return $account;
    }

    public function outputGroupDataProvider()
    {
        $accountShort = [
            'EmployeeId' => 1,
            'Login' => 'le login',
        ];
        $accountPublic = [
            ...$accountShort,
            'Created' => '2024-04-18 11:52:13.533707',
            'RoleId' => 5,
            'Authenticator' => 'Password',
        ];

        $employeeShort = [
            'Id' => 1,
            'Name' => 'le name',
        ];

        $employeePublic = [
            ...$employeeShort,
            'JobTitle' => 'Manger',
            'SupervisorId' => null,
            'Photo' => null,
        ];

        $role = [
            'Id' => 5,
            'Name' => 'le role name',
        ];

        $logsPublic = [
            ['Id' => 1, 'Message' => 'le message 1'],
            ['Id' => 2, 'Message' => 'le message 2'],
        ];
        $logsShort = [
            ['Message' => 'le message 1'],
            ['Message' => 'le message 2'],
        ];

        return [
            [
                'public',
                [
                    ...$accountPublic,
                    'OgEmployee' => $employeePublic,
                    'OgRole' => $role,
                    'OgLogs' => $logsPublic,
                ],
            ],
            [
                'short',
                [
                    ...$accountShort,
                    'OgEmployee' => $employeeShort,
                ],
            ],
            [
                [
                    OgAccount::class => 'public',
                    OgEmployee::class => 'public',
                    'default' => 'short',
                ],
                [
                    ...$accountPublic,
                    'OgEmployee' => $employeePublic,
                    'OgRole' => $role,
                    'OgLogs' => $logsShort,
                ],
            ],
        ];
    }

    /**
     * @dataProvider outputGroupDataProvider
     *
     * @return void
     */
    public function testOutputGroup($outputGroup, array $expected)
    {
        $account = $this->getPopulatedAccountObject();
        $output = $account->toOutputGroup($outputGroup);

        $this->assertEquals($expected, $output);
    }

    /**
     * @dataProvider outputGroupDataProvider
     *
     * @return void
     */
    public function testUpdatedCollectionClassName()
    {
        $collectionClass = Map\OgEmployeeTableMap::getTableMap()->getCollectionClassName();

        $this->assertEquals(ObjectCollectionWithOutputGroups::class, $collectionClass);
    }
}
