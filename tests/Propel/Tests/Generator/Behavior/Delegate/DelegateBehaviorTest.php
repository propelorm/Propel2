<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Delegate;

use DelegateBasketballer;
use DelegateDelegate;
use DelegateFootballer;
use DelegateMain;
use Map\DelegateDelegateTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Tests\TestCase;
use SecondDelegateDelegate;
use TestTablePrefixSameDatabaseMain;

/**
 * Tests for DelegateBehavior class
 *
 * @author François Zaninotto
 */
class DelegateBehaviorTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('DelegateDelegate')) {
            $schema = <<<EOF
<database name="delegate_behavior_test_1">

    <table name="delegate_main">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="delegate_id" type="INTEGER"/>
        <foreign-key foreignTable="second_delegate_delegate">
            <reference local="delegate_id" foreign="id"/>
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="delegate_delegate, second_delegate_delegate"/>
        </behavior>
    </table>

    <table name="delegate_delegate">
        <column name="subtitle" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="second_delegate_delegate">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="summary" type="VARCHAR" size="100" primaryString="true"/>
        <behavior name="delegate">
            <parameter name="to" value="third_delegate_delegate"/>
        </behavior>
    </table>

    <table name="third_delegate_delegate">
        <column name="body" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="delegate_player">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="first_name" type="VARCHAR" size="100" primaryString="true"/>
        <column name="last_name" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="delegate_basketballer">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="points" type="INTEGER"/>
        <column name="field_goals" type="INTEGER"/>
        <column name="player_id" type="INTEGER"/>
        <foreign-key foreignTable="delegate_player">
            <reference local="player_id" foreign="id"/>
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="delegate_player"/>
        </behavior>
    </table>

    <table name="delegate_team">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="name" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="delegate_footballer">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="goals_scored" type="INTEGER"/>
        <column name="fouls_committed" type="INTEGER"/>
        <column name="player_id" type="INTEGER"/>
        <foreign-key foreignTable="delegate_player">
            <reference local="player_id" foreign="id"/>
        </foreign-key>
        <column name="team_id" type="INTEGER"/>
        <foreign-key foreignTable="delegate_team">
            <reference local="team_id" foreign="id"/>
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="delegate_player, delegate_team"/>
        </behavior>
    </table>

</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testModifyTableRelatesOneToOneDelegate()
    {
        $delegateTable = DelegateDelegateTableMap::getTableMap();
        $this->assertEquals(2, count($delegateTable->getColumns()));
        $this->assertEquals(1, count($delegateTable->getRelations()));
        $this->assertTrue(method_exists('DelegateMain', 'getDelegateDelegate'));
        $this->assertTrue(method_exists('DelegateDelegate', 'getDelegateMain'));
    }

    /**
     * @return void
     */
    public function testOneToOneDelegationCreatesANewDelegateIfNoneExists()
    {
        $main = new DelegateMain();
        $main->setSubtitle('foo');
        $delegate = $main->getDelegateDelegate();
        $this->assertInstanceOf('DelegateDelegate', $delegate);
        $this->assertTrue($delegate->isNew());
        $this->assertEquals('foo', $delegate->getSubtitle());
        $this->assertEquals('foo', $main->getSubtitle());
    }

    /**
     * @return void
     */
    public function testManyToOneDelegationCreatesANewDelegateIfNoneExists()
    {
        $main = new DelegateMain();
        $main->setSummary('foo');
        $delegate = $main->getSecondDelegateDelegate();
        $this->assertInstanceOf('SecondDelegateDelegate', $delegate);
        $this->assertTrue($delegate->isNew());
        $this->assertEquals('foo', $delegate->getSummary());
        $this->assertEquals('foo', $main->getSummary());
    }

    /**
     * @return void
     */
    public function testOneToOneDelegationUsesExistingDelegateIfExists()
    {
        $main = new DelegateMain();
        $delegate = new DelegateDelegate();
        $delegate->setSubtitle('bar');
        $main->setDelegateDelegate($delegate);
        $this->assertEquals('bar', $main->getSubtitle());
    }

    /**
     * @return void
     */
    public function testManyToOneDelegationUsesExistingDelegateIfExists()
    {
        $main = new DelegateMain();
        $delegate = new SecondDelegateDelegate();
        $delegate->setSummary('bar');
        $main->setSecondDelegateDelegate($delegate);
        $this->assertEquals('bar', $main->getSummary());
    }

    /**
     * @return void
     */
    public function testAModelCanHaveSeveralDelegates()
    {
        $main = new DelegateMain();
        $main->setSubtitle('foo');
        $main->setSummary('bar');
        $delegate = $main->getDelegateDelegate();
        $this->assertInstanceOf('DelegateDelegate', $delegate);
        $this->assertTrue($delegate->isNew());
        $this->assertEquals('foo', $delegate->getSubtitle());
        $this->assertEquals('foo', $main->getSubtitle());
        $delegate = $main->getSecondDelegateDelegate();
        $this->assertInstanceOf('SecondDelegateDelegate', $delegate);
        $this->assertTrue($delegate->isNew());
        $this->assertEquals('bar', $delegate->getSummary());
        $this->assertEquals('bar', $main->getSummary());
    }

    /**
     * @return void
     */
    public function testAModelCannotHaveCascadingDelegates()
    {
        $this->expectException(BadMethodCallException::class);

        $main = new DelegateMain();
        $main->setSummary('bar');
        $main->setBody('baz');
    }

    /**
     * @return void
     */
    public function testOneToOneDelegatesCanBePersisted()
    {
        $main = new DelegateMain();
        $main->setSubtitle('foo');
        $main->save();
        $this->assertFalse($main->isNew());
        $this->assertFalse($main->getDelegateDelegate()->isNew());
        $this->assertNull($main->getSecondDelegateDelegate());
    }

    /**
     * @return void
     */
    public function testManyToOneDelegatesCanBePersisted()
    {
        $main = new DelegateMain();
        $main->setSummary('foo');
        $main->save();
        $this->assertFalse($main->isNew());
        $this->assertFalse($main->getSecondDelegateDelegate()->isNew());
        $this->assertNull($main->getDelegateDelegate());
    }

    /**
     * @return void
     */
    public function testDelegateSimulatesClassTableInheritance()
    {
        $basketballer = new DelegateBasketballer();
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
     * @return void
     */
    public function testDelegateSimulatesMultipleClassTableInheritance()
    {
        $footballer = new DelegateFootballer();
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

    /**
     * @return void
     */
    public function testTablePrefixSameDatabase()
    {
        $schema = <<<EOF
<database name="testTablePrefixSameDatabase_database" tablePrefix="foo">

    <table name="testTablePrefixSameDatabase_main">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="delegate_id" type="INTEGER"/>
        <foreign-key foreignTable="testTablePrefixSameDatabase_delegate">
            <reference local="delegate_id" foreign="id"/>
        </foreign-key>
        <behavior name="delegate">
            <parameter name="to" value="testTablePrefixSameDatabase_delegate"/>
        </behavior>
    </table>

    <table name="testTablePrefixSameDatabase_delegate">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="subtitle" type="VARCHAR" size="100" primaryString="true"/>
    </table>

</database>
EOF;
        QuickBuilder::buildSchema($schema);
        $main = new TestTablePrefixSameDatabaseMain();
        $main->setSubtitle('bar');
        $delegate = $main->getTestTablePrefixSameDatabaseDelegate();
        $this->assertInstanceOf('TestTablePrefixSameDatabaseDelegate', $delegate);
        $this->assertTrue($delegate->isNew());
        $this->assertEquals('bar', $delegate->getSubtitle());
        $this->assertEquals('bar', $main->getSubtitle());
    }
}
