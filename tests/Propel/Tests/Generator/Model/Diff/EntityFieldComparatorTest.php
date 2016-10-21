<?php

/*
 *	$Id: EntityTest.php 1891 2010-08-09 15:03:18Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\EntityComparator;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Diff\FieldComparator;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the Field methods of the EntityComparator service class.
 *
 */
class EntityFieldComparatorTest extends TestCase
{
    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameFields()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $this->assertFalse(EntityComparator::computeDiff($t1, $t2));
    }

    public function testCompareNotSameFields()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Bar');
        $t2->addField($c2);

        $diff = EntityComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof EntityDiff);
    }

    public function testCompareCaseInsensitive()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('fOO');
        $t2->addField($c2);

        $diff = EntityComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof EntityDiff);

        $this->assertFalse(EntityComparator::computeDiff($t1, $t2, true));
    }

    public function testCompareAddedField()
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

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getAddedFields()));
        $this->assertEquals(array('foo' => $c2), $entityDiff->getAddedFields());
    }

    public function testCompareRemovedField()
    {
        $t1 = new Entity();
        $c1 = new Field('Bar');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $t2 = new Entity();

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getRemovedFields()));
        $this->assertEquals(array('bar' => $c1), $entityDiff->getRemovedFields());
    }

    public function testCompareModifiedField()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getModifiedFields()));
        $fieldDiff = FieldComparator::computeDiff($c1, $c2);
        $this->assertEquals(array('foo' => $fieldDiff), $entityDiff->getModifiedFields());
    }

    public function testCompareRenamedField()
    {
        $t1 = new Entity();
        $c1 = new Field('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t1->addField($c1);
        $t2 = new Entity();
        $c2 = new Field('Bar');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($entityDiff->getRenamedFields()));
        $this->assertEquals(array(array($c1, $c2)), $entityDiff->getRenamedFields());
        $this->assertEquals(array(), $entityDiff->getAddedFields());
        $this->assertEquals(array(), $entityDiff->getRemovedFields());
    }

    public function testCompareSeveralFieldDifferences()
    {
        $t1 = new Entity();
        $c1 = new Field('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addField($c1);
        $c2 = new Field('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->setNotNull(true);
        $t1->addField($c2);
        $c3 = new Field('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->replaceSize(255);
        $t1->addField($c3);

        $t2 = new Entity();
        $c4 = new Field('col1');
        $c4->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c4->getDomain()->replaceScale(2);
        $c4->getDomain()->replaceSize(3);
        $c4->setNotNull(true);
        $c4->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c4);
        $c5 = new Field('col22');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c5->setNotNull(true);
        $t2->addField($c5);
        $c6 = new Field('col4');
        $c6->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $c6->getDomain()->setDefaultValue(new FieldDefaultValue('123', FieldDefaultValue::TYPE_VALUE));
        $t2->addField($c6);

        // col1 was modified, col2 was renamed, col3 was removed, col4 was added
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(4, $nbDiffs);
        $this->assertEquals(array(array($c2, $c5)), $entityDiff->getRenamedFields());
        $this->assertEquals(array('col4' => $c6), $entityDiff->getAddedFields());
        $this->assertEquals(array('col3' => $c3), $entityDiff->getRemovedFields());
        $fieldDiff = FieldComparator::computeDiff($c1, $c4);
        $this->assertEquals(array('col1' => $fieldDiff), $entityDiff->getModifiedFields());
    }

    public function testCompareSeveralRenamedSameFields()
    {
        $t1 = new Entity();
        $c1 = new Field('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $t1->addField($c1);
        $c2 = new Field('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c2->getDomain()->replaceSize(255);
        $t1->addField($c2);
        $c3 = new Field('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->replaceSize(255);
        $t1->addField($c3);

        $t2 = new Entity();
        $c4 = new Field('col4');
        $c4->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c4->getDomain()->replaceSize(255);
        $t2->addField($c4);
        $c5 = new Field('col5');
        $c5->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c5->getDomain()->replaceSize(255);
        $t2->addField($c5);
        $c6 = new Field('col3');
        $c6->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c6->getDomain()->replaceSize(255);
        $t2->addField($c6);

        // col1 and col2 were renamed
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareFields();
        $entityDiff = $tc->getEntityDiff();
        $this->assertEquals(2, $nbDiffs);
        $this->assertEquals(array(array($c1, $c4), array($c2, $c5)), $entityDiff->getRenamedFields());
        $this->assertEquals(array(), $entityDiff->getAddedFields());
        $this->assertEquals(array(), $entityDiff->getRemovedFields());
        $this->assertEquals(array(), $entityDiff->getModifiedFields());
    }

}
