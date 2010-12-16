<?php

/*
 *	$Id: VersionableBehaviorTest.php 1460 2010-01-17 22:36:48Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/behavior/versionable/VersionableBehavior.php';
require_once dirname(__FILE__) . '/../../../../../runtime/lib/Propel.php';

/**
 * Tests for VersionableBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior.versionable
 */
class VersionableBehaviorObjectBuilderModifierTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		if (!class_exists('VersionableBehaviorTest1')) {
			$schema = <<<EOF
<database name="versionable_behavior_test_1">
	<table name="versionable_behavior_test_1">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable" />
	</table>
	<table name="versionable_behavior_test_2">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable">
			<parameter name="version_column" value="foo_ver" />
		</behavior>
	</table>
</database>>
EOF;
$b = new PropelQuickBuilder();
$b->setSchema($schema);
//echo $b->getClasses();
			PropelQuickBuilder::buildSchema($schema);
		}
	}

	public function testGetVersionExists()
	{
		$this->assertTrue(method_exists('VersionableBehaviorTest1', 'getVersion'));
		$this->assertTrue(method_exists('VersionableBehaviorTest2', 'getVersion'));
	}

	public function testSetVersionExists()
	{
		$this->assertTrue(method_exists('VersionableBehaviorTest1', 'setVersion'));
		$this->assertTrue(method_exists('VersionableBehaviorTest2', 'setVersion'));
	}
	
	public function providerForNewActiveRecordTests()
	{
		// Damn you phpUnit, why do providers execute before setUp() ?
		$this->setUp();
		return array(
			array(new VersionableBehaviorTest1()),
			array(new VersionableBehaviorTest2()),
		);
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionGetterAndSetter($o)
	{
		$o->setVersion(1234);
		$this->assertEquals(1234, $o->getVersion());
	}
	
	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionDefaultValue($o)
	{
		$this->assertEquals(0, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionValueInitializesOnInsert($o)
	{
		$o->save();
		$this->assertEquals(1, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionValueIncrementsOnUpdate($o)
	{
		$o->save();
		$this->assertEquals(1, $o->getVersion());
		$o->setBar(12);
		$o->save();
		$this->assertEquals(2, $o->getVersion());
		$o->setBar(13);
		$o->save();
		$this->assertEquals(3, $o->getVersion());
		$o->setBar(12);
		$o->save();
		$this->assertEquals(4, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionDoesNotIncrementOnUpdateWithNoChange($o)
	{
		$o->setBar(12);
		$o->save();
		$this->assertEquals(1, $o->getVersion());
		$o->setBar(12);
		$o->save();
		$this->assertEquals(1, $o->getVersion());
	}
	
}