<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Delegate;

use Map\DelegateDelegateEntityMap;
use Propel\Generator\Behavior\Delegate\DelegateBehavior;
use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Configuration;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests for DelegateBehavior class
 *
 * @author François Zaninotto
 */
class DelegateBehaviorTest extends TestCase
{

    public function setUp()
    {
        if (!class_exists('DelegateDelegate')) {
            $schema = <<<EOF
<database name="delegate_behavior_test_1" activeRecord="true">

    <entity name="delegate_main">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="delegate_id" type="INTEGER" />
        <foreign-key foreignEntity="second_delegate_delegate">
            <reference local="delegate_id" foreign="id" />
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="DelegateDelegate, SecondDelegateDelegate" />
        </behavior>
    </entity>

    <entity name="delegate_delegate">
        <field name="subtitle" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="second_delegate_delegate">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="summary" type="VARCHAR" size="100" primaryString="true" />
        <behavior name="delegate">
            <parameter name="to" value="ThirdDelegateDelegate" />
        </behavior>
    </entity>

    <entity name="third_delegate_delegate">
        <field name="body" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="delegate_player">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="first_name" type="VARCHAR" size="100" primaryString="true" />
        <field name="last_name" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="delegate_basketballer">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="points" type="INTEGER" />
        <field name="field_goals" type="INTEGER" />
        <field name="player_id" type="INTEGER" />
        <foreign-key foreignEntity="delegate_player">
            <reference local="player_id" foreign="id" />
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="DelegatePlayer" />
        </behavior>
    </entity>

    <entity name="delegate_team">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="name" type="VARCHAR" size="100" primaryString="true" />
    </entity>

    <entity name="delegate_footballer">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="goals_scored" type="INTEGER" />
        <field name="fouls_committed" type="INTEGER" />
        <field name="player_id" type="INTEGER" />
        <foreign-key foreignEntity="delegate_player">
            <reference local="player_id" foreign="id" />
        </foreign-key>
        <field name="team_id" type="INTEGER" />
        <foreign-key foreignEntity="delegate_team">
            <reference local="team_id" foreign="id" />
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="DelegatePlayer, DelegateTeam" />
        </behavior>
    </entity>

</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function testModifyTableRelatesOneToOneDelegate()
    {
        $delegateTable = QuickBuilder::$configuration->getEntityMap(DelegateDelegateEntityMap::ENTITY_CLASS);
        $this->assertEquals(2, count($delegateTable->getFields()));
        $this->assertEquals(1, count($delegateTable->getRelations()));
        $this->assertTrue(method_exists('DelegateMain', 'getDelegateDelegate'));
        $this->assertTrue(method_exists('DelegateDelegate', 'getDelegateMain'));
    }

    public function testOneToOneDelegationCreatesANewDelegateIfNoneExists()
    {
        $main = new \DelegateMain();
        $main->setSubtitle('foo');
        $delegate = $main->getDelegateDelegate();
        $this->assertInstanceOf('DelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('foo', $delegate->getSubtitle());
        $this->assertEquals('foo', $main->getSubtitle());
    }

    public function testManyToOneDelegationCreatesANewDelegateIfNoneExists()
    {
        $main = new \DelegateMain();
        $main->setSummary('foo');
        $delegate = $main->getSecondDelegateDelegate();
        $this->assertInstanceOf('SecondDelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('foo', $delegate->getSummary());
        $this->assertEquals('foo', $main->getSummary());
    }

    public function testOneToOneDelegationUsesExistingDelegateIfExists()
    {
        $main = new \DelegateMain();
        $delegate = new \DelegateDelegate();
        $delegate->setSubtitle('bar');
        $main->setDelegateDelegate($delegate);
        $this->assertEquals('bar', $main->getSubtitle());
    }

    public function testManyToOneDelegationUsesExistingDelegateIfExists()
    {
        $main = new \DelegateMain();
        $delegate = new \SecondDelegateDelegate();
        $delegate->setSummary('bar');
        $main->setSecondDelegateDelegate($delegate);
        $this->assertEquals('bar', $main->getSummary());
    }

    public function testAModelCanHaveSeveralDelegates()
    {
        $main = new \DelegateMain();
        $main->setSubtitle('foo');
        $main->setSummary('bar');
        $delegate = $main->getDelegateDelegate();
        $this->assertInstanceOf('DelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('foo', $delegate->getSubtitle());
        $this->assertEquals('foo', $main->getSubtitle());
        $delegate = $main->getSecondDelegateDelegate();
        $this->assertInstanceOf('SecondDelegateDelegate', $delegate);
        $this->assertTrue(QuickBuilder::$configuration->getSession()->isNew($delegate));
        $this->assertEquals('bar', $delegate->getSummary());
        $this->assertEquals('bar', $main->getSummary());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\BadMethodCallException
     */
    public function testAModelCannotHaveCascadingDelegates()
    {
        $main = new \DelegateMain();
        $main->setSummary('bar');
        $main->setBody('baz');
    }

    public function testOneToOneDelegatesCanBePersisted()
    {
        $main = new \DelegateMain();
        $main->setSubtitle('foo');
        $main->save();
        $this->assertFalse($main->isNew());
        $this->assertFalse($main->getDelegateDelegate()->isNew());
        $this->assertNull($main->getSecondDelegateDelegate());
    }

    public function testManyToOneDelegatesCanBePersisted()
    {
        $main = new \DelegateMain();
        $main->setSummary('foo');
        $main->save();
        $this->assertFalse($main->isNew());
        $this->assertFalse($main->getSecondDelegateDelegate()->isNew());
        $this->assertNull($main->getDelegateDelegate());
    }

    public function testDelegateSimulatesClassTableInheritance()
    {
        $basketballer = new \DelegateBasketballer();
        $basketballer->setPoints(101);
        $basketballer->setFieldGoals(47);
        $this->assertNull($basketballer->getDelegatePlayer());
        $basketballer->setFirstName('Michael');
        $basketballer->setLastName('Giordano');
        $this->assertNotNull($basketballer->getDelegatePlayer());
        $this->assertEquals('Michael', $basketballer->getDelegatePlayer()->getFirstName());
        $this->assertEquals('Michael', $basketballer->getFirstName());
        $basketballer->save(); // should not throw exception
    }

    /**
     * @group test
     */
    public function testDelegateSimulatesMultipleClassTableInheritance()
    {
        Configuration::$globalConfiguration->getSession()->reset();
        $footballer = new \DelegateFootballer();

        $footballer->setGoalsScored(43);
        $footballer->setFoulsCommitted(4);
        $this->assertNull($footballer->getDelegatePlayer());
        $this->assertNull($footballer->getDelegateTeam());
        $footballer->setFirstName('Michael');
        $footballer->setLastName('Giordano');

        $this->assertNotNull($footballer->getDelegatePlayer());
        $this->assertEquals('Michael', $footballer->getDelegatePlayer()->getFirstName());
        $this->assertEquals('Michael', $footballer->getFirstName());
        $footballer->setName('Saint Etienne');

        $this->assertNotNull($footballer->getDelegateTeam());
        $this->assertEquals('Saint Etienne', $footballer->getDelegateTeam()->getName());
        $this->assertEquals('Saint Etienne', $footballer->getName());
        $footballer->save(); // should not throw exception
    }

//    public function testTablePrefixSameDatabase()
//    {
//        $schema = <<<EOF
//<database name="testTablePrefixSameDatabase_database" tablePrefix="foo">
//
//    <table name="testTablePrefixSameDatabase_main">
//        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
//        <field name="title" type="VARCHAR" size="100" primaryString="true" />
//        <field name="delegate_id" type="INTEGER" />
//        <foreign-key foreignTable="testTablePrefixSameDatabase_delegate">
//            <reference local="delegate_id" foreign="id" />
//        </foreign-key>
//        <behavior name="delegate">
//            <parameter name="to" value="testTablePrefixSameDatabase_delegate" />
//        </behavior>
//    </table>
//
//    <table name="testTablePrefixSameDatabase_delegate">
//        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
//        <field name="subtitle" type="VARCHAR" size="100" primaryString="true" />
//    </table>
//
//</database>
//EOF;
//        QuickBuilder::buildSchema($schema);
//        $main = new \TestTablePrefixSameDatabaseMain();
//        $main->setSubtitle('bar');
//        $delegate = $main->getTestTablePrefixSameDatabaseDelegate();
//        $this->assertInstanceOf('TestTablePrefixSameDatabaseDelegate', $delegate);
//        $this->assertTrue($delegate->isNew());
//        $this->assertEquals('bar', $delegate->getSubtitle());
//        $this->assertEquals('bar', $main->getSubtitle());
//    }

}
