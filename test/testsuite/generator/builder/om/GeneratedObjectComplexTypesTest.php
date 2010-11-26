<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../../runtime/lib/Propel.php';

/**
 * Tests the generated objects for complex column types accessor & mutator
 *
 * @author     Francois Zaninotto
 * @package    generator.builder.om
 */
class GeneratedObjectComplexTypeTest extends PHPUnit_Framework_TestCase
{
	public function testObjectColumnType()
	{
		$schema = <<<EOF
<database name="generated_object_complex_type_test_1">
	<table name="complex_column_type_entity_1">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="OBJECT" />
	</table>
</database>
EOF;
		PropelQuickBuilder::buildSchema($schema);
		$e = new ComplexColumnTypeEntity1();
		$this->assertNull($e->getBar(), 'object columns are null by default');
		$c = new FooColumnValue();
		$c->bar = 1234;
		$e->setBar($c);
		$this->assertEquals($c, $e->getBar(), 'object columns can store objects');
		$e->setBar(null);
		$this->assertNull($e->getBar(), 'object columns are nullable');
		$e->setBar($c);
		$e->save();
		ComplexColumnTypeEntity1Peer::clearInstancePool();
		$e = ComplexColumnTypeEntity1Query::create()->findOne();
		$this->assertEquals($c, $e->getBar(), 'object columns are persisted');
	}

	public function testArrayColumnType()
	{
		$schema = <<<EOF
<database name="generated_object_complex_type_test_2">
	<table name="complex_column_type_entity_2">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="ARRAY" />
	</table>
</database>
EOF;
		//$builder = new PropelQuickBuilder();
		//$builder->setSchema($schema);
		//echo $builder->getSQL();
		//exit();
		PropelQuickBuilder::buildSchema($schema);
		$e = new ComplexColumnTypeEntity2();
		$this->assertequals(array(), $e->getBar(), 'array columns return an empty array by default');
		$value = array('foo', 1234);
		$e->setBar($value);
		$this->assertEquals($value, $e->getBar(), 'array columns can store arrays');
		$e->setBar(array());
		$this->assertEquals(array(), $e->getBar(), 'object columns can be reset');
		$e->setBar($value);
		$e->save();
		ComplexColumnTypeEntity2Peer::clearInstancePool();
		$e = ComplexColumnTypeEntity2Query::create()->findOne();
		$this->assertEquals($value, $e->getBar(), 'array columns are persisted');
	}
}

class FooColumnValue
{
	public $bar;
}