<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\TestCase;

/**
 * Test class for RelationMap.
 *
 * @author François Zaninotto
 * @version $Id$
 */
class RelationMapTest extends TestCase
{
    /**
     * @var \Propel\Runtime\Map\DatabaseMap
     */
    protected $databaseMap;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var \Propel\Runtime\Map\RelationMap
     */
    protected $rmap;

    /**
     * @var \Propel\Runtime\Map\RelationMap
     */
    protected $defaultLocalTable;

    /**
     * @var \Propel\Runtime\Map\RelationMap
     */
    protected $defaultForeignTable;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseMap = new DatabaseMap('foodb');
        $this->relationName = 'foo';
        $this->defaultLocalTable = new TableMap('local');
        $this->defaultForeignTable = new TableMap('foreign');
        $this->rmap = new RelationMap($this->relationName, $this->defaultLocalTable, $this->defaultForeignTable);
    }

    /**
     * @return void
     */
    public function testConstructor()
    {
        $this->assertEquals($this->relationName, $this->rmap->getName(), 'constructor sets the relation name');
    }

    /**
     * @return void
     */
    public function testLocalTable()
    {
        $this->assertSame($this->defaultLocalTable, $this->rmap->getLocalTable(), 'constructor sets the local table');
        $tmap1 = new TableMap('foo', $this->databaseMap);
        $this->rmap->setLocalTable($tmap1);
        $this->assertEquals($tmap1, $this->rmap->getLocalTable(), 'The local table is set by setLocalTable()');
    }

    /**
     * @return void
     */
    public function testForeignTable()
    {
        $this->assertSame($this->defaultForeignTable, $this->rmap->getForeignTable(), 'constructor sets the foreign table');
        $tmap2 = new TableMap('bar', $this->databaseMap);
        $this->rmap->setForeignTable($tmap2);
        $this->assertEquals($tmap2, $this->rmap->getForeignTable(), 'The foreign table is set by setForeignTable()');
    }

    /**
     * @return void
     */
    public function testProperties()
    {
        $properties = ['type', 'onUpdate', 'onDelete'];
        foreach ($properties as $property) {
            $getter = 'get' . ucfirst($property);
            $setter = 'set' . ucfirst($property);
            $this->assertNull($this->rmap->$getter(), "A new relation has no $property");
            $this->rmap->$setter(RelationMap::MANY_TO_MANY);
            $this->assertEquals(RelationMap::MANY_TO_MANY, $this->rmap->$getter(), "The $property is set by setType()");
        }
    }

    /**
     * @return void
     */
    public function testColumns()
    {
        $this->assertEquals([], $this->rmap->getLocalColumns(), 'A new relation has no local columns');
        $this->assertEquals([], $this->rmap->getForeignColumns(), 'A new relation has no foreign columns');
        $tmap1 = new TableMap('foo', $this->databaseMap);
        $col1 = $tmap1->addColumn('FOO1', 'Foo1PhpName', 'INTEGER');
        $tmap2 = new TableMap('bar', $this->databaseMap);
        $col2 = $tmap2->addColumn('BAR1', 'Bar1PhpName', 'INTEGER');
        $this->rmap->addColumnMapping($col1, $col2);
        $this->assertEquals([$col1], $this->rmap->getLocalColumns(), 'addColumnMapping() adds a local table');
        $this->assertEquals([$col2], $this->rmap->getForeignColumns(), 'addColumnMapping() adds a foreign table');
        $expected = ['foo.FOO1' => 'bar.BAR1'];
        $this->assertEquals($expected, $this->rmap->getColumnMappings(), 'getColumnMappings() returns an associative array of column mappings');
        $col3 = $tmap1->addColumn('FOOFOO', 'FooFooPhpName', 'INTEGER');
        $col4 = $tmap2->addColumn('BARBAR', 'BarBarPhpName', 'INTEGER');
        $this->rmap->addColumnMapping($col3, $col4);
        $this->assertEquals([$col1, $col3], $this->rmap->getLocalColumns(), 'addColumnMapping() adds a local table');
        $this->assertEquals([$col2, $col4], $this->rmap->getForeignColumns(), 'addColumnMapping() adds a foreign table');
        $expected = ['foo.FOO1' => 'bar.BAR1', 'foo.FOOFOO' => 'bar.BARBAR'];
        $this->assertEquals($expected, $this->rmap->getColumnMappings(), 'getColumnMappings() returns an associative array of column mappings');
    }
}
