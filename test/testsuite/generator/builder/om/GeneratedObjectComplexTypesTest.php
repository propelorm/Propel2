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

class GeneratedObjectComplexTypeTest extends PHPUnit_Framework_TestCase
{
	public function testObjectColumnType()
	{
		$schema = <<<EOF
<database name="generated_object_complex_type_test">
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
		$nb = ComplexColumnTypeEntity1Query::create()->where('ComplexColumnTypeEntity1.Bar LIKE ?', '%1234%')->count();
		$this->assertEquals(1, $nb, 'object columns are searchable by serialized string');
		$nb = ComplexColumnTypeEntity1Query::create()->filterByBar($c)->count();
		$this->assertEquals(1, $nb, 'object columns are searchable by object');
	}
}

class FooColumnValue
{
	public $bar;
}