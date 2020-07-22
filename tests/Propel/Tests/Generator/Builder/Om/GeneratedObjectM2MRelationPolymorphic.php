<?php namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Collection\ObjectCombinationCollection;
use Propel\Tests\Helpers\PlatformDatabaseBuildTimeBase;


class GeneratedObjectM2MRelationPolymorphic extends PlatformDatabaseBuildTimeBase
{
    /**
     * Tests for a M2M relation with three pks where each is a FK.
     *
     * @group database
     */
    protected $databaseName = 'migration';

    public function setUp()
    {
        parent::setUp();

        if (!class_exists('\RelationPMUserQuery')) {
            $schema = '
    <database name="migration" schema="migration">
        <table name="relationpm_user_child" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="child_type" primaryKey="true"/>
            <column name="child_id" type="integer" primaryKey="true"/>       

            <foreign-key foreignTable="relationpm_user" phpName="User" onDelete="cascade">
                <reference local="user_id" foreign="id"/>
            </foreign-key>            

            <foreign-key foreignTable="relationpm_position" phpName="Position" onDelete="cascade">
                <reference local="child_id" foreign="id"/>
                <reference local="child_type" value="position"/>
            </foreign-key>                        
            
            <foreign-key foreignTable="relationpm_group" phpName="Group" onDelete="cascade">
                <reference local="child_id" foreign="id"/>
                <reference local="child_type" value="group"/>
            </foreign-key>
        </table>

        <table name="relationpm_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relationpm_group">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>        

        <table name="relationpm_position">
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
        \RelationpmUserQuery::create()->deleteAll();
        \RelationpmGroupQuery::create()->deleteAll();
        \RelationpmUserChildQuery::create()->deleteAll();
        \RelationpmPositionQuery::create()->deleteAll();

        $hans = new \RelationpmUser();
        $hans->setName('hans');

        $admins = new \RelationpmGroup();
        $admins->setName('Admins');

        $position = new \RelationpmPosition();
        $position->setName('Trainee');

        $hans->addGroup($admins);
        $hans->addPosition($position);
        $this->assertCount(1, $hans->getGroups());
        $this->assertCount(1, $hans->getPositions());
        $this->assertCount(1, $admins->getUsers());
        $this->assertCount(1, $position->getUsers());
        $hans->save();

        $this->assertEquals(2, \RelationpmUserChildQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \RelationpmPositionQuery::create()->count(), 'We have one position.');

        $positionLead = new \RelationpmPosition();
        $positionLead->setName('Lead');
        $hans->addPosition($positionLead);
        $this->assertCount(1, $hans->getGroups());
        $this->assertCount(2, $hans->getPositions());
        $this->assertCount(1, $admins->getUsers());
        $this->assertCount(1, $positionLead->getUsers());
        $hans->save();

        $this->assertEquals(3, \RelationpmUserChildQuery::create()->count(), 'We have three connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(2, \RelationpmPositionQuery::create()->count(), 'We have two positions.');

        //try to add the same combination on a new instance
        \Map\RelationpmUserTableMap::clearInstancePool();
        /** @var $newHansObject \RelationpmUser */
        $newHansObject = \RelationpmUserQuery::create()->findOneByName('hans');

        $newHansObject->addGroup($admins);
        $newHansObject->addPosition($position);
        $this->assertCount(1, $newHansObject->getGroups());
        $this->assertCount(2, $newHansObject->getPositions());
        $this->assertCount(1, $admins->getUsers());
        $this->assertCount(1, $position->getUsers());
        $newHansObject->save();

        $this->assertEquals(3, \RelationpmUserChildQuery::create()->count(), 'We have three connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(2, \RelationpmPositionQuery::create()->count(), 'We have two positions.');
    }


    /*
     * ####################################
     * 2. add, remove
     */
    public function test2()
    {
        \RelationpmUserQuery::create()->deleteAll();
        \RelationpmGroupQuery::create()->deleteAll();
        \RelationpmUserChildQuery::create()->deleteAll();
        \RelationpmPositionQuery::create()->deleteAll();

        $hans = new \RelationpmUser();
        $hans->setName('hans');

        $admins = new \RelationpmGroup();
        $admins->setName('Admins');

        $position = new \RelationpmPosition();
        $position->setName('Trainee');

        $hans->addGroup($admins);
        $hans->addPosition($position);
        $this->assertCount(1, $hans->getGroups());
        $this->assertCount(1, $hans->getPositions());
        $hans->save();

        $this->assertEquals(2, \RelationpmUserChildQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \RelationpmPositionQuery::create()->count(), 'We have one position.');

        $hans->removeGroup($admins);
        $hans->removePosition($position);
        $this->assertCount(0, $hans->getGroups());
        $this->assertCount(0, $hans->getPositions());
        $hans->save();

        $this->assertEquals(0, \RelationpmUserChildQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \RelationpmPositionQuery::create()->count(), 'We have one position.');

    }

    /*
     * ####################################
     * 3. add, set
     */
    public function test3()
    {
        \RelationpmUserQuery::create()->deleteAll();
        \RelationpmGroupQuery::create()->deleteAll();
        \RelationpmUserChildQuery::create()->deleteAll();
        \RelationpmPositionQuery::create()->deleteAll();

        $hans = new \RelationpmUser();
        $hans->setName('hans');

        $admins = new \RelationpmGroup();
        $admins->setName('Admins');

        $position = new \RelationpmPosition();
        $position->setName('Trainee');

        $hans->addGroup($admins);
        $hans->addPosition($position);
        $this->assertCount(1, $hans->getGroups());
        $this->assertCount(1, $hans->getPositions());
        $hans->save();

        $this->assertEquals(2, \RelationpmUserChildQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \RelationpmPositionQuery::create()->count(), 'We have one position.');

        $emptyColl = new ObjectCombinationCollection();
        $hans->setGroups($emptyColl);
        $hans->setPositions($emptyColl);
        $this->assertCount(0, $hans->getGroups());
        $this->assertCount(0, $hans->getPositions());
        $hans->save();

        $this->assertEquals(0, \RelationpmUserChildQuery::create()->count(), 'We have zero connections.');
        $this->assertEquals(1, \RelationpmUserQuery::create()->count(), 'We have one user.');
        $this->assertEquals(1, \RelationpmGroupQuery::create()->count(), 'We have one group.');
        $this->assertEquals(1, \RelationpmPositionQuery::create()->count(), 'We have one position.');
    }

}