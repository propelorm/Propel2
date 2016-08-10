<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Generator\Builder\Om;

use \MyNameSpace\ComplexFieldTypeEntity2;
use \MyNameSpace\ComplexFieldTypeEntity2Query;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Configuration;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for array column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectArrayFieldTypeTest extends TestCase
{
    /** @var  Configuration */
    private $con;

    public function setUp()
    {
        if (!class_exists('MyNameSpace\\ComplexFieldTypeEntity2')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_2" namespace="MyNameSpace">
    <entity name="ComplexFieldTypeEntity2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="tags" type="ARRAY" />
        <field name="value_set" type="ARRAY" />
        <field name="defaults" type="ARRAY" defaultValue="FOO" />
        <field name="multiple_defaults" type="ARRAY" defaultValue="FOO,BAR,BAZ" />
    </entity>
</database>
EOF;
            $this->con = QuickBuilder::buildSchema($schema);
        } else {
            $this->con = Configuration::getCurrentConfiguration();
        }

        $this->getRepository()->deleteAll();
    }

    public function testGetterDefaultValue()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->assertEquals(array(), $e->getTags(), 'array columns return an empty array by default');
    }

    public function testGetterDefaultValueWithData()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->assertEquals(array('FOO'), $e->getDefaults());
    }

    public function testGetterDefaultValueWithMultipleData()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $e->getMultipleDefaults());
    }

    public function testDefaultValuesAreWellPersisted()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->getRepository()->save($e);

        $e = ComplexFieldTypeEntity2Query::create()->findOne();

        $this->assertEquals(array('FOO'), $e->getDefaults());
    }

    public function testMultipleDefaultValuesAreWellPersisted()
    {
        $e = new ComplexFieldTypeEntity2();
        $this->getRepository()->save($e);

        $e = ComplexFieldTypeEntity2Query::create()->findOne();

        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $e->getMultipleDefaults());
    }

    public function testSetterArrayValue()
    {
        $e = new ComplexFieldTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $this->assertEquals($value, $e->getTags(), 'array columns can store arrays');
    }

    public function testSetterResetValue()
    {
        $e = new ComplexFieldTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $e->setTags(array());
        $this->assertEquals(array(), $e->getTags(), 'object columns can be reset');
    }

    public function testValueIsPersisted()
    {
        $e = new ComplexFieldTypeEntity2();
        $value = array('foo', 1234);
        $e->setTags($value);
        $this->getRepository()->save($e);

        $e = ComplexFieldTypeEntity2Query::create()->findOne();
        $this->assertEquals($value, $e->getTags(), 'array columns are persisted');
    }

    public function testGetterDoesNotKeepValueBetweenTwoHydrationsWhenUsingOnDemandFormatter()
    {
        $repository = $this->getRepository();
        $repository->deleteAll();

        $e = new ComplexFieldTypeEntity2();
        $e->setTags(array(1,2));
        $repository->save($e);

        $e = new ComplexFieldTypeEntity2();
        $e->setTags(array(3,4));
        $repository->save($e);

        $q = $repository->createQuery()
            ->setFormatter(ModelCriteria::FORMAT_ON_DEMAND)
            ->find();

        $tags = array();
        foreach ($q as $e) {
            $tags[] = $e->getTags();
        }
        $this->assertNotEquals($tags[0], $tags[1]);
    }

    public function testHydrateOverwritePreviousValues()
    {
        $repository = $this->getRepository();

        $obj = new ComplexFieldTypeEntity2();
        $this->assertEquals(array('FOO', 'BAR', 'BAZ'), $obj->getMultipleDefaults());

        $obj->setMultipleDefaults(array('baz'));
        $this->assertEquals(array('baz'), $obj->getMultipleDefaults());

        $repository->save($obj);

        $obj = $repository->createQuery()
            ->findOne();
        $this->assertEquals(array('baz'), $obj->getMultipleDefaults());
    }

    private function getRepository($entityName = 'MyNameSpace\\ComplexFieldTypeEntity2')
    {
        return $this->con->getRepository($entityName);
    }
}
