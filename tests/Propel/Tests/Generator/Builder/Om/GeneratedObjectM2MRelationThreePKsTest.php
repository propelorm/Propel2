<?php

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Collection\ObjectCombinationCollection;
use Propel\Tests\Helpers\PlatformDatabaseBuildTimeBase;

/**
 * Tests for a M2M relation with three pks where each is a FK.
 *
 * @group database
 */
class GeneratedObjectM2MRelationThreePKsTest extends PlatformDatabaseBuildTimeBase
{
    protected $databaseName = 'migration';

    public function setUp()
    {
        parent::setUp();

        if (!class_exists('\Relation2UserQuery')) {
            $schema = '
    <database name="migration" schema="migration">
        <table name="relation2_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="position_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation2_user" phpName="User" onDelete="cascade">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation2_group" phpName="Group" onDelete="cascade">
                <reference local="group_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation2_position" phpName="Position" onDelete="cascade">
                <reference local="position_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation2_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation2_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation2_position">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>
    </database>
        ';

            $this->buildAndMigrate($schema);
        }
    }

    /**
     *
     *           add     |    remove     |    set     |    get
     *        +---------------------------------------------------
     * add    |    1             2             3            4
     * remove |    5             6             7            8
     * set    |    9            10            11           12
     *
     */



    /*
     * ####################################
     * 1. add, add
     */
    public function test1()
    {

        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $this->assertCount(1, $admins->getUserPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $positionLead = new \Relation2Position();
        $positionLead->setName('Lead');
        $hans->addGroup($admins, $positionLead);
        $this->assertCount(2, $hans->getGroupPositions());
        $this->assertCount(2, $admins->getUserPositions());
        $hans->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        //try to add the same combination on a new instance
        \Map\Relation2UserTableMap::clearInstancePool();
        /** @var $newHansObject \Relation2User */
        $newHansObject = \Relation2UserQuery::create()->findOneByName('hans');

        $newHansObject->addGroup($admins, $positionLead);
        $this->assertCount(2, $newHansObject->getGroupPositions());
        $this->assertCount(2, $admins->getUserPositions()); //wrong
        $newHansObject->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');
    }


    /*
     * ####################################
     * 2. add, remove
     */
    public function test2()
    {
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $hans->removeGroupPosition($admins, $position);
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \Relation2UserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

    }

    /*
     * ####################################
     * 3. add, set
     */
    public function test3()
    {
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $emptyColl = new ObjectCombinationCollection();
        $hans->setGroupPositions($emptyColl);
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \Relation2UserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');
    }

    /*
     * ####################################
     * 4. and 5. add, get
     */
    public function test4_5()
    {
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');
        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        \Map\Relation2UserTableMap::clearInstancePool();
        $newHansObject = \Relation2UserQuery::create()->findOneByName('hans');
        $this->assertCount(1, $newHansObject->getGroupPositions(), 'Makes a extra query and returns the correct one group list');

        \Map\Relation2UserTableMap::clearInstancePool();
        $newHansObject = \Relation2UserQuery::create()->findOneByName('hans');

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');

        $newHansObject->addGroup($cleaner, $position2);
        $this->assertCount(2, $newHansObject->getGroupPositions(), 'getGroupPositions makes a query and adds then the added group, thus we have 2');
        $newHansObject->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');
    }

    /*
     * ####################################
     * 6. remove, remove
     */
    public function test6()
    {
        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');

        $hans->addGroup($admins, $position);
        $hans->addGroup($cleaner, $position2);
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        $hans->removeGroupPosition($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        $hans->removeGroupPosition($cleaner, $position2);
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \Relation2UserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        //add two groupPositions again and made the same removing with a complete new object instance each time
        $hans->addGroup($admins, $position);
        $hans->addGroup($cleaner, $position2);
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();
        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        \Map\Relation2UserTableMap::clearInstancePool();
        $newHansObject = \Relation2UserQuery::create()->findOneByName('hans');

        $newHansObject->removeGroupPosition($admins, $position);
        $this->assertCount(1, $newHansObject->getGroupPositions());
        $newHansObject->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        \Map\Relation2UserTableMap::clearInstancePool();
        $newHansObject = \Relation2UserQuery::create()->findOneByName('hans');
        $newHansObject->removeGroupPosition($cleaner, $position2);
        $this->assertCount(0, $newHansObject->getGroupPositions());
        $newHansObject->save();

        $this->assertEquals(0, \Relation2UserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');
    }

    /**
     * 7. remove, set
     */
    public function test7(){

        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $hans->addGroup($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $hans->removeGroupPosition($admins, $position);

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');
        $col = new ObjectCombinationCollection();
        $col[] = [$cleaner, $position2];
        $hans->setGroupPositions($col);
        $this->assertCount(1, $hans->getGroupPositions());
        $this->assertEquals([$cleaner, $position2], $hans->getGroupPositions()->getFirst());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have still one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        $userGroup = \Relation2UserGroupQuery::create()->filterByUser($hans)->findOne();
        $this->assertEquals($position2->getId(), $userGroup->getPositionId());
        $this->assertEquals($cleaner->getId(), $userGroup->getGroupId());
    }

    /**
     * 8. remove, get
     */
    public function test8(){

        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');

        $hans->addGroup($admins, $position);
        $hans->addGroup($cleaner, $position2);
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have still two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        $hans->removeGroupPosition($admins, $position);
        $hans->save();

        \Map\Relation2UserTableMap::clearInstancePool();
        $newHansObject = \Relation2UserQuery::create()->findOneByName('hans');
        $this->assertCount(1, $newHansObject->getGroupPositions());
    }

    /**
     * 9. set, add
     */
    public function test9(){

        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');

        $col = new ObjectCombinationCollection();
        $col->push($admins, $position);
        $hans->setGroupPositions($col);
        $hans->addGroup($cleaner, $position2);
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \Relation2UserGroupQuery::create()->count(), 'We have still two connections.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');
    }

    /**
     * 10. set, remove
     */
    public function test10(){

        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');

        $col = new ObjectCombinationCollection();
        $col->push($admins, $position);
        $col->push($cleaner, $position2);
        $hans->setGroupPositions($col);

        $hans->removeGroupPosition($admins, $position);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');
    }

    /**
     * 11-12. set, set - set, get
     */
    public function test11_12(){

        \Relation2UserQuery::create()->deleteAll();
        \Relation2GroupQuery::create()->deleteAll();
        \Relation2UserGroupQuery::create()->deleteAll();
        \Relation2PositionQuery::create()->deleteAll();

        $hans = new \Relation2User();
        $hans->setName('hans');

        $admins = new \Relation2Group();
        $admins->setName('Admins');

        $position = new \Relation2Position();
        $position->setName('Trainee');

        $col = new ObjectCombinationCollection();
        $col->push($admins, $position);
        $hans->setGroupPositions($col);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation2GroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \Relation2PositionQuery::create()->count(), 'We have one position.');

        $cleaner = new \Relation2Group();
        $cleaner->setName('Cleaner');

        $position2 = new \Relation2Position();
        $position2->setName('Chef');

        $col = new ObjectCombinationCollection();
        $col->push($cleaner, $position2);
        $hans->setGroupPositions($col);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \Relation2UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation2UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \Relation2GroupQuery::create()->count(), 'We have two groups.');
        $this->assertEquals(2, \Relation2PositionQuery::create()->count(), 'We have two positions.');

        $userGroup = \Relation2UserGroupQuery::create()->filterByUser($hans)->findOne();
        $this->assertEquals($position2->getId(), $userGroup->getPositionId());
        $this->assertEquals($cleaner->getId(), $userGroup->getGroupId());
    }


    public function testRelationThree3()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation3_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>
            <column name="relation_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation3_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation3_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>

            <foreign-key foreignTable="relation3_relation" phpName="Relation">
                <reference local="relation_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation3_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation3_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

        <table name="relation3_relation">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>
    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation3UserGroupQuery::create()->deleteAll();
        \Relation3UserQuery::create()->deleteAll();
        \Relation3GroupQuery::create()->deleteAll();
        \Relation3RelationQuery::create()->deleteAll();

        $hans = new \Relation3User();
        $hans->setName('hans');

        $admins = new \Relation3Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $relation = new \Relation3Relation();
        $relation->setName('Leader');

        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');
        $hans->addGroup($admins, $relation);
        $this->assertCount(1, $hans->getGroupRelations(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation3UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation3UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation3UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, $relation);
        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation3UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation3UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation3UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');

    }


    public function testRelationThree4()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation4_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>
            <column name="relation" type="varchar" primaryKey="true"/>

            <foreign-key foreignTable="relation4_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation4_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>
        </table>

        <table name="relation4_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation4_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation4UserGroupQuery::create()->deleteAll();
        \Relation4UserQuery::create()->deleteAll();
        \Relation4GroupQuery::create()->deleteAll();

        $hans = new \Relation4User();
        $hans->setName('hans');

        $admins = new \Relation4Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');
        $hans->addGroup($admins, 'teamLeader');
        $this->assertCount(1, $hans->getGroupRelations(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation4UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation4UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation4UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, 'teamLeader');
        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation4UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation4UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation4UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');
    }

    public function testRelationThree5()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation5_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="relation" type="varchar" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation5_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>

            <foreign-key foreignTable="relation5_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation5_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation5_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation5UserGroupQuery::create()->deleteAll();
        \Relation5UserQuery::create()->deleteAll();
        \Relation5GroupQuery::create()->deleteAll();

        $hans = new \Relation5User();
        $hans->setName('hans');

        $admins = new \Relation5Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');
        $hans->addGroup($admins, 'teamLeader');
        $this->assertCount(1, $hans->getGroupRelations(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation5UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation5UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation5UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroupRelation($admins, 'teamLeader');
        $this->assertCount(0, $hans->getGroupRelations(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation5UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation5UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation5UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');
    }

    public function testRelationThree6()
    {
        $schema = '
    <database name="migration" schema="migration">
        <table name="relation6_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_id2" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation6_group" phpName="Group">
                <reference local="group_id" foreign="id"/>
                <reference local="group_id2" foreign="id2"/>
            </foreign-key>

            <foreign-key foreignTable="relation6_user" phpName="User">
                <reference local="user_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation6_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relation6_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="id2" type="integer" primaryKey="true"/>
            <column name="name" />
        </table>

    </database>
        ';

        $this->buildAndMigrate($schema);

        \Relation6UserGroupQuery::create()->deleteAll();
        \Relation6UserQuery::create()->deleteAll();
        \Relation6GroupQuery::create()->deleteAll();

        $hans = new \Relation6User();
        $hans->setName('hans');

        $admins = new \Relation6Group();
        $admins->setName('Admins');
        $admins->setId2(1);

        $this->assertCount(0, $hans->getGroups(), 'no groups');
        $hans->addGroup($admins);
        $this->assertCount(1, $hans->getGroups(), 'one groups');

        $hans->save();

        $this->assertEquals(1, \Relation6UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \Relation6UserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation6UserQuery::create()->filterByGroup($admins)->count(), 'Hans has one group.');

        $hans->removeGroup($admins);
        $this->assertCount(0, $hans->getGroups(), 'no groups');

        $hans->save();

        $this->assertEquals(1, \Relation6UserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(0, \Relation6UserGroupQuery::create()->count(), 'We have zero connection.');
        $this->assertEquals(0, \Relation6UserQuery::create()->filterByGroup($admins)->count(), 'Hans has zero groups.');
    }

}