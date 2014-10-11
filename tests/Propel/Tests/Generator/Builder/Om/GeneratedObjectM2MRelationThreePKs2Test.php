<?php

namespace Propel\Tests\Generator\Builder\Om;

use Base\RelationpkUserGroupQuery;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Collection\ObjectCombinationCollection;
use Propel\Tests\Helpers\PlatformDatabaseBuildTimeBase;

/**
 * Tests for a M2M relation with three pks where first two are FKs and third not.
 * Special: Second FK has two local refs as PK.
 *
 * @group database
 */
class GeneratedObjectM2MRelationThreePKs2Test extends PlatformDatabaseBuildTimeBase
{
    protected $databaseName = 'migration';

    public function setUp()
    {
        parent::setUp();

        if (!class_exists('\RelationpkUserQuery')) {
            $schema = '
    <database name="migration" schema="migration">
        <table name="relationpk_user_group" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="group_id" type="integer" primaryKey="true"/>
            <column name="group_type" size="64" type="varchar" primaryKey="true"/>
            <column name="position" type="varchar" size="64" primaryKey="true"/>

            <foreign-key foreignTable="relationpk_user" phpName="User" onDelete="cascade">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relationpk_group" phpName="Group" onDelete="cascade">
                <reference local="group_id" foreign="id"/>
                <reference local="group_type" foreign="type"/>
            </foreign-key>
        </table>

        <table name="relationpk_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relationpk_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="type" type="varchar" size="64" primaryKey="true" default="standalone"/>
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

        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $hans->addGroup($admins, 'standard');
        $this->assertCount(1, $hans->getGroupPositions());
        $this->assertEquals([$admins, 'standard'], $hans->getGroupPositions()->getFirst());

        $this->assertCount(1, $admins->getUserPositions());
        $this->assertEquals([$hans, 'standard'], $admins->getUserPositions()->getFirst());

        $hans->save();
        $this->assertEquals([$admins], iterator_to_array($hans->getGroups('standard')));

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        $hans->addGroup($admins, 'lead');
        $this->assertCount(2, $hans->getGroupPositions());
        $this->assertCount(2, $admins->getUserPositions());
        $hans->save();

        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        //try to add the same combination on a new instance
        \Map\RelationpkUserTableMap::clearInstancePool();
        /** @var $newHansObject \RelationpkUser */
        $newHansObject = \RelationpkUserQuery::create()->findOneByName('hans');

        $newHansObject->addGroup($admins, 'lead');
        $this->assertCount(2, $newHansObject->getGroupPositions());
        $this->assertCount(2, $admins->getUserPositions());
        $this->assertEquals([[$hans, 'standard'], [$hans, 'lead']], iterator_to_array($admins->getUserPositions()));
        $newHansObject->save();

        $collection = $newHansObject->getGroupPositions(RelationpkUserGroupQuery::create()->filterByPosition('lead'));
        $this->assertCount(1, $collection);
        $this->assertCount(1, $collection->getObjectsFromPosition(1));
        $this->assertEquals('Admins', $collection->getObjectsFromPosition(1)[0]->getName());
        $this->assertEquals('lead', $collection->getObjectsFromPosition(2)[0]);

        $this->assertEquals('Admins', $collection->getFirst()[0]->getName());
        $this->assertCount(1, $newHansObject->getGroups('lead'));
        $this->assertCount(2, $newHansObject->getGroups());
        $this->assertEquals(1, $newHansObject->countGroups('lead'));
        $this->assertEquals(2, $newHansObject->countGroups());
        $this->assertEquals('Admins', $newHansObject->getGroups('lead')->getFirst()->getName());

        $this->assertCount(2, \RelationpkUserQuery::create()->filterByGroup($admins)->find());
        $this->assertCount(
            1,
            \RelationpkUserQuery::create()
                ->useRelationpkUserGroupQuery()
                    ->filterByPosition('lead')
                ->endUse()
                ->find()
        );
        $this->assertCount(
            1,
            \RelationpkUserQuery::create()
                ->useRelationpkUserGroupQuery()
                    ->filterByPosition('standard')
                ->endUse()
                ->find()
        );
        $this->assertEquals(
            'hans',
            \RelationpkUserQuery::create()
                ->useRelationpkUserGroupQuery()
                    ->filterByPosition('standard')
                ->endUse()
                ->find()
                ->getFirst()
                ->getName()
        );
        $this->assertCount(
            0,
            \RelationpkUserQuery::create()
                ->useRelationpkUserGroupQuery()
                    ->filterByPosition('not existent')
                ->endUse()
                ->find()
        );

        $this->assertCount(1, \RelationpkUserGroupQuery::create()
            ->filterByPosition('lead')
            ->filterByGroup($admins)
            ->find());

        $this->assertCount(2, \RelationpkUserGroupQuery::create()
            ->filterByGroup($admins)
            ->find());

        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');
    }


    /*
     * ####################################
     * 2. add, remove
     */
    public function test2()
    {
        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $hans->addGroup($admins, 'trainee');
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        $hans->removeGroupPosition($admins, 'trainee');
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \RelationpkUserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

    }

    /*
     * ####################################
     * 3. add, set
     */
    public function test3()
    {
        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $hans->addGroup($admins, 'trainee');
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        $emptyColl = new ObjectCombinationCollection();
        $hans->setGroupPositions($emptyColl);
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \RelationpkUserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');
    }

    /*
     * ####################################
     * 4. and 5. add, get
     */
    public function test4_5()
    {
        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $hans->addGroup($admins, 'trainee');
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        \Map\RelationpkUserTableMap::clearInstancePool();
        $newHansObject = \RelationpkUserQuery::create()->findOneByName('hans');
        $this->assertCount(1, $newHansObject->getGroupPositions(), 'Makes a extra query and returns the correct one group list');

        \Map\RelationpkUserTableMap::clearInstancePool();
        $newHansObject = \RelationpkUserQuery::create()->findOneByName('hans');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $newHansObject->addGroup($cleaner, 'chef');
        $this->assertCount(2, $newHansObject->getGroupPositions(), 'getGroupPositions makes a query and adds then the added group, thus we have 2');
        $newHansObject->save();

        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');
    }

    /*
     * ####################################
     * 6. remove, remove
     */
    public function test6()
    {
        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $hans->addGroup($admins, 'trainee');
        $hans->addGroup($cleaner, 'chef');
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        $hans->removeGroupPosition($admins, 'trainee');
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        $hans->removeGroupPosition($cleaner, 'chef');
        $this->assertCount(0, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(0, \RelationpkUserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        //add two groupPositions again and made the same removing with a complete new object instance each time
        $hans->addGroup($admins, 'trainee');
        $hans->addGroup($cleaner, 'chef');
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();
        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        \Map\RelationpkUserTableMap::clearInstancePool();
        $newHansObject = \RelationpkUserQuery::create()->findOneByName('hans');

        $newHansObject->removeGroupPosition($admins, 'trainee');
        $this->assertCount(1, $newHansObject->getGroupPositions());
        $newHansObject->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        \Map\RelationpkUserTableMap::clearInstancePool();
        /** @var $newHansObject \RelationpkUser */
        $newHansObject = \RelationpkUserQuery::create()->findOneByName('hans');
        $newHansObject->removeGroupPosition($cleaner, 'chef');
        $this->assertCount(0, $newHansObject->getGroupPositions());
        $newHansObject->save();

        $this->assertEquals(0, \RelationpkUserGroupQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');
    }

    /**
     * 7. remove, set
     */
    public function test7(){

        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $hans->addGroup($admins, 'trainee');
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        $hans->removeGroupPosition($admins, 'trainee');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $col = new ObjectCombinationCollection();
        $col[] = [$cleaner, 'chef'];
        $hans->setGroupPositions($col);
        $this->assertCount(1, $hans->getGroupPositions());
        $this->assertEquals([$cleaner, 'chef'], $hans->getGroupPositions()->getFirst());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have still one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        $userGroup = \RelationpkUserGroupQuery::create()->filterByUser($hans)->findOne();
        $this->assertEquals('chef', $userGroup->getPosition());
        $this->assertEquals($cleaner->getId(), $userGroup->getGroupId());
    }

    /**
     * 8. remove, get
     */
    public function test8(){

        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $hans->addGroup($admins, 'trainee');
        $hans->addGroup($cleaner, 'chef');
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have still two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        $hans->removeGroupPosition($admins, 'trainee');
        $hans->save();

        \Map\RelationpkUserTableMap::clearInstancePool();
        $newHansObject = \RelationpkUserQuery::create()->findOneByName('hans');
        $this->assertCount(1, $newHansObject->getGroupPositions());
    }

    /**
     * 9. set, add
     */
    public function test9(){

        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $col = new ObjectCombinationCollection();
        $col->push($admins, 'trainee');
        $hans->setGroupPositions($col);
        $hans->addGroup($cleaner, 'chef');
        $this->assertCount(2, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(2, \RelationpkUserGroupQuery::create()->count(), 'We have still two connections.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');
    }

    /**
     * 10. set, remove
     */
    public function test10(){

        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $col = new ObjectCombinationCollection();
        $col->push($admins, 'trainee');
        $col->push($cleaner, 'chef');
        $hans->setGroupPositions($col);

        $hans->removeGroupPosition($admins, 'trainee');
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');
    }

    /**
     * 11-12. set, set - set, get
     */
    public function test11_12(){

        \RelationpkUserQuery::create()->deleteAll();
        \RelationpkGroupQuery::create()->deleteAll();
        \RelationpkUserGroupQuery::create()->deleteAll();

        $hans = new \RelationpkUser();
        $hans->setName('hans');

        $admins = new \RelationpkGroup();
        $admins->setName('Admins');

        $col = new ObjectCombinationCollection();
        $col->push($admins, 'trainee');
        $hans->setGroupPositions($col);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpkGroupQuery::create()->count(), 'We have one group.');

        $cleaner = new \RelationpkGroup();
        $cleaner->setName('Cleaner');

        $col = new ObjectCombinationCollection();
        $col->push($cleaner, 'chef');
        $hans->setGroupPositions($col);
        $this->assertCount(1, $hans->getGroupPositions());
        $hans->save();

        $this->assertEquals(1, \RelationpkUserGroupQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \RelationpkUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(2, \RelationpkGroupQuery::create()->count(), 'We have two groups.');

        $userGroup = \RelationpkUserGroupQuery::create()->filterByUser($hans)->findOne();
        $this->assertEquals('chef', $userGroup->getPosition());
        $this->assertEquals($cleaner->getId(), $userGroup->getGroupId());
    }
}