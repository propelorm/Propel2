<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Configuration;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\EntityMap;
use Propel\Tests\TestCase;

/**
 * Test class for RelationMap.
 *
 * @author François Zaninotto
 * @version    $Id$
 */
class RelationMapTest extends TestCase
{
    /**
     * @var DatabaseMap
     */
    protected $databaseMap;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var RelationMap
     */
    protected $rmap;

    protected function setUp()
    {
        parent::setUp();
        $this->databaseMap = new DatabaseMap('foodb');
        Configuration::getCurrentConfigurationOrCreate()->registerDatabase($this->databaseMap);
        $this->relationName = 'foo';
        $this->rmap = new RelationMap($this->relationName);
    }

    public function testConstructor()
    {
        $this->assertEquals($this->relationName, $this->rmap->getName(), 'constructor sets the relation name');
    }

    public function testLocalEntity()
    {
        $this->assertNull($this->rmap->getLocalEntity(), 'A new relation has no local table');
        $tmap1 = $this->getMockForAbstractClass(EntityMap::class, ['foo', 'foodb', Configuration::getCurrentConfiguration()]);
        $this->rmap->setLocalEntity($tmap1);
        $this->assertEquals($tmap1, $this->rmap->getLocalEntity(), 'The local table is set by setLocalEntity()');
    }

    public function testForeignEntity()
    {
        $this->assertNull($this->rmap->getForeignEntity(), 'A new relation has no foreign table');
        $tmap2 = $this->getMockForAbstractClass(EntityMap::class, ['bar', 'foodb', Configuration::getCurrentConfiguration()]);
        $this->rmap->setForeignEntity($tmap2);
        $this->assertEquals($tmap2, $this->rmap->getForeignEntity(), 'The foreign table is set by setForeignEntity()');
    }

    public function testProperties()
    {
        $properties = array('type', 'onUpdate', 'onDelete');
        foreach ($properties as $property) {
            $getter = 'get' . ucfirst($property);
            $setter = 'set' . ucfirst($property);
            $this->assertNull($this->rmap->$getter(), "A new relation has no $property");
            $this->rmap->$setter('foo_value');
            $this->assertEquals('foo_value', $this->rmap->$getter(), "The $property is set by setType()");
        }
    }

    public function testFields()
    {
        $this->assertEquals(array(), $this->rmap->getLocalFields(), 'A new relation has no local columns');
        $this->assertEquals(array(), $this->rmap->getForeignFields(), 'A new relation has no foreign columns');
        $tmap1 = $this->getMockForAbstractClass(EntityMap::class, ['foo', 'foodb', Configuration::getCurrentConfiguration()]);
        $col1 = $tmap1->addField('FOO1', 'INTEGER');
        
        $tmap2 = $this->getMockForAbstractClass(EntityMap::class, ['bar', 'foodb', Configuration::getCurrentConfiguration()]);
        $col2 = $tmap2->addField('BAR1', 'INTEGER');
        
        $this->rmap->addFieldMapping($col1, $col2);
        $this->assertEquals(array($col1), $this->rmap->getLocalFields(), 'addFieldMapping() adds a local table');
        $this->assertEquals(array($col2), $this->rmap->getForeignFields(), 'addFieldMapping() adds a foreign table');

        $expected = array('foo.FOO1' => 'bar.BAR1');
        $this->assertEquals($expected, $this->rmap->getFieldMappings(), 'getFieldMappings() returns an associative array of column mappings');

        $col3 = $tmap1->addField('FOOFOO', 'INTEGER');
        $col4 = $tmap2->addField('BARBAR', 'INTEGER');
        $this->rmap->addFieldMapping($col3, $col4);
        $this->assertEquals(array($col1, $col3), $this->rmap->getLocalFields(),
            'addFieldMapping() adds a local table');
        $this->assertEquals(array($col2, $col4), $this->rmap->getForeignFields(),
            'addFieldMapping() adds a foreign table');
        $expected = array('foo.FOO1' => 'bar.BAR1', 'foo.FOOFOO' => 'bar.BARBAR');
        $this->assertEquals($expected, $this->rmap->getFieldMappings(),
            'getFieldMappings() returns an associative array of column mappings');
    }
}
