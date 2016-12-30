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

        if (!class_exists('\RelationpmsrUserQuery')) {
            $schema = '
    <database name="migration" schema="migration">
        <table name="relationpmsr_user_child" isCrossRef="true">
            <column name="user_id" type="integer" primaryKey="true"/>
            <column name="child_type" primaryKey="true"/>
            <column name="child_id" type="integer" primaryKey="true"/>       

            <foreign-key foreignTable="relationpmsr_user" phpName="User" onDelete="cascade">
                <reference local="user_id" foreign="id"/>
            </foreign-key>            

            <foreign-key foreignTable="relationpmsr_position" phpName="Position" onDelete="cascade">
                <reference local="child_id" foreign="id"/>
                <reference local="child_type" value="position"/>
            </foreign-key>                        
            
            <foreign-key foreignTable="relationpmsr_user" phpName="ChildUser" onDelete="cascade">
                <reference local="child_id" foreign="id"/>
                <reference local="child_type" value="user"/>
            </foreign-key>
        </table>

        <table name="relationpmsr_user">
            <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <column name="name" />
        </table>

        <table name="relationpmsr_position">
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
        \RelationpmsrUserQuery::create()->deleteAll();
        \RelationpmsrUserChildQuery::create()->deleteAll();
        \RelationpmsrPositionQuery::create()->deleteAll();

        $hans = new \RelationpmsrUser();
        $hans->setName('hans');

        $frank = new \RelationpmsrUser();
        $frank->setName('frank');

        $position = new \RelationpmsrPosition();
        $position->setName('Trainee');

        $hans->addChildUser($frank);
        $hans->addPosition($position);
        $this->assertCount(1, $hans->getChildUsers());
        $this->assertCount(1, $hans->getPositions());
        $this->assertCount(1, $position->getUsers());
        $hans->save();

        $this->assertEquals(2, \RelationpmsrUserChildQuery::create()->count(), 'We have two connections.');
        $this->assertEquals(2, \RelationpmsrUserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(1, \RelationpmsrPositionQuery::create()->count(), 'We have one position.');

        $positionLead = new \RelationpmsrPosition();
        $positionLead->setName('Lead');
        $hans->addPosition($positionLead);
        $this->assertCount(1, $hans->getChildUsers());
        $this->assertCount(2, $hans->getPositions());
        $this->assertCount(1, $positionLead->getUsers());
        $hans->save();

        $this->assertEquals(3, \RelationpmsrUserChildQuery::create()->count(), 'We have three connections.');
        $this->assertEquals(2, \RelationpmsrUserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(2, \RelationpmsrPositionQuery::create()->count(), 'We have two positions.');

        //try to add the same combination on a new instance
        \Map\RelationpmsrUserTableMap::clearInstancePool();
        /** @var $newHansObject \RelationpmsrUser */
        $newHansObject = \RelationpmsrUserQuery::create()->findOneByName('hans');

        $newHansObject->addChildUser($frank);
        $newHansObject->addPosition($position);
        $this->assertCount(1, $newHansObject->getChildUsers());
        $this->assertCount(2, $newHansObject->getPositions());
        $this->assertCount(1, $frank->getUsers());
        $this->assertCount(1, $position->getUsers());
        $newHansObject->save();

        $this->assertEquals(3, \RelationpmsrUserChildQuery::create()->count(), 'We have three connections.');
        $this->assertEquals(2, \RelationpmsrUserQuery::create()->count(), 'We have two users.');
        $this->assertEquals(2, \RelationpmsrPositionQuery::create()->count(), 'We have two positions.');
    }
}