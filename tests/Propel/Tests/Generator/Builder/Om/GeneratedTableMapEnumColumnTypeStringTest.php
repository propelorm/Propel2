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
 * Tests the generated TableMap classes for enum column type constants when using native ENUM string values
 *
 * @author Francois Zaninotto
 * @author Miroslav Oujesky
 */
class GeneratedTableMapEnumColumnTypeStringTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists('ComplexColumnTypeEntity104')) {
            $schema = <<<EOF
<database name="generated_object_complex_type_test_104">
    <table name="complex_column_type_entity_104">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="ENUM" valueSet="foo, bar, baz, 1, 4,(, foo bar " phpType="string" sqlType="ENUM('foo', 'bar', 'baz', '1', '4', '(', 'foo bar')" />
    </table>
</database>
EOF;

            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            $builder->buildClasses();
        }
    }

    public function valueSetConstantProvider()
    {
        return array(
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR_FOO', 'foo'),
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR_BAR', 'bar'),
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR_BAZ', 'baz'),
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR_1', '1'),
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR_4', '4'),
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR__', '('),
            array('\Map\ComplexColumnTypeEntity104TableMap::COL_BAR_FOO_BAR', 'foo bar'),
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
        $expected = array(\Map\ComplexColumnTypeEntity104TableMap::COL_BAR => array('foo' => 'foo', 'bar' => 'bar',
            'baz' => 'baz', '1' => '1', '4' => '4', '(' => '(', 'foo bar' => 'foo bar'));
        $this->assertEquals($expected, \Map\ComplexColumnTypeEntity104TableMap::getValueSets());

    }

    public function testGetValueSet()
    {
        $expected = array('foo' => 'foo', 'bar' => 'bar',
            'baz' => 'baz', '1' => '1', '4' => '4', '(' => '(', 'foo bar' => 'foo bar');
        $this->assertEquals($expected, \Map\ComplexColumnTypeEntity104TableMap::getValueSet(\Map\ComplexColumnTypeEntity104TableMap::COL_BAR));
    }
}
