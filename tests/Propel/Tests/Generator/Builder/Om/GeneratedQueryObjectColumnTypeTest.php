<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Tests the generated queries for object column types filters
 *
 * @author Francois Zaninotto
 */
class GeneratedQueryObjectColumnTypeTest extends \PHPUnit_Framework_TestCase
{
    protected $c1, $c2;

    public function setUp()
    {
        $this->c1 = new FooColumnValue2();
        $this->c1->bar = 1234;
        $this->c2 = new FooColumnValue2();
        $this->c2->bar = 5678;

        if (!class_exists('ComplexColumnTypeEntity10')) {
            $schema = <<<EOF
<database name="generated_query_complex_type_test_10">
    <table name="complex_column_type_entity_10">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="OBJECT" />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            $e0 = new \ComplexColumnTypeEntity10();
            $e0->save();
            $e1 = new \ComplexColumnTypeEntity10();
            $e1->setBar($this->c1);
            $e1->save();
            $e2 = new \ComplexColumnTypeEntity10();
            $e2->setBar($this->c2);
            $e2->save();
            \Map\ComplexColumnTypeEntity10TableMap::clearInstancePool();
        }
    }

    public function testColumnHydration()
    {
        $e = \ComplexColumnTypeEntity10Query::create()
            ->orderById()
            ->offset(1)
            ->findOne();
        $this->assertEquals($this->c1, $e->getBar(), 'object columns are correctly hydrated');
    }

    public function testWhere()
    {
        $nb = \ComplexColumnTypeEntity10Query::create()
            ->where('ComplexColumnTypeEntity10.Bar LIKE ?', '%1234%')
            ->count();
        $this->assertEquals(1, $nb, 'object columns are searchable by serialized object using where()');
        $e = \ComplexColumnTypeEntity10Query::create()
            ->where('ComplexColumnTypeEntity10.Bar = ?', $this->c1)
            ->findOne();
        $this->assertEquals($this->c1, $e->getBar(), 'object columns are searchable by object using where()');
    }

    public function testFilterByColumn()
    {
        $e = \ComplexColumnTypeEntity10Query::create()
            ->filterByBar($this->c1)
            ->findOne();
        $this->assertEquals($this->c1, $e->getBar(), 'object columns are searchable by object');
        $e = \ComplexColumnTypeEntity10Query::create()
            ->filterByBar($this->c2)
            ->findOne();
        $this->assertEquals($this->c2, $e->getBar(), 'object columns are searchable by object');
        $e = \ComplexColumnTypeEntity10Query::create()
            ->filterByBar($this->c1, Criteria::NOT_EQUAL)
            ->findOne();
        $this->assertEquals($this->c2, $e->getBar(), 'object columns are searchable by object');
    }
}

class FooColumnValue2
{
    public $bar;
}
