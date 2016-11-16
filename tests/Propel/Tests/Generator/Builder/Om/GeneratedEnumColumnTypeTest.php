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
 * Tests the generated EntityMap classes for enum column type constants
 *
 * @author Francois Zaninotto
 */
class GeneratedEnumColumnTypeTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('ComplexColumnTypeEntity103')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_103">
    <table name="complex_column_type_entity_103">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="ENUM" valueSet="foo, bar, baz, 1, 4,(, foo bar " />
    </table>
</database>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    public function valueSetConstantProvider()
    {
        return array(
            array('\ComplexColumnTypeEntity103::BAR_TYPE_FOO', 'foo'),
            array('\ComplexColumnTypeEntity103::BAR_TYPE_BAR', 'bar'),
            array('\ComplexColumnTypeEntity103::BAR_TYPE_BAZ', 'baz'),
            array('\ComplexColumnTypeEntity103::BAR_TYPE_1', '1'),
            array('\ComplexColumnTypeEntity103::BAR_TYPE_4', '4'),
            array('\ComplexColumnTypeEntity103::BAR_TYPE__', '('),
            array('\ComplexColumnTypeEntity103::BAR_TYPE_FOO_BAR', 'foo bar'),
        );
    }

    /**
     * @dataProvider valueSetConstantProvider
     */
    public function testValueSetConstants($constantName, $value)
    {
        $this->assertTrue(defined($constantName));
        $this->assertEquals($value, constant($constantName));
    }

    public function testGetValueSets()
    {
        $expected = array('foo', 'bar', 'baz', '1', '4', '(', 'foo bar');
        $this->assertEquals($expected, \ComplexColumnTypeEntity103::BAR_TYPES);
    }
}
