<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sluggable;

use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Event\SaveEvent;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Behavior\Base\BaseEntity13Repository;
use Propel\Tests\Bookstore\Behavior\Base\BaseEntity14Repository;
use Propel\Tests\Bookstore\Behavior\Entity13;
use Propel\Tests\Bookstore\Behavior\Entity14;
use Propel\Tests\Bookstore\Map\BookEntityMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

use Propel\Tests\Bookstore\Behavior\Entity13Query;
use Propel\Tests\Bookstore\Behavior\Map\Entity13EntityMap;
use Propel\Tests\Bookstore\Behavior\Entity14Query;
use Propel\Tests\Bookstore\Behavior\Map\Entity14EntityMap;
use Propel\Tests\Bookstore\Behavior\EntityWithScope;
use Propel\Tests\Bookstore\Behavior\EntityWithScopeQuery;

/**
 * Tests for SluggableBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class SluggableBehaviorTest extends BookstoreTestBase
{
    protected function setUp()
    {
        parent::setUp();
    }


    public function testParameters()
    {
        $table13 = $this->getConfiguration()->getEntityMap(Entity13::class);
        $this->assertEquals(count($table13->getFields()), 3, 'Sluggable adds one columns by default');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity13', 'getSlug'), 'Sluggable adds a slug column by default');

        $table14 = $this->getConfiguration()->getEntityMap(Entity14::class);
        $this->assertEquals(count($table14->getFields()), 4, 'Sluggable does not add a column when it already exists');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity14', 'getUrl'), 'Sluggable allows customization of slug_column name');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity14', 'setUrl'), 'Sluggable allows customization of slug_column name');
    }

    public function testObjectGetter()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Entity13', 'getSlug'), 'Sluggable adds a getter for the slug column');
        $t = new Entity13();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getSlug(), 'getSlug() returns the object slug');
        $t = new Entity14();
        $t->setUrl('foo');
        $this->assertEquals('foo', $t->getUrl(), 'getSlug() returns the object slug');
    }

    public static function cleanupSlugProvider()
    {
        return array(
            array('', ''),
            array(null, ''),
            array('foo', 'foo'),
            array('foo bar', 'foo-bar'),
            array('foo  bar', 'foo-bar'),
            array('FoO', 'foo'),
            array('fôo', 'foo'),
            array(' foo ', 'foo'),
            array('f/o:o', 'f-o-o'),
            array('foo1', 'foo1'),
        );
    }

    /**
     * @dataProvider cleanupSlugProvider
     */
    public function testObjectCleanupSlugPart($in, $out)
    {
        /** @var BaseEntity13Repository $entity13Repository */
        $entity13Repository = $this->getConfiguration()->getRepository(Entity13::class);
        $this->assertEquals($out, $entity13Repository->cleanupSlugPart($in), 'cleanupSlugPart() cleans up the slug part');
    }

    public static function limitSlugSizeProvider()
    {
        return array(
            array('123', '123'),
            array(str_repeat('*', 80), str_repeat('*', 80)),
            array(str_repeat('*', 97), str_repeat('*', 97)),
            array(str_repeat('*', 98), str_repeat('*', 97)),
            array(str_repeat('*', 99), str_repeat('*', 97)),
            array(str_repeat('*', 100), str_repeat('*', 97)),
            array(str_repeat('*', 150), str_repeat('*', 97)),
        );
    }

    /**
     * @dataProvider limitSlugSizeProvider
     */
    public function testObjectLimitSlugSize($in, $out)
    {
        /** @var BaseEntity14Repository $entity14Repository */
        $entity14Repository = $this->getConfiguration()->getRepository(Entity14::class);
        $this->assertEquals($out, $entity14Repository->cleanupSlugPart($in), 'limitSlugsize() limits the slug size');
    }

    /**
     * Entity 14 has slug_pattern=/foo/{category}/bar/{Title} and separator=/ defined
     *
     * -> tests/Fixtures/bookstore/behavior-sluggable-schema.xml:16
     */
    public function testSlugPattern()
    {
        Entity14Query::create()->deleteAll();

        $entity = new Entity14;
        $entity->setTitle('My Supi title');
        $entity->setCategory('cool');
        $entitiesToInsert = [$entity];
        $entity->save();
        $this->assertEquals('/foo/cool/bar/my-supi-title', $entity->getUrl(), 'slug_pattern of /foo/{category}/bar/{Title} should be correctly replaced');

        $entity2 = clone $entity;
        $entity2->setId(null);
        $entity2->setUrl(null);
        $entity2->save();
        $this->assertEquals('/foo/cool/bar/my-supi-title/2', $entity2->getUrl(), 'slug_pattern of /foo/{category}/bar/{Title} should be correctly incremented');

        $entity3 = clone $entity2;
        $entity3->setId(null);
        $entity3->setUrl(null);
        $entity3->save();
        $this->assertEquals('/foo/cool/bar/my-supi-title/3', $entity3->getUrl(), 'slug_pattern of /foo/{category}/bar/{Title} should be correctly incremented');

        $entity3->setTitle('My');
        $entity3->save();
        $this->assertEquals('/foo/cool/bar/my', $entity3->getUrl(), 'slug_pattern of /foo/{category}/bar/{Title} should be correctly replaced');

        $entity3->setTitle('My Supi title');
        $entity3->save();
        $this->assertEquals('/foo/cool/bar/my-supi-title/3', $entity3->getUrl(), 'slug_pattern of /foo/{category}/bar/{Title} should be correctly replaced');
    }

    public function testBiggerChangeSet()
    {
        Entity14Query::create()->deleteAll();
        $entity = new Entity14;
        $entity->setTitle('My Supi');
        $entity->setCategory('cool');
        $this->getConfiguration()->getSession()->persist($entity);
        $this->getConfiguration()->getSession()->commit();

        $this->assertEquals('/foo/cool/bar/my-supi', $entity->getUrl());
        $entity->setTitle('My Supi title');


        $entity2 = new Entity14;
        $entity2->setTitle('My Supi title');
        $entity2->setCategory('cool');

        $entity3 = new Entity14;
        $entity3->setTitle('My Supi title');
        $entity3->setCategory('cool');


        //all three should now have the same slug. first should get the one without incrementation
        $this->getConfiguration()->getSession()->persist($entity);
        $this->getConfiguration()->getSession()->persist($entity2);
        $this->getConfiguration()->getSession()->persist($entity3);
        $this->getConfiguration()->getSession()->commit();

        $this->assertEquals('/foo/cool/bar/my-supi-title', $entity->getUrl());
        $this->assertEquals('/foo/cool/bar/my-supi-title/2', $entity2->getUrl());
        $this->assertEquals('/foo/cool/bar/my-supi-title/3', $entity3->getUrl());
    }
}
