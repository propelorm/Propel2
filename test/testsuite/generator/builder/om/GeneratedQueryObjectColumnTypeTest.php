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
 * Tests the generated queries for object column types filters
 *
 * @author     Francois Zaninotto
 * @package    generator.builder.om
 */
class GeneratedQueryObjectColumnTest extends PHPUnit_Framework_TestCase
{
	public function testObjectColumnType()
	{
		$schema = <<<EOF
<database name="generated_query_complex_type_test_10">
	<table name="complex_column_type_entity_10">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="OBJECT" />
	</table>
</database>
EOF;
		PropelQuickBuilder::buildSchema($schema);
		$e1 = new ComplexColumnTypeEntity10();
		$c1 = new FooColumnValue2();
		$c1->bar = 1234;
		$e1->setBar($c1);
		$e1->save();
		$e2 = new ComplexColumnTypeEntity10();
		$c2 = new FooColumnValue2();
		$c2->bar = 5678;
		$e2->setBar($c2);
		$e2->save();
		$e = ComplexColumnTypeEntity10Query::create()->findPk($e1->getPrimaryKey());
		$this->assertEquals($c1, $e->getBar(), 'object columns are correctly hydrated');
		$nb = ComplexColumnTypeEntity10Query::create()->where('ComplexColumnTypeEntity10.Bar LIKE ?', '%1234%')->count();
		$this->assertEquals(1, $nb, 'object columns are searchable by serialized string using where()');
		$e = ComplexColumnTypeEntity10Query::create()->filterByBar($c1)->findOne();
		$this->assertEquals($e1, $e, 'object columns are searchable by object');
		$this->assertEquals($c1, $e->getBar(), 'object columns are searchable by object');
		$e = ComplexColumnTypeEntity10Query::create()->filterByBar($c2)->findOne();
		$this->assertEquals($e2, $e, 'object columns are searchable by object');
		$this->assertEquals($c2, $e->getBar(), 'object columns are searchable by object');
		$e = ComplexColumnTypeEntity10Query::create()->filterByBar($c1, Criteria::NOT_EQUAL)->findOne();
		$this->assertEquals($e2, $e, 'object columns are searchable by object and accept a comparator');
	}
}

class FooColumnValue2
{
	public $bar;
}