<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use ComplexColumnTypeJsonEntity;
use ComplexColumnTypeJsonEntityQuery;
use Map\ComplexColumnTypeJsonEntityTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;
use PublicComplexColumnTypeJsonEntity;

/**
 * Tests the generated objects for enum column types accessor & mutator
 *
 * @author Francois Zaninotto
 */
class GeneratedObjectJsonColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('ComplexColumnTypeJsonEntity')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_json">
    <table name="complex_column_type_json_entity">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
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

    /**
     * @return void
     */
    public function testGetter()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeJsonEntity', 'getBar'));
        $e = new ComplexColumnTypeJsonEntity();
        $this->assertInstanceOf('stdClass', $e->getBar(false));
        $e = new PublicComplexColumnTypeJsonEntity();
        $e->bar = '{"key":"value"}';
        $this->assertInstanceOf('stdClass', $e->getBar(false));
        $this->assertEquals('value', $e->getBar(false)->key);
        $this->assertEquals('value', $e->getBar()['key']);
    }

    /**
     * @return void
     */
    public function testSetter()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeJsonEntity', 'setBar'));
        $e = new PublicComplexColumnTypeJsonEntity();
        $e->setBar([
            'key' => 'value',
        ]);
        $this->assertEquals('{"key":"value"}', $e->bar);
        $e->setBar(null);
        $this->assertNull($e->getBar());
    }

    /**
     * @return void
     */
    public function testIsModified()
    {
        $this->assertTrue(method_exists('ComplexColumnTypeJsonEntity', 'setBar'));
        $e = new PublicComplexColumnTypeJsonEntity();
        $e->setBar([
          'key' => 'value',
        ]);
        $this->assertTrue($e->isModified());

        $e = new PublicComplexColumnTypeJsonEntity();
        $e->setBar([
          'defaultKey' => 'defaultValue',
        ]);
        $this->assertFalse($e->isModified());

        $e->setBar('{"defaultKey":"defaultValue"}');
        $this->assertFalse($e->isModified());

        $e->setBar('{"defaultKey"  :  "defaultValue"}');
        $this->assertFalse($e->isModified());

        $e->setBar((object)[
          'defaultKey' => 'defaultValue',
        ]);
        $this->assertFalse($e->isModified());

        $e->setBar((object)[
          'key' => 'value',
        ]);
        $this->assertTrue($e->isModified());
    }

    /**
     * @return void
     */
    public function testValueIsPersisted()
    {
        $e = new ComplexColumnTypeJsonEntity();
        $e->setBar([
            'key' => 'value',
        ]);
        $e->save();
        ComplexColumnTypeJsonEntityTableMap::clearInstancePool();
        $e = ComplexColumnTypeJsonEntityQuery::create()->findOne();
        $this->assertEquals('value', $e->getBar(false)->key);
        $this->assertEquals('value', $e->getBar()['key']);
    }

    /**
     * @return void
     */
    public function testValueIsCopied()
    {
        $e1 = new ComplexColumnTypeJsonEntity();
        $e1->setBar([
            'key' => 'value',
        ]);
        $e2 = new ComplexColumnTypeJsonEntity();
        $e1->copyInto($e2);
        $this->assertEquals('value', $e2->getBar()['key']);
        $this->assertEquals('value', $e2->getBar(false)->key);
    }
}
