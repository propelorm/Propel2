<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Map\ComplexColumnTypeEntity103TableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests the generated TableMap classes for enum column type constants
 *
 * @author Francois Zaninotto
 */
class GeneratedTableMapEnumColumnTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('ComplexColumnTypeEntity103')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_103">
    <table name="complex_column_type_entity_103">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="ENUM" valueSet="foo, bar, baz, 1, 4,(, foo bar "/>
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function valueSetConstantProvider()
    {
        return [
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR_FOO', 'foo'],
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR_BAR', 'bar'],
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR_BAZ', 'baz'],
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR_1', '1'],
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR_4', '4'],
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR__', '('],
            ['\Map\ComplexColumnTypeEntity103TableMap::COL_BAR_FOO_BAR', 'foo bar'],
        ];
    }

    /**
     * @dataProvider valueSetConstantProvider
     *
     * @return void
     */
    public function testValueSetConstants($constantName, $value)
    {
        $this->assertTrue(defined($constantName));
        $this->assertEquals($value, constant($constantName));
    }

    /**
     * @return void
     */
    public function testGetValueSets()
    {
        $expected = [ComplexColumnTypeEntity103TableMap::COL_BAR => ['foo', 'bar', 'baz', '1', '4', '(', 'foo bar']];
        $this->assertEquals($expected, ComplexColumnTypeEntity103TableMap::getValueSets());
    }

    /**
     * @return void
     */
    public function testGetValueSet()
    {
        $expected = ['foo', 'bar', 'baz', '1', '4', '(', 'foo bar'];
        $this->assertEquals($expected, ComplexColumnTypeEntity103TableMap::getValueSet(ComplexColumnTypeEntity103TableMap::COL_BAR));
    }
}
