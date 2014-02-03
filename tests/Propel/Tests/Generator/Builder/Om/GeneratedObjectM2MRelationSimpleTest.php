<?php

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Collection\ObjectCombinationCollection;
use Propel\Tests\Helpers\PlatformDatabaseBuildTimeBase;

/**
 * @group database
 */
class GeneratedObjectM2MRelationSimpleTest extends PlatformDatabaseBuildTimeBase
{
    protected $databaseName = 'migration';
    protected $connected = false;

    public function setUp()
    {
        parent::setUp();

        if (!class_exists('\Relation1UserFriendQuery')) {
            $schema = '
    <database name="migration" schema="migration">
        <table name="relation1_user_friend" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="friend_id" type="integer" primaryKey="true"/>

            <foreign-key foreignTable="relation1_user" phpName="Who">
                <reference local="user_id" foreign="id"/>
            </foreign-key>

            <foreign-key foreignTable="relation1_user" phpName="Friend">
                <reference local="friend_id" foreign="id"/>
            </foreign-key>
        </table>

        <table name="relation1_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name"/>
        </table>
    </database>
        ';

            $this->buildAndMigrate($schema);
        }
    }

    /**
     *
     *               addFriend | removeFriend | setFriends | getFriends
     *              +---------------------------------------------------
     * addFriend    |    1             2             3            4
     * removeFriend |    5             6             7            8
     * setFriends   |    9            10            11           12
     *
     */

    /*
     * ####################################
     * 1. addFriend, addFriend
     */
    public function test1()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

    }

    /*
     * ####################################
     * 2. addFriend, removeFriend
     */
    public function test2()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertEquals($hans, $friend1->getWhos()->getFirst(), 'Hans is friend1\'s friend.');
        $this->assertEquals($hans, $friend2->getWhos()->getFirst(), 'Hans is friend2\'s friend.');
        $hans->save();
        $this->assertCount(1, $friend1->getWhos(), 'Friend 1 is from one guy (hans) a friend');
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

        $hans->removeFriend($friend1);
        $this->assertCount(0, $friend1->getWhos(), 'Friend 1 is from nobody a friend');
        $this->assertEquals($hans, $friend2->getWhos()->getFirst(), 'Hans is friend2\'s friend.');
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->save();
        $this->assertCount(0, $friend1->getWhos(), 'Friend 1 is from nobody a friend');
        $this->assertEquals($hans, $friend2->getWhos()->getFirst(), 'Hans is friend2\'s friend.');

        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 2 as friend');

    }

    /*
     * ####################################
     * 3. addFriend, setFriends
     */
    public function test3()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');

        $friends = new ObjectCollection();
        $friends[] = $friend1;
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

    }

    /*
     * ####################################
     * 4. addFriend, getFriend
     */
    public function test4()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertCount(1, $hans->getFriends());

    }

    /*
     * ####################################
     * 5. removeFriend, addFriend
     */
    public function test5()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');
        $this->assertCount(1, $hans->getFriends());


        //db prepared.
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->removeFriend($friend1);
        $this->assertCount(0, $hans->getFriends());
        $hans->addFriend($friend2);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 2 as friend');


        //same with new instances.
        \Map\Relation1UserTableMap::clearInstancePool();
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');
        $friend2 = \Relation1UserQuery::create()->findOneByName('Friend 2');

        $this->assertSame($friend2, $newHansObject->getFriends()->getFirst());
        $this->assertCount(1, $newHansObject->getFriends());
        $newHansObject->removeFriend($friend2);
        $this->assertCount(0, $newHansObject->getFriends());
        $newHansObject->save();

        $newHansObject->addFriend($friend1);
        $this->assertCount(1, $newHansObject->getFriends());

        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($newHansObject)->findOne(), 'Hans has Friend 2 as friend');

    }

    /*
     * ####################################
     * 6. removeFriend, removeFriend
     */
    public function test6()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends());
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertCount(2, $hans->getFriends());

        //db prepared, work now with new objects.
        \Map\Relation1UserTableMap::clearInstancePool();
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');
        $friend2 = \Relation1UserQuery::create()->findOneByName('Friend 2');

        $newHansObject->removeFriend($friend1);
        $this->assertCount(1, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($newHansObject)->findOne(), 'Hans has Friend 2 as friend');


        $newHansObject->removeFriend($friend2);
        $this->assertCount(0, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(0, \Relation1UserFriendQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(0, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has zero friends.');

    }

    /*
     * ####################################
     * 7. removeFriend, setFriends
     */
    public function test7()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends());
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');
        $this->assertCount(1, $hans->getFriends());

        //db prepared, work now with new objects.
        \Map\Relation1UserTableMap::clearInstancePool();
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');

        $newHansObject->removeFriend($friend1);
        $this->assertCount(0, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(0, \Relation1UserFriendQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(0, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has zero friends.');

        $friends = new ObjectCollection();
        $friends[] = $friend1;
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $newHansObject->setFriends($friends);
        $this->assertCount(2, $newHansObject->getFriends());
        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has two friends.');

    }

    /*
     * ####################################
     * 8. removeFriend, getFriends
     */
    public function test8()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends());
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertCount(2, $hans->getFriends());

        //db prepared, work now with new objects.
        \Map\Relation1UserTableMap::clearInstancePool();
        /** @var \Relation1User $newHansObject */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend1 = \Relation1UserQuery::create()->findOneByName('Friend 1');
        $friend2 = \Relation1UserQuery::create()->findOneByName('Friend 2');

        $this->assertCount(2, $newHansObject->getFriends());
        $newHansObject->removeFriend($friend1);
        $this->assertCount(1, $newHansObject->getFriends());
        $this->assertEquals($friend2, $newHansObject->getFriends()->getFirst());
        $newHansObject->save();

        \Map\Relation1UserTableMap::clearInstancePool();
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $this->assertCount(1, $newHansObject->getFriends());

        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has one friend.');
        $this->assertEquals('Friend 2', \Relation1UserQuery::create()->filterByWho($newHansObject)->findOne()->getName(), 'Hans has Friend 2 as friend');

    }

    /*
     * ####################################
     * 9. setFriends, addFriend
     */
    public function test9()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

        $friend3 = (new \Relation1User())->setName('Friend 3');
        $hans->addFriend($friend3);
        $hans->save();
        $this->assertEquals(4, \Relation1UserQuery::create()->count(), 'We have four users.');
        $this->assertEquals(3, \Relation1UserFriendQuery::create()->count(), 'We have three connections.');
        $this->assertEquals(3, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has three friends.');;

    }

    /*
     * ####################################
     * 10. setFriends, removeFriend
     */
    public function test10()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');

        $hans->removeFriend($friend1);
        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');

    }

    /*
     * ####################################
     * 11. setFriends, setFriends
     */
    public function test11()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals($friends->getArrayCopy(), \Relation1UserQuery::create()->filterByWho($hans)->find()->getArrayCopy());


        $friends = new ObjectCollection();
        $friends[] = $friend3 = (new \Relation1User())->setName('Friend 3');
        $friends[] = $friend4 = (new \Relation1User())->setName('Friend 4');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->save();
        $this->assertEquals(5, \Relation1UserQuery::create()->count(), 'We have five users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals($friends->getArrayCopy(), \Relation1UserQuery::create()->filterByWho($hans)->find()->getArrayCopy());

    }

    /*
     * ####################################
     * 12. setFriends, getFriends
     */
    public function test12()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friends = new ObjectCollection();
        $friends[] = $friend1 = (new \Relation1User())->setName('Friend 1');
        $friends[] = $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->setFriends($friends);
        $this->assertCount(2, $hans->getFriends(), 'two friends');
        $hans->save();

        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have three users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals('Friend 1', \Relation1UserQuery::create()->filterByWho($hans)->findOne()->getName(), 'Hans\'s first friend is Friend 1.');

        \Map\Relation1UserTableMap::clearInstancePool();
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $this->assertCount(2, $newHansObject->getFriends(), 'two friends');

        $friends = new ObjectCollection();
        $friends[] = $friend3 = (new \Relation1User())->setName('Friend 3');
        $friends[] = $friend4 = (new \Relation1User())->setName('Friend 4');
        $newHansObject->setFriends($friends);
        $newHansObject->save();

        $this->assertEquals(5, \Relation1UserQuery::create()->count(), 'We have five users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has two friends.');
        $this->assertEquals('Friend 3', \Relation1UserQuery::create()->filterByWho($hans)->findOne()->getName(), 'Hans\'s first friend is Friend 3.');

    }

    /*
     * ####################################
     * Special: Add friend to db and fire addFriend on a new instance.
     */
    public function testAddOnNewInstance()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');

        //get new instance of $hans and fire addFriend
        \Map\Relation1UserTableMap::clearInstancePool();
        /** @var $newHansObject \Relation1User */
        $newHansObject = \Relation1UserQuery::create()->findOneByName('hans');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $friend2->save();
        $newHansObject->addFriend($friend2);
        $this->assertCount(2, $newHansObject->getFriends(), 'two friends');

        $newHansObject->save();
        $this->assertEquals(3, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(2, \Relation1UserFriendQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \Relation1UserQuery::create()->filterByWho($newHansObject)->count(), 'Hans has two friends.');

    }

    /*
     * ####################################
     * Special: addFriend same friend as the one in the database.
     */
    public function testAddAfterDB()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $hans->addFriend($friend1);
        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');

        //check if next addFriend
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');

    }

    /**
     * ####################################
     * Special: addFriend, addFriend, removeFriend
     *
     */
    public function testAddAddRemove()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->removeFriend($friend1);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend2, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 2 as friend');

    }

    /*
     * ####################################
     * Special: addFriend, addFriend, removeFriend different order
     */
    public function testAddAddRemoveDiffOrder()
    {
        \Relation1UserFriendQuery::create()->deleteAll();
        \Relation1UserQuery::create()->deleteAll();

        $hans = new \Relation1User();
        $hans->setName('hans');

        $friend1 = (new \Relation1User())->setName('Friend 1');
        $friend2 = (new \Relation1User())->setName('Friend 2');
        $hans->addFriend($friend1);
        $hans->addFriend($friend2);
        $this->assertCount(2, $hans->getFriends(), 'two friends');

        $hans->removeFriend($friend2);
        $this->assertCount(1, $hans->getFriends(), 'one friend');

        $hans->save();
        $this->assertEquals(2, \Relation1UserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \Relation1UserFriendQuery::create()->count(), 'We have one connection.');
        $this->assertEquals(1, \Relation1UserQuery::create()->filterByWho($hans)->count(), 'Hans has one friend.');
        $this->assertEquals($friend1, \Relation1UserQuery::create()->filterByWho($hans)->findOne(), 'Hans has Friend 1 as friend');

    }
}