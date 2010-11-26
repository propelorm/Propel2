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
 * Tests the generated queries for complex column types filters
 *
 * @author     Francois Zaninotto
 * @package    generator.builder.om
 */
class GeneratedQueryComplexTypeTest extends PHPUnit_Framework_TestCase
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

	public function testArrayColumnType()
	{
		$schema = <<<EOF
<database name="generated_query_complex_type_test_11">
	<table name="complex_column_type_entity_11">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="ARRAY" />
	</table>
</database>
EOF;
		PropelQuickBuilder::buildSchema($schema);
		$e0 = new ComplexColumnTypeEntity11();
		$e0->save();
		$e1 = new ComplexColumnTypeEntity11();
		$e1->setBar(array('foo', 'bar', 'baz'));
		$e1->save();
		$e2 = new ComplexColumnTypeEntity11();
		$e2->setBar(array('bar'));
		$e2->save();
		$e3 = new ComplexColumnTypeEntity11();
		$e3->setBar(array('bar23'));
		$e3->save();
		$e = ComplexColumnTypeEntity11Query::create()->findPk($e1->getPrimaryKey());
		$this->assertEquals(array('foo', 'bar', 'baz'), $e->getBar(), 'array columns are correctly hydrated');
		$e = ComplexColumnTypeEntity11Query::create()->filterByBar('bar')->orderById()->find();
		$this->assertEquals($e1, $e[0], 'array columns are searchable by element');
		$this->assertEquals(array('foo', 'bar', 'baz'), $e[0]->getBar(), 'array columns are searchable by element');
		$this->assertEquals($e2, $e[1], 'array columns are searchable by element');
		$this->assertEquals(array('bar'), $e[1]->getBar(), 'array columns are searchable by element');
		$this->assertEquals(2, $e->count(), 'array columns do not return false positives');
		$e = ComplexColumnTypeEntity11Query::create()->filterByBar('bar23')->findOne();
		$this->assertEquals($e3, $e, 'array columns are searchable by element');
		$e = ComplexColumnTypeEntity11Query::create()
			->filterByBar('bar', Criteria::CONTAINS)
			->orderById()
			->find();
		$this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::CONTAINS');
		$this->assertEquals($e1, $e[0], 'array columns are searchable by element using Criteria::CONTAINS');
		$this->assertEquals($e2, $e[1], 'array columns are searchable by element using Criteria::CONTAINS');
		$e = ComplexColumnTypeEntity11Query::create()
			->filterByBar('bar', Criteria::NOT_CONTAINS)
			->orderById()
			->find();
		$this->assertEquals(2, $e->count(), 'array columns are searchable by element using Criteria::NOT_CONTAINS');
		$this->assertEquals($e0, $e[0], 'array columns are searchable by element using Criteria::NOT_CONTAINS');
		$this->assertEquals($e3, $e[1], 'array columns are searchable by element using Criteria::NOT_CONTAINS');
	}
}

class FooColumnValue2
{
	public $bar;
}