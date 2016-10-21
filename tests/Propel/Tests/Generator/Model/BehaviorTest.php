<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Entity;
use Propel\Tests\TestCase;

/**
 * Tests for Behavior class
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 */
class BehaviorTest extends TestCase
{
    private $schemaReader;
    private $appData;

    public function testSetupObject()
    {
        $b = new Behavior();
        $b->loadMapping(array('name' => 'foo'));
        $this->assertEquals($b->getName(), 'foo', 'setupObject() sets the Behavior name from XML attributes');
    }

    public function testSetupObjectWithMultipleBehaviorWithNoId()
    {
        $b = new Propel\Tests\Helpers\MultipleBehavior();
        $b->loadMapping(array('name' => 'foo'));

        $this->assertEquals($b->getName(), 'foo', 'setupObject() sets the Behavior name from XML attributes');
        $this->assertEquals($b->getId(), 'foo', 'setupObject() sets the Behavior id from its name when no explicit id is given');
    }

    public function testSetupObjectWithMultipleBehaviorWithId()
    {
        $b = new Propel\Tests\Helpers\MultipleBehavior();
        $b->loadMapping(array('name' => 'foo', 'id' => 'bar'));

        $this->assertEquals($b->getName(), 'foo', 'setupObject() sets the Behavior name from XML attributes');
        $this->assertEquals($b->getId(), 'bar', 'setupObject() sets the Behavior id from XML attributes');
    }

    /**
     * @expectedException Propel\Generator\Exception\LogicException
     */
    public function testSetupObjectFailIfIdGivenOnNotMultipleBehavior()
    {
        $b = new Behavior();
        $b->loadMapping(array('name' => 'foo', 'id' => 'lala'));
    }

    public function testName()
    {
        $b = new Behavior();
        $this->assertNull($b->getName(), 'Behavior name is null by default');
        $b->setName('foo');
        $this->assertEquals($b->getName(), 'foo', 'setName() sets the name, and getName() gets it');
    }

    public function testEntity()
    {
        $b = new Behavior();
        $this->assertNull($b->getEntity(), 'Behavior Entity is null by default');
        $t = new Entity();
        $t->setName('fooEntity');
        $b->setEntity($t);
        $this->assertEquals($b->getEntity(), $t, 'setEntity() sets the name, and getEntity() gets it');
    }

    public function testParameters()
    {
        $b = new Behavior();
        $this->assertEquals($b->getParameters(), array(), 'Behavior parameters is an empty array by default');
        $b->addParameter(array('name' => 'foo', 'value' => 'bar'));
        $this->assertEquals($b->getParameters(), array('foo' => 'bar'), 'addParameter() sets a parameter from an associative array');
        $b->addParameter(array('name' => 'foo2', 'value' => 'bar2'));
        $this->assertEquals($b->getParameters(), array('foo' => 'bar', 'foo2' => 'bar2'), 'addParameter() adds a parameter from an associative array');
        $b->addParameter(array('name' => 'foo', 'value' => 'bar3'));
        $this->assertEquals($b->getParameters(), array('foo' => 'bar3', 'foo2' => 'bar2'), 'addParameter() changes a parameter from an associative array');
        $this->assertEquals($b->getParameter('foo'), 'bar3', 'getParameter() retrieves a parameter value by name');
        $b->setParameters(array('foo3' => 'bar3', 'foo4' => 'bar4'));
        $this->assertEquals($b->getParameters(), array('foo3' => 'bar3', 'foo4' => 'bar4'), 'setParameters() changes the whole parameter array');
    }

    /**
     * test if the entities get the package name from the properties file
     *
     */
    public function testSchemaReader()
    {
        $schemaReader = new SchemaReader();
        $schema = <<<EOF
<database name="test1">
  <entity name="entity1">
    <field name="id" type="INTEGER" primaryKey="true" />
    <field name="title" type="VARCHAR" size="100" primaryString="true" />
    <field name="created_on" type="TIMESTAMP" />
    <field name="updated_on" type="TIMESTAMP" />
    <behavior name="timestampable">
      <parameter name="create_field" value="created_on" />
      <parameter name="update_field" value="updated_on" />
    </behavior>
  </entity>
</database>
EOF;
        $appData = $schemaReader->parseString($schema);
        $entity = $appData->getDatabase('test1')->getEntity('Entity1');
        $behaviors = $entity->getBehaviors();
        $this->assertEquals(1, count($behaviors), 'SchemaReader ads as many behaviors as there are behaviors tags');
        $behavior = $entity->getBehavior('timestampable');
        $this->assertEquals('Entity1', $behavior->getEntity()->getName(), 'SchemaReader sets the behavior entity correctly');
        $this->assertEquals(
            array('create_field' => 'created_on', 'update_field' => 'updated_on', 'disable_created_at' => 'false', 'disable_updated_at' => 'false'),
            $behavior->getParameters(),
            'SchemaReader sets the behavior parameters correctly'
        );
    }

  /**
   * @expectedException \Propel\Generator\Exception\BehaviorNotFoundException
   */
    public function testUnknownBehavior()
    {
        $schemaReader = new SchemaReader();
        $schema = <<<EOF
<database name="test1">
  <entity name="entity1">
    <field name="id" type="INTEGER" primaryKey="true" />
    <behavior name="foo" />
  </entity>
</database>
EOF;
        $appData = $schemaReader->parseString($schema);
    }

    public function testModifyEntity()
    {
        $schemaReader = new SchemaReader();
        $schema = <<<EOF
<database name="test1">
  <entity name="entity2">
    <field name="id" type="INTEGER" primaryKey="true" />
    <field name="title" type="VARCHAR" size="100" primaryString="true" />
    <behavior name="timestampable" />
  </entity>
</database>
EOF;
        $appData = $schemaReader->parseString($schema);
        $entity = $appData->getDatabase('test1')->getEntity('entity2', true);
        $this->assertEquals(count($entity->getFields()), 4, 'A behavior can modify its entity by implementing modifyEntity()');
    }

    public function testModifyDatabase()
    {
        $schemaReader = new SchemaReader();
        $schema = <<<EOF
<database name="test1">
  <behavior name="timestampable" />
  <entity name="entity1">
    <field name="id" type="INTEGER" primaryKey="true" />
  </entity>
</database>
EOF;
        $appData = $schemaReader->parseString($schema);
        $entity = $appData->getDatabase('test1')->getEntity('Entity1');
        $this->assertTrue(array_key_exists('timestampable', $entity->getBehaviors()), 'A database behavior is automatically copied to all its entity');
    }

    public function testGetFieldForParameter()
    {
        $schemaReader = new SchemaReader();
        $schema = <<<EOF
<database name="test1">
  <entity name="entity1">
    <field name="id" type="INTEGER" primaryKey="true" />
    <field name="title" type="VARCHAR" size="100" primaryString="true" />
    <field name="created_on" type="TIMESTAMP" />
    <field name="updated_on" type="TIMESTAMP" />
    <behavior name="timestampable">
      <parameter name="create_field" value="created_on" />
      <parameter name="update_field" value="updated_on" />
    </behavior>
  </entity>
</database>
EOF;
        $appData = $schemaReader->parseString($schema);
        $entity = $appData->getDatabase('test1')->getEntity('Entity1');
        $behavior = $entity->getBehavior('timestampable');
        $this->assertEquals($entity->getField('created_on'), $behavior->getFieldForParameter('create_field'), 'getFieldForParameter() returns the configured field for behavior based on a parameter name');
    }
}
