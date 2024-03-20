<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\TestCase;

/**
 * Test class for ObjectBuilder.
 *
 * @author François Zaninotto
 * @version $Id$
 */
class ObjectBuilderTest extends TestCase
{
    protected $builder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $builder = new TestableObjectBuilder(new Table('Foo'));
        $builder->setPlatform(new MysqlPlatform());
        $this->builder = $builder;
    }

    public static function getDefaultValueStringProvider()
    {
        $col1 = new Column('Bar');
        $col1->setDomain(new Domain('VARCHAR'));
        $col1->setDefaultValue(new ColumnDefaultValue('abc', ColumnDefaultValue::TYPE_VALUE));
        $val1 = "'abc'";
        $col2 = new Column('Bar');
        $col2->setDomain(new Domain('INTEGER'));
        $col2->setDefaultValue(new ColumnDefaultValue(1234, ColumnDefaultValue::TYPE_VALUE));
        $val2 = '1234';
        $col3 = new Column('Bar');
        $col3->setDomain(new Domain('DATE'));
        $col3->setDefaultValue(new ColumnDefaultValue('0000-00-00', ColumnDefaultValue::TYPE_VALUE));
        $val3 = 'NULL';

        return [
            [$col1, $val1],
            [$col2, $val2],
            [$col3, $val3],
        ];
    }

    /**
     * @dataProvider getDefaultValueStringProvider
     *
     * @return void
     */
    public function testGetDefaultValueString($column, $value)
    {
        $this->assertEquals($value, $this->builder->getDefaultValueString($column));
    }

    /**
     * @return void
     */
    public function testGetDefaultKeyType()
    {
        $this->assertEquals('TYPE_PHPNAME', $this->builder->getDefaultKeyType());
    }
}

class TestableObjectBuilder extends ObjectBuilder
{
    public function getDefaultValueString(Column $col): string
    {
        return parent::getDefaultValueString($col);
    }
}
