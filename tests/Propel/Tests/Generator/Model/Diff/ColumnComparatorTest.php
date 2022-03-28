<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Diff\ColumnComparator;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\TestCase;

/**
 * Tests for the ColumnComparator service class.
 */
class ColumnComparatorTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->platform = new MysqlPlatform();
    }

    /**
     * @return void
     */
    public function testCompareNoDifference()
    {
        $c1 = new Column('');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column('');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $this->assertEquals([], ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareType()
    {
        $c1 = new Column('');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c2 = new Column('');
        $c2->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $expectedChangedProperties = [
            'type' => ['VARCHAR', 'LONGVARCHAR'],
            'sqlType' => ['VARCHAR', 'TEXT'],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareScale()
    {
        $c1 = new Column('');
        $c1->getDomain()->replaceScale(2);
        $c2 = new Column('');
        $c2->getDomain()->replaceScale(3);
        $expectedChangedProperties = ['scale' => [2, 3]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareSize()
    {
        $c1 = new Column('');
        $c1->getDomain()->replaceSize(2);
        $c2 = new Column('');
        $c2->getDomain()->replaceSize(3);
        $expectedChangedProperties = ['size' => [2, 3]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareSqlType()
    {
        $c1 = new Column('');
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2 = new Column('');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->getDomain()->setSqlType('INTEGER(10) UNSIGNED');
        $expectedChangedProperties = ['sqlType' => ['INTEGER', 'INTEGER(10) UNSIGNED']];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareNotNull()
    {
        $c1 = new Column('');
        $c1->setNotNull(true);
        $c2 = new Column('');
        $c2->setNotNull(false);
        $expectedChangedProperties = ['notNull' => [true, false]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareDefaultValueToNull()
    {
        $c1 = new Column('');
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column('');
        $expectedChangedProperties = [
            'defaultValueType' => [ColumnDefaultValue::TYPE_VALUE, null],
            'defaultValueValue' => [123, null],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareDefaultValueFromNull()
    {
        $c1 = new Column('');
        $c2 = new Column('');
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'defaultValueType' => [null, ColumnDefaultValue::TYPE_VALUE],
            'defaultValueValue' => [null, 123],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareDefaultValueValue()
    {
        $c1 = new Column('');
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column('');
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(456, ColumnDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'defaultValueValue' => [123, 456],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareDefaultValueType()
    {
        $c1 = new Column('');
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column('');
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_EXPR));
        $expectedChangedProperties = [
            'defaultValueType' => [ColumnDefaultValue::TYPE_VALUE, ColumnDefaultValue::TYPE_EXPR],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @see http://www.propelorm.org/ticket/1141
     *
     * @return void
     */
    public function testCompareDefaultExrpCurrentTimestamp()
    {
        $c1 = new Column('');
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue('NOW()', ColumnDefaultValue::TYPE_EXPR));
        $c2 = new Column('');
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue('CURRENT_TIMESTAMP', ColumnDefaultValue::TYPE_EXPR));
        $this->assertEquals([], ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareAutoincrement()
    {
        $c1 = new Column('');
        $c1->setAutoIncrement(true);
        $c2 = new Column('');
        $c2->setAutoIncrement(false);
        $expectedChangedProperties = ['autoIncrement' => [true, false]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }

    /**
     * @return void
     */
    public function testCompareMultipleDifferences()
    {
        $c1 = new Column('');
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c1->setNotNull(false);
        $c2 = new Column('');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'type' => ['INTEGER', 'DOUBLE'],
            'sqlType' => ['INTEGER', 'DOUBLE'],
            'scale' => [null, 2],
            'size' => [null, 3],
            'notNull' => [false, true],
            'defaultValueType' => [null, ColumnDefaultValue::TYPE_VALUE],
            'defaultValueValue' => [null, 123],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2));
    }
}
