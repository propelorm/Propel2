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

use Propel\Runtime\Configuration;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for enum field types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectEnumFieldTypeTest extends TestCase
{
    /** @var  Configuration */
    private $con;

    public function setUp()
    {
        if (!class_exists('ComplexFieldTypeEntity3')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_3">
    <entity name="ComplexFieldTypeEntity3">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="ENUM" valueSet="foo, bar, baz, 1, 4,(, foo bar " />
        <field name="bar2" type="ENUM" valueSet="foo, bar" defaultValue="bar" />
    </entity>
</database>
EOF;
            $this->con = QuickBuilder::buildSchema($schema);
            // ok this is hackish but it makes testing of getter and setter independent of each other
            $publicAccessorCode = <<<EOF
class PublicComplexFieldTypeEntity3 extends ComplexFieldTypeEntity3
{
    public \$bar;
}
EOF;
            eval($publicAccessorCode);
        } else {
            $this->con = Configuration::getCurrentConfiguration();
        }
    }

    public function testGetter()
    {
        $this->assertTrue(method_exists('ComplexFieldTypeEntity3', 'getBar'));
        $e = new \ComplexFieldTypeEntity3();
        $this->assertNull($e->getBar());
        $e = new \PublicComplexFieldTypeEntity3();
        $e->bar = 0;
        $this->assertEquals('foo', $e->getBar());
        $e->bar = 3;
        $this->assertEquals('1', $e->getBar());
        $e->bar = 6;
        $this->assertEquals('foo bar', $e->getBar());
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testGetterThrowsExceptionOnUnknownKey()
    {
        $e = new \PublicComplexFieldTypeEntity3();
        $e->bar = 156;
        $e->getBar();
    }

    public function testGetterDefaultValue()
    {
        $e = new \PublicComplexFieldTypeEntity3();
        $this->assertEquals('bar', $e->getBar2());
    }

    public function testSetter()
    {
        $this->assertTrue(method_exists('\ComplexFieldTypeEntity3', 'setBar'));
        $e = new \PublicComplexFieldTypeEntity3();
        $e->setBar('foo');
        $this->assertEquals(0, $e->bar);
        $e->setBar(1);
        $this->assertEquals(3, $e->bar);
        $e->setBar('1');
        $this->assertEquals(3, $e->bar);
        $e->setBar('foo bar');
        $this->assertEquals(6, $e->bar);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\PropelException
     */
    public function testSetterThrowsExceptionOnUnknownValue()
    {
        $e = new \ComplexFieldTypeEntity3();
        $e->setBar('bazz');
    }

    public function testValueIsPersisted()
    {
        $e = new \ComplexFieldTypeEntity3();
        $e->setBar('baz');
        $this->getRepository()->save($e);
        $e = \ComplexFieldTypeEntity3Query::create()->findOne();
        $this->assertEquals('baz', $e->getBar());
    }

    public function testValueIsCopied()
    {
        $e1 = new \ComplexFieldTypeEntity3();
        $e1->setBar('baz');
        $e2 = new \ComplexFieldTypeEntity3();
        $this->getRepository()->getEntityMap()->copyInto($e1, $e2);
        $this->assertEquals('baz', $e2->getBar());
    }

    /**
     * @see https://github.com/propelorm/Propel/issues/139
     */
    public function testSetterWithSameValueDoesNotUpdateObject()
    {
        $repository= $this->getRepository();

        $e = new \ComplexFieldTypeEntity3();
        $e->setBar('baz');
        $repository->save($e);
        $this->assertFalse($this->con->getSession()->isChanged($e));

        $e->setBar('baz');
        $this->assertFalse($this->con->getSession()->isChanged($e));
    }

    /**
     * @see https://github.com/propelorm/Propel/issues/139
     */
    public function testSetterWithSameValueDoesNotUpdateHydratedObject()
    {
        $repository = $this->getRepository();
        $e = new \ComplexFieldTypeEntity3();
        $e->setBar('baz');
        $repository->save($e);
        // force hydration
        $this->con->getSession()->clearFirstLevelCache();
        $e = $repository->createQuery()->findPk($e->getId());
        $e->setBar('baz');
        $this->assertFalse($this->con->getSession()->isChanged($e));
    }

    protected function getRepository($entityName = null)
    {
        if (null === $entityName) {
            $entityName = '\ComplexFieldTypeEntity3';
        }

        return $this->con->getRepository($entityName);
    }
}
