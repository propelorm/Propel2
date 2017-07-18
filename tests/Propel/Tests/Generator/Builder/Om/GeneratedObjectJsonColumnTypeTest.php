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
use Propel\Tests\TestCase;

/**
 * Tests the generated objects for enum column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectJsonColumnTypeTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('ComplexColumnTypeJsonEntity')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_json">
    <table name="complex_column_type_json_entity">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="JSON" sqlType="json" default='{"defaultKey":"defaultValue"}'/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
            // ok this is hackish but it makes testing of getter and setter independent of each other
            $publicAccessorCode = <<<EOF
class PublicComplexColumnTypeJsonEntity extends ComplexColumnTypeJsonEntity
{
    public \$bar;
}
EOF;
            eval($publicAccessorCode);
        }
    }

    public function testGetter()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeJsonEntity', 'getBar'));
        $e = new \ComplexColumnTypeJsonEntity();
        $this->assertInstanceOf('stdClass', $e->getBar(false));
        $e = new \PublicComplexColumnTypeJsonEntity();
        $e->bar = '{"key":"value"}';
        $this->assertInstanceOf('stdClass', $e->getBar(false));
        $this->assertEquals('value', $e->getBar(false)->key);
        $this->assertEquals('value', $e->getBar()['key']);
    }

    public function testSetter()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeJsonEntity', 'setBar'));
        $e = new \PublicComplexColumnTypeJsonEntity();
        $e->setBar(array(
            'key' => 'value'
        ));
        $this->assertEquals('{"key":"value"}', $e->bar);
        $e->setBar(null);
        $this->assertNull($e->getBar());
    }
    
    public function testIsModified()
    {
      $this->assertTrue(method_exists('ComplexColumnTypeJsonEntity', 'setBar'));
      $e = new \PublicComplexColumnTypeJsonEntity();
      $e->setBar(array(
          'key' => 'value'
      ));
      $this->assertTrue($e->isModified());
      
      $e = new \PublicComplexColumnTypeJsonEntity();
      $e->setBar(array(
          'defaultKey' => 'defaultValue'
      ));
      $this->assertFalse($e->isModified());
      
      $e->setBar('{"defaultKey":"defaultValue"}');
      $this->assertFalse($e->isModified());
      
      $e->setBar('{"defaultKey"  :  "defaultValue"}');
      $this->assertFalse($e->isModified());
      
      $e->setBar((object)array(
          'defaultKey' => 'defaultValue'
      ));
      $this->assertFalse($e->isModified());
      
      $e->setBar((object)array(
          'key' => 'value'
      ));
      $this->assertTrue($e->isModified());
    }

    public function testValueIsPersisted()
    {
        $e = new \ComplexColumnTypeJsonEntity();
        $e->setBar(array(
            'key' => 'value'
        ));
        $e->save();
        \Map\ComplexColumnTypeJsonEntityTableMap::clearInstancePool();
        $e = \ComplexColumnTypeJsonEntityQuery::create()->findOne();
        $this->assertEquals('value', $e->getBar(false)->key);
        $this->assertEquals('value', $e->getBar()['key']);
    }

    public function testValueIsCopied()
    {
        $e1 = new \ComplexColumnTypeJsonEntity();
        $e1->setBar(array(
            'key' => 'value'
        ));
        $e2 = new \ComplexColumnTypeJsonEntity();
        $e1->copyInto($e2);
        $this->assertEquals('value', $e2->getBar()['key']);
        $this->assertEquals('value', $e2->getBar(false)->key);
    }
}
