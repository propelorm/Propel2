<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use TestGroupObject;
use TestUserObject;
use TestGroupObjectNegative;
use TestUserObjectNegative;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Regression test for https://github.com/propelorm/Propel2/pull/1994
 */
class IssueIsCrossRefTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\TestUserGroupObject')) {
            $schema = <<<EOF
<database>
    <table name="test_group_object">
        <column name="id" type="integer" primaryKey="true" required="true" />
    </table>
    <table name="test_user_object">
        <column name="id" type="integer" primaryKey="true" required="true" />
    </table>
    <table name="test_group_object_negative">
        <column name="id" type="integer" primaryKey="true" required="true" />
    </table>
    <table name="test_user_object_negative">
        <column name="id" type="integer" primaryKey="true" required="true" />
    </table>
    <table name="test_user_group_object" isCrossRef="true">
        <column name="id" type="integer" primaryKey="true" required="true" />
        <column name="user_id" type="integer" required="true"/>
        <column name="group_id" type="integer" required="true"/>
        <foreign-key foreignTable="test_user_object">
            <reference local="user_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="test_group_object">
            <reference local="group_id" foreign="id"/>
        </foreign-key>
    </table>
    <table name="test_user_group_object_negative" isCrossRef="true">
        <column name="id" type="integer" primaryKey="true" required="true" />
        <column name="user_negative_id" type="integer"/>
        <column name="group_negative_id" type="integer"/>
        <foreign-key foreignTable="test_user_object_negative">
            <reference local="user_negative_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="test_group_object_negative">
            <reference local="group_negative_id" foreign="id"/>
        </foreign-key>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testGenerateIsCrossRefCode()
    {
        $testGroupObject = new TestGroupObject();
        $testUserObject = new TestUserObject();
        $testGroupNegative = new TestGroupObjectNegative();
        $testUserNegative = new TestUserObjectNegative();

        $this->assertTrue(
            method_exists($testGroupObject, 'createTestUserObjectsQuery'),
            'Class does not have method createTestUserObjectsQuery'
        );
        $this->assertTrue(
            method_exists($testUserObject, 'createTestGroupObjectsQuery'),
            'Class does not have method createTestUserObjectsQuery'
        );
        $this->assertFalse(
            method_exists($testGroupNegative, 'createTestUserObjectNegativesQuery'),
            'Class does not have method createTestUserObjectsQuery'
        );
        $this->assertFalse(
            method_exists($testUserNegative, 'createTestGroupObjectNegativesQuery'),
            'Class does not have method createTestUserObjectsQuery'
        );
    }
}
