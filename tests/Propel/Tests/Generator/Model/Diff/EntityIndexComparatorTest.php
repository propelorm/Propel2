<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Diff\EntityComparator;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the Field methods of the EntityComparator service class.
 *
 */
class EntityIndexComparatorTest extends TestCase
{
    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameIndices()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $t1->addIndex($i1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);
        $i2 = new Index('Foo_Index');
        $i2->addField($c2);
        $t2->addIndex($i2);

        $this->assertFalse(EntityComparator::computeDiff($t1, $t2));
    }

    public function testCompareNotSameIndices()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $t1->addIndex($i1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);
        $i2 = new Unique('Foo_Index');
        $i2->addField($c2);
        $t2->addIndex($i2);

        $diff = EntityComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof EntityDiff);
    }

    public function testCompareCaseInsensitive()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $t1->addIndex($i1);

        $t2 = new Entity();
        $c2 = new Field('fOO');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);
        $i2 = new Index('fOO_iNDEX');
        $i2->addField($c2);
        $t2->addIndex($i2);

        $this->assertFalse(EntityComparator::computeDiff($t1, $t2, $caseInsensitive = true));
        $this->assertNotFalse(EntityComparator::computeDiff($t1, $t2, $caseInsensitive = false));
    }

    public function testCompareAddedIndices()
    {
        $t1 = new Entity();
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);
        $i2 = new Index('Foo_Index');
        $i2->addField($c2);
        $t2->addIndex($i2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareIndices();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getAddedIndices()));
        $this->assertEquals(array('fooIndex' => $i2), $entityDiff->getAddedIndices());
    }

    public function testCompareRemovedIndices()
    {
        $t1 = new Entity();
        $c1 = new Field('Bar');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $i1 = new Index('Bar_Index');
        $i1->addField($c1);
        $t1->addIndex($i1);
        $t2 = new Entity();

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareIndices();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getRemovedIndices()));
        $this->assertEquals(array('barIndex' => $i1), $entityDiff->getRemovedIndices());
    }

    public function testCompareModifiedIndices()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addField($c1);
        $i1 = new Index('Foo_Index');
        $i1->addField($c1);
        $t1->addIndex($i1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);
        $i2 = new Unique('Foo_Index');
        $i2->addField($c2);
        $t2->addIndex($i2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareIndices();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getModifiedIndices()));
        $this->assertEquals(array('fooIndex' => array($i1, $i2)), $entityDiff->getModifiedIndices());
    }

}
