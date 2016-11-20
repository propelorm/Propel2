<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Timestampable;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Behavior\Entity1;
use Propel\Tests\Bookstore\Behavior\Map\Entity1EntityMap;
use Propel\Tests\Bookstore\Behavior\Entity2;
use Propel\Tests\Bookstore\Behavior\Map\Entity2EntityMap;
use Propel\Tests\Bookstore\Behavior\Entity2Query;

use Propel\Runtime\Collection\ObjectCollection;

/**
 * Tests for TimestampableBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class TimestampableBehaviorTest extends BookstoreTestBase
{
    private function assertTimeEquals($expected, $actual, $message = '')
    {
        // accept $expected or ($expected + 1s)
        return $this->assertThat(
            $actual,
            $this->logicalOr(
                $this->equalTo($expected),
                $this->equalTo($expected + 1)
            ),
            $message
        );
    }

    public function testParameters()
    {
        $entity2 = Entity2EntityMap::getEntityMap();
        $this->assertEquals(count($entity2->getFields()), 4, 'Timestampable adds two columns by default');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity2', 'getCreatedAt'), 'Timestampable adds a created_at column by default');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity2', 'getUpdatedAt'), 'Timestampable adds an updated_at column by default');
        $entity1 = Entity1EntityMap::getEntityMap();
        $this->assertEquals(count($entity1->getFields()), 4, 'Timestampable does not add two columns when they already exist');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity1', 'getCreatedOn'), 'Timestampable allows customization of create_column name');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity1', 'getUpdatedOn'), 'Timestampable allows customization of update_column name');
    }

    public function testPreSave()
    {
        $t1 = new Entity2();
        $this->assertNull($t1->getUpdatedAt());
        $t1->save();
        $this->assertNotNull($t1->getUpdatedAt(), 'Timestampable sets updated_column to time() on creation');
        $current = $t1->getUpdatedAt();
        sleep(1);

        $t1->setTitle('foo');
        $t1->save();

        $this->assertNotEquals($current, $t1->getUpdatedAt(), 'Timestampable changes updated_column to time() on update');
    }

    public function testPreSaveNoChange()
    {
        $t1 = new Entity2();
        $this->assertNull($t1->getUpdatedAt());
        $tsave = time();
        $t1->save();
        $this->assertTimeEquals($tsave, $t1->getUpdatedAt('U'), 'Timestampable sets updated_column to time() on creation');
        sleep(1);
        $tupdate = time();
        $t1->save();
        $this->assertTimeEquals($tsave, $t1->getUpdatedAt('U'), 'Timestampable only changes updated_column if the object was modified');
    }

    public function testPreSaveManuallyUpdated()
    {
        $t1 = new Entity2();
        $t1->setUpdatedAt(time() - 10);
        $tsave = time();
        $t1->save();
        $this->assertLessThan($tsave, $t1->getUpdatedAt('U'), 'Timestampable does not set updated_column to time() on creation when it is set by the user');
        // tip: if I set it to time()-10 a second time, the object sees that I want to change it to the same value
        // and skips the update, therefore the updated_at is not in the list of modified columns,
        // and the behavior changes it to the current date... let's say it's an edge case
        $t1->setUpdatedAt(time() - 15);
        $tupdate = time();
        $t1->save();
        $this->assertLessThan($tupdate, $t1->getUpdatedAt('U'), 'Timestampable does not change updated_column to time() on update when it is set by the user');
    }

    public function testPreInsert()
    {
        $t1 = new Entity2();
        $this->assertNull($t1->getCreatedAt());
        $tsave = time();
        $t1->save();
        $this->assertTimeEquals($tsave, $t1->getCreatedAt('U'), 'Timestampable sets created_column to time() on creation');
        sleep(1);
        $t1->setTitle('foo');
        $tupdate = time();
        $t1->save();
        $this->assertTimeEquals($tsave, $t1->getCreatedAt('U'), 'Timestampable does not update created_column on update');
    }

    public function testPreInsertManuallyUpdated()
    {
        $t1 = new Entity2();
        $t1->setCreatedAt(time() - 10);
        $tsave = time();
        $t1->save();
        $this->assertLessThan($tsave, $t1->getCreatedAt('U'), 'Timestampable does not set created_column to time() on creation when it is set by the user');
    }

    /**
     * @group test
     */
    public function testObjectKeepUpdateDateUnchanged()
    {
        $t1 = new Entity2();
        $t1->setUpdatedAt(time() - 10);
        $tsave = time();
        $t1->save();
        $this->assertLessThan($tsave, $t1->getUpdatedAt('U'));
        $this->assertFalse(
            $this->getConfiguration()->getEntityMapForEntity($t1)->isFieldModified($t1, 'updatedAt'),
            'updatedAt should not be modified after save call'
        );
        // let's save it a second time; the updated_at should be changed
        $t1->setTitle('foo');
        $tsave = time();
        $t1->save();
        $this->assertTimeEquals($tsave, $t1->getUpdatedAt('U'));

        // now let's do this a second time
        $t1 = new Entity2();
        $t1->setUpdatedAt(time() - 10);
        $tsave = time();
        $t1->save();
        $this->assertLessThan($tsave, $t1->getUpdatedAt('U'));
    }

    protected function populateUpdatedAt()
    {
        Entity2Query::create()->deleteAll();
        $ts = new ObjectCollection();
        $ts->setModel('\Propel\Tests\Bookstore\Behavior\Entity2');
        for ($i=0; $i < 10; $i++) {
            $t = new Entity2();
            $t->setTitle('UpdatedAt' . $i);
            /* additional -30 in case the check is done in the same second (which we can't guarantee, so no assert(8 ...) below).*/
            $t->setUpdatedAt(time() - $i * 24 * 60 * 60 - 30);
            $ts[]= $t;
        }
        $ts->save();
    }

    protected function populateCreatedAt()
    {
        Entity2Query::create()->deleteAll();
        $ts = new ObjectCollection();
        $ts->setModel('\Propel\Tests\Bookstore\Behavior\Entity2');
        for ($i=0; $i < 10; $i++) {
            $t = new Entity2();
            $t->setTitle('CreatedAt' . $i);
            $t->setCreatedAt(time() - $i * 24 * 60 * 60 - 30);
            $ts[]= $t;
        }
        $ts->save();
    }

    public function testQueryRecentlyUpdated()
    {
        $q = Entity2Query::create()->recentlyUpdated();
        $this->assertTrue($q instanceof Entity2Query, 'recentlyUpdated() returns the current Query object');
        $this->populateUpdatedAt();
        $ts = Entity2Query::create()->recentlyUpdated()->count();
        $this->assertEquals(7, $ts, 'recentlyUpdated() returns the elements updated in the last 7 days by default');
        $ts = Entity2Query::create()->recentlyUpdated(5)->count();
        $this->assertEquals(5, $ts, 'recentlyUpdated() accepts a number of days as parameter');
    }

    public function testQueryRecentlyCreated()
    {
        $q = Entity2Query::create()->recentlyCreated();
        $this->assertTrue($q instanceof Entity2Query, 'recentlyCreated() returns the current Query object');
        $this->populateCreatedAt();
        $ts = Entity2Query::create()->recentlyCreated()->count();
        $this->assertEquals(7, $ts, 'recentlyCreated() returns the elements created in the last 7 days by default');
        $ts = Entity2Query::create()->recentlyCreated(5)->count();
        $this->assertEquals(5, $ts, 'recentlyCreated() accepts a number of days as parameter');
    }

    public function testQueryLastUpdatedFirst()
    {
        $q = Entity2Query::create()->lastUpdatedFirst();
        $this->assertTrue($q instanceof Entity2Query, 'lastUpdatedFirst() returns the current Query object');
        $this->populateUpdatedAt();
        $t = Entity2Query::create()->lastUpdatedFirst()->findOne();
        $this->assertEquals('UpdatedAt0', $t->getTitle(), 'lastUpdatedFirst() returns element with most recent update date first');
    }

    public function testQueryFirstUpdatedFirst()
    {
        $q = Entity2Query::create()->firstUpdatedFirst();
        $this->assertTrue($q instanceof Entity2Query, 'firstUpdatedFirst() returns the current Query object');
        $this->populateUpdatedAt();
        $t = Entity2Query::create()->firstUpdatedFirst()->findOne();
        $this->assertEquals('UpdatedAt9', $t->getTitle(), 'firstUpdatedFirst() returns the element with oldest updated date first');
    }

    public function testQueryLastCreatedFirst()
    {
        $q = Entity2Query::create()->lastCreatedFirst();
        $this->assertTrue($q instanceof Entity2Query, 'lastCreatedFirst() returns the current Query object');
        $this->populateCreatedAt();
        $t = Entity2Query::create()->lastCreatedFirst()->findOne();
        $this->assertEquals('CreatedAt0', $t->getTitle(), 'lastCreatedFirst() returns element with most recent create date first');
    }

    public function testQueryFirstCreatedFirst()
    {
        $q = Entity2Query::create()->firstCreatedFirst();
        $this->assertTrue($q instanceof Entity2Query, 'firstCreatedFirst() returns the current Query object');
        $this->populateCreatedAt();
        $t = Entity2Query::create()->firstCreatedFirst()->findOne();
        $this->assertEquals('CreatedAt9', $t->getTitle(), 'firstCreatedFirst() returns the element with oldest create date first');
    }

    public function testDisableUpdatedAt()
    {
        $schema = <<<EOF
<database name="TimestampableBehaviorTest:testDisableUpdatedAt">
    <entity name="EntityWithoutUpdatedAt" activeRecord="true">
        <field name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />
        <field name="name" type="varchar" />

        <behavior name="timestampable">
            <parameter name="disable_updated_at" value="true" />
        </behavior>
    </entity>
</database>
EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->build('sqlite:memory:');

        $this->assertTrue(method_exists('EntityWithoutUpdatedAt', 'getCreatedAt'));
        $this->assertTrue(method_exists('EntityWithoutUpdatedAt', 'setCreatedAt'));
        $this->assertFalse(method_exists('EntityWithoutUpdatedAt', 'getUpdatedAt'));
        $this->assertFalse(method_exists('EntityWithoutUpdatedAt', 'setUpdatedAt'));

        $obj = new \EntityWithoutUpdatedAt();
        $obj->setName('Peter');
        $this->assertNull($obj->getCreatedAt());
        $obj->save();
        $this->assertNotNull($obj->getCreatedAt());
    }

    public function testDisableCreatedAt()
    {
        $schema = <<<EOF
<database name="TimestampableBehaviorTest:testDisableCreatedAt">
    <entity name="EntityWithoutCreatedAt" activeRecord="true">
        <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" />

        <behavior name="timestampable">
            <parameter name="disable_created_at" value="true" />
        </behavior>
    </entity>
</database>
EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->build('sqlite:memory:');

        $this->assertFalse(method_exists('EntityWithoutCreatedAt', 'getCreatedAt'));
        $this->assertFalse(method_exists('EntityWithoutCreatedAt', 'setCreatedAt'));
        $this->assertTrue(method_exists('EntityWithoutCreatedAt', 'getUpdatedAt'));
        $this->assertTrue(method_exists('EntityWithoutCreatedAt', 'setUpdatedAt'));

        $obj = new \EntityWithoutCreatedAt();
        $obj->setName('Peter');
        $this->assertNull($obj->getUpdatedAt());
        $obj->save();
        $this->assertNotNull($obj->getUpdatedAt());
    }
}
