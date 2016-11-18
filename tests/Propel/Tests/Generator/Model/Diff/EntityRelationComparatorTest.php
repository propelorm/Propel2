<?php

/*
 *	$Id: EntityTest.php 1891 2010-08-09 15:03:18Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\EntityComparator;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Model\Database;
use \Propel\Tests\TestCase;

/**
 * Tests for the Field methods of the EntityComparator service class.
 */
class EntityRelationComparatorTest extends TestCase
{
    /**
     * @var MysqlPlatform
     */
    protected $platform;

    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameFks()
    {

        $c1 = new Field('Foo');
        $c2 = new Field('Bar');

        $fk1 = new Relation();
        $fk1->setForeignEntityName('Baz');
        $fk1->addReference($c1, $c2);

        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);

        $c3 = new Field('Foo');
        $c4 = new Field('Bar');

        $fk2 = new Relation();
        $fk2->setForeignEntityName('Baz');
        $fk2->addReference($c3, $c4);

        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);

        $diff = EntityComparator::computeDiff($t1, $t2);
        $this->assertFalse($diff);
    }

    public function testCompareNotSameFks()
    {
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');

        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);

        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);

        $t2 = new Entity('Baz');

        $diff = EntityComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof EntityDiff);
    }

    public function testCaseInsensitive()
    {
        $t1 = new Entity('Baz');
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1->addRelation($fk1);

        $t2 = new Entity('baz');
        $c3 = new Field('fOO');
        $c4 = new Field('bAR');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $t2->addRelation($fk2);

        $diff = EntityComparator::computeDiff($t1, $t2, true);
        echo $diff;
        $this->assertFalse($diff);
    }

    public function testCompareAddedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $t1 = new Entity('Baz');
        $db1->addEntity($t1);

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $c3 = new Field('foo');
        $c4 = new Field('bar');
        $fk2 = new Relation();
        $fk2->addReference($c3, $c4);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $db2->addEntity($t2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareRelations();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getAddedFks()));
        $this->assertEquals(array('baz_fk_4e99e8' => $fk2), $tableDiff->getAddedFks());
    }

    public function testCompareRemovedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation();
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $db1->addEntity($t1);

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $t2 = new Entity('Baz');
        $db2->addEntity($t2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareRelations();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getRemovedFks()));
        $this->assertEquals(array('baz_fk_4e99e8' => $fk1), $tableDiff->getRemovedFks());
    }

    public function testCompareModifiedFks()
    {
        $db1 = new Database();
        $db1->setPlatform($this->platform);
        $c1 = new Field('Foo');
        $c2 = new Field('Bar');
        $fk1 = new Relation('my_foreign_key');
        $fk1->addReference($c1, $c2);
        $t1 = new Entity('Baz');
        $t1->addRelation($fk1);
        $db1->addEntity($t1);

        $db2 = new Database();
        $db2->setPlatform($this->platform);
        $c3 = new Field('Foo');
        $c4 = new Field('Bar2');
        $fk2 = new Relation('my_foreign_key');
        $fk2->addReference($c3, $c4);
        $t2 = new Entity('Baz');
        $t2->addRelation($fk2);
        $db2->addEntity($t2);

        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $nbDiffs = $tc->compareRelations();
        $tableDiff = $tc->getEntityDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, count($tableDiff->getModifiedFks()));
        $this->assertEquals(array('my_foreign_key' => array($fk1, $fk2)), $tableDiff->getModifiedFks());
    }
}
