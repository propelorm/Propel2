<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sluggable;

use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Configuration;
use Propel\Tests\TestCase;

/**
 * Tests for SluggableBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class SluggableBehaviorTest extends TestCase
{
    /**
     * @var \Propel\Runtime\Configuration
     */
    protected $configuration;

    protected function setUp()
    {
        if (!class_exists('\Entity13')) {
            $schema = <<<XML
<database name = "bookstore-behavior" defaultIdMethod = "native">

    <entity name = "Entity13" >
        <field name = "id" required = "true" primaryKey = "true" autoIncrement = "true" type = "INTEGER" />
        <field name = "title" type = "VARCHAR" size = "100" primaryString = "true" />
        <behavior name = "sluggable" />
    </entity >

    <entity name = "Entity14" >
        <field name = "id" required = "true" primaryKey = "true" autoIncrement = "true" type = "INTEGER" />
        <field name = "title" type = "VARCHAR" size = "100" primaryString = "true" />
        <field name = "url" type = "VARCHAR" size = "100" />
        <behavior name = "sluggable" >
            <parameter name = "slug_field" value = "url" />
            <parameter name = "slug_pattern" value = "/foo/{Title}/bar" />
            <parameter name = "replace_pattern" value = "/[^\w\/]+/" />
            <parameter name = "separator" value = "/" />
            <parameter name = "permanent" value = "true" />
        </behavior >
    </entity >

    <entity name = "EntityWithScope" >
        <field name = "id" required = "true" primaryKey = "true" autoIncrement = "true" type = "INTEGER" />
        <field name = "scope" type = "INTEGER" required = "false" />
        <field name = "title" type = "VARCHAR" size = "100" primaryString = "true" />
        <behavior name = "sluggable" >
            <parameter name = "scope_field" value = "scope" />
        </behavior >
    </entity >

</database >
XML;
            $this->configuration = QuickBuilder::buildSchema($schema);
        }
    }

    public function getConfiguration()
    {
        if (null === $this->configuration) {
            $this->configuration = Configuration::getCurrentConfiguration();
        }

        return $this->configuration;
    }

    /**
     * @expectedException \Propel\Generator\Exception\BuildException
     */
    public function testNoPrimaryStringAndNoPatternThrowsException()
    {
        $schema = <<<XML
<database name = "bookstore-behavior" defaultIdMethod = "native">
    <entity name = "Entity15" >
        <field name = "id" required = "true" primaryKey = "true" autoIncrement = "true" type = "INTEGER" />
        <field name = "title" type = "VARCHAR" size = "100" />
        <behavior name = "sluggable" />
    </entity >
</database>
XML;
        QuickBuilder::buildSchema($schema);
    }

    public function testParameters()
    {
        $entity13 = $this->getConfiguration()->getEntityMap('\Entity13');
        $this->assertEquals(count($entity13->getFields()), 3, 'Sluggable adds one fields by default');
        $this->assertTrue(method_exists('\Entity13', 'getSlug'), 'Sluggable adds a slug field by default');
        $entity14 = $this->getConfiguration()->getEntityMap('\Entity14');
        $this->assertEquals(count($entity14->getFields()), 3, 'Sluggable does not add a field when it already exists');
        $this->assertTrue(method_exists('\Entity14', 'getUrl'), 'Sluggable allows customization of slug_field name');
        $this->assertTrue(method_exists('\Entity14', 'getSlug'), 'Sluggable adds a standard getter for the slug field');
    }

    public function testObjectGetter()
    {
        $this->assertTrue(method_exists('\Entity13', 'getSlug'), 'Sluggable adds a getter for the slug field');
        $t = new \Entity13();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getSlug(), 'getSlug() returns the object slug');
        $this->assertTrue(method_exists('\Entity14', 'getSlug'), 'Sluggable adds a getter for the slug field, even if the field does not have the default name');
        $t = new \Entity14();
        $t->setUrl('foo');
        $this->assertEquals('foo', $t->getSlug(), 'getSlug() returns the object slug');
    }

    public function testObjectSetter()
    {
        $this->assertTrue(method_exists('\Entity13', 'setSlug'), 'Sluggable adds a setter for the slug field');
        $t = new \Entity13();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getSlug(), 'setSlug() sets the object slug');
        $this->assertTrue(method_exists('\Entity14', 'setSlug'), 'Sluggable adds a setter for the slug field, even if the field does not have the default name');
        $t = new \Entity14();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getUrl(), 'setSlug() sets the object slug');
    }

    public function cleanupSlugProvider()
    {
        return array(
            array('', 'n-a'),
            array('foo', 'foo'),
            array('foo bar', 'foo-bar'),
            array('foo  bar', 'foo-bar-1'),
            array('FoO', 'foo-1'),
            array('fôo', 'foo-2'),
            array(' foo ', 'foo-3'),
            array('f/o:o', 'f-o-o'),
            array('foo1', 'foo1'),
        );
    }

    /**
     * @dataProvider cleanupSlugProvider
     */
    public function testCleanupSlugPart($in, $out)
    {
        $repository = $this->getConfiguration()->getRepository('\Entity13');

        $t = new \Entity13();
        $t->setSlug($in);
        $repository->save($t);
        $this->assertEquals($out, $t->getSlug(), 'cleanupSlugPart() cleans up the slug part');
    }

    public function limitSlugSizeProvider()
    {
        return array(
            array('123', '123'),
            array(str_repeat('A', 80), str_repeat('a', 80)),
            array(str_repeat('B', 97), str_repeat('b', 97)),
            array(str_repeat('C', 98), str_repeat('c', 97)),
            array(str_repeat('D', 99), str_repeat('d', 97)),
            array(str_repeat('E', 100), str_repeat('e', 97)),
            array(str_repeat('F', 150), str_repeat('f', 97)),
        );
    }

    /**
     * @dataProvider limitSlugSizeProvider
     */
    public function testObjectLimitSlugSize($in, $out)
    {
        $repository = $this->getConfiguration()->getRepository('\Entity14');
        $t = new \Entity14();
        $t->setSlug($in);
        $repository->save($t);
        $this->assertEquals($out, $t->getSlug(), 'the slug size islimited');
    }

    public function testObjectCreateSlug()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity13');
        $repository->deleteAll();

        $t0 = new \Entity13();
        $repository->save($t0);
        $this->assertEquals('n-a', $t0->getSlug(), 'for an empty object the slug is n-a');

        $t = new \Entity13();
        $t->setTitle('Hello, World!');
        $repository->save($t);
        $this->assertEquals('hello-world', $t->getSlug(), 'saving a non-empty object, creates a clean slug');


        $t1 = new \Entity13();
        $t1->setTitle('Hello; wOrld');
        $repository->save($t1);
        $this->assertEquals('hello-world-1', $t1->getSlug(), 'The slug is unique');

        $t1->setTitle('Hello My Awesome World');
        $repository->save($t1);
        $this->assertEquals('hello-my-awesome-world', $t1->getSlug(), 'Changing the primary string will change the slug, too');

        $repository1 = $this->getConfiguration()->getRepository('\Entity14');
        $repository1->deleteAll();

        $t2 = new \Entity14();
        $repository1->save($t2);
        $this->assertEquals('/foo/n-a/bar', $t2->getSlug(), 'returns a slug for an empty object with a pattern');

        $t3 = new \Entity14();
        $t3->setTitle('Hello, World!');
        $repository1->save($t3);
        $this->assertEquals('/foo/hello-world/bar', $t3->getSlug(), 'returns a cleaned up slug');

        $t4 = new \Entity14();
        $t4->setTitle('Hello; wOrld:');
        $repository1->save($t4);
        $this->assertEquals('/foo/hello-world/bar/1', $t4->getSlug(), 'returns a unique slug');

        $repository->deleteAll();
        for ($i = 0; $i <= 5; $i++) {
            $t5 = new \Entity13();
            $t5->setTitle('Hello, World!');
            $repository->save($t5);
        }
        $this->assertEquals('hello-world-5', $t5->getSlug(), 'the slug is correctly incremented');
    }

    public function testObjectPreSave()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity14');
        $repository->deleteAll();

        $t = new \Entity14();
        $repository->save($t);
        $this->assertEquals('/foo/n-a/bar', $t->getSlug(), 'preSave() sets a default slug for empty objects');
        $t = new \Entity14();
        $t->setTitle('Hello, World');
        $repository->save($t);
        $this->assertEquals('/foo/hello-world/bar', $t->getSlug(), 'preSave() sets a cleaned up slug for objects');
        $t = new \Entity14();
        $t->setTitle('Hello, World');
        $repository->save($t);
        $this->assertEquals('/foo/hello-world/bar/1', $t->getSlug(), 'preSave() sets a unique slug for objects');
        $t = new \Entity14();
        $t->setTitle('Hello, World');
        $t->setSlug('/foo/custom/bar');
        $repository->save($t);
        $this->assertEquals('/foo/custom/bar', $t->getSlug(), 'preSave() uses the given slug if it exists');
        $t = new \Entity14();
        $t->setTitle('Hello, World');
        $t->setSlug('/foo/custom/bar');
        $repository->save($t);
        $this->assertEquals('/foo/custom/bar/1', $t->getSlug(), 'preSave() uses the given slug if it exists and makes it unique');
    }

    public function testObjectSlugLifecycle()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity13');
        $repository->deleteAll();

        $t = new \Entity13();
        $t->setTitle('Hello, World');
        $repository->save($t);
        $this->assertEquals('hello-world', $t->getSlug(), 'preSave() creates a slug for new objects');
        $t->setSlug('hello-bar');
        $repository->save($t);
        $this->assertEquals('hello-bar', $t->getSlug(), 'setSlug() allows to override default slug');
        $t->setSlug('');
        $repository->save($t);
        $this->assertEquals('hello-world', $t->getSlug(), 'setSlug(null) relaunches the slug generation');

        $repository = $this->getConfiguration()->getRepository('\Entity14');
        $repository->deleteAll();

        $t = new \Entity14();
        $t->setTitle('Hello, World2');
        $t->setSlug('hello-bar2');
        $repository->save($t);
        $this->assertEquals('hello-bar2', $t->getSlug(), 'setSlug() allows to override default slug, even before save');
        $t->setSlug('');
        $repository->save($t);
        $this->assertEquals('/foo/hello-world2/bar', $t->getSlug(), 'setSlug(null) relaunches the slug generation');
    }

    public function testObjectSlugAutoUpdate()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity13');
        $repository->deleteAll();

        $t = new \Entity13();
        $t->setTitle('Hello, World');
        $repository->save($t);
        $this->assertEquals('hello-world', $t->getSlug(), 'preSave() creates a slug for new objects');
        $t->setTitle('Hello, My World');
        $repository->save($t);
        $this->assertEquals('hello-my-world', $t->getSlug(), 'preSave() autoupdates slug on object change');
        $t->setTitle('Hello, My Whole New World');
        $t->setSlug('hello-bar');
        $repository->save($t);
        $this->assertEquals('hello-bar', $t->getSlug(), 'preSave() does not autoupdate slug when it was set by the user');
    }

    public function testObjectSlugAutoUpdatePermanent()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity14');
        $repository->deleteAll();

        $t = new \Entity14();
        $t->setTitle('Hello, World');
        $repository->save($t);
        $this->assertEquals('/foo/hello-world/bar', $t->getSlug(), 'preSave() creates a slug for new objects');
        $t->setTitle('Hello, My World');
        $repository->save($t);
        $this->assertEquals('/foo/hello-world/bar', $t->getSlug(), 'preSave() does not autoupdate slug on object change for permanent slugs');
        $t->setSlug('hello-bar');
        $repository->save($t);
        $this->assertEquals('hello-bar', $t->getSlug(), 'setSlug() still works for permanent slugs');
    }

    public function testObjectSlugWithScope()
    {
        $repository = $this->getConfiguration()->getRepository('\EntityWithScope');

        $t = new \EntityWithScope();
        $t->setTitle('Hello World');
        $t->setScope(1);
        $repository->save($t);
        $t1 = new \EntityWithScope();
        $t1->setTitle('Hello World');
        $t1->setScope(2);
        $repository->save($t1);
        $this->assertEquals($t->getSlug(), $t1->getSlug(), 'Same slugs can coexist in different scopes');

        for ($i = 0; $i < 5; $i++) {
            $t2 = new \EntityWithScope();
            $t2->setTitle('Hello World');
            $t2->setScope(1);
            $repository->save($t2);
        }
        $t3 = new \EntityWithScope();
        $t3->setTitle('Hello World');
        $t3->setScope(2);
        $repository->save($t3);
        $this->assertEquals('hello-world-5', $t2->getSlug());
        $this->assertEquals('hello-world-1', $t3->getSlug());

        $this->assertNotEquals($t2->getSlug(), $t3->getSlug(), 'slugs are incremented separately for each scope');
    }

    public function testQueryFindOneBySlug()
    {
        $this->assertFalse(method_exists('\Entity13Query', 'findOneBySlug'), 'The generated query does not provide a findOneBySlug() method if the slug field is "slug".');
        $this->assertTrue(method_exists('\Entity14Query', 'findOneBySlug'), 'The generated query provides a findOneBySlug() method even if the slug field doesn\'t have the default name');

        $repository = $this->getConfiguration()->getRepository('\Entity14');
        $repository->deleteAll();

        $t1 = new \Entity14();
        $t1->setTitle('Hello, World');
        $repository->save($t1);
        $t2 = new \Entity14();
        $t2->setTitle('Hello, Cruel World');
        $repository->save($t2);
        $t = $repository->createQuery()->findOneBySlug('/foo/hello-world/bar');
        $this->assertEquals($t1, $t, 'findOneBySlug() returns a single object matching the slug');
    }

    public function testQueryFindOneBySlugWithScope()
    {
        $repository = $this->getConfiguration()->getRepository('\EntityWithScope');
        $repository->deleteAll();

        $t1 = new \EntityWithScope();
        $t1->setTitle('Hello, World');
        $t1->setScope(1);
        $repository->save($t1);
        $t2 = new \EntityWithScope();
        $t2->setTitle('Hello, Cruel World');
        $t2->setScope(1);
        $repository->save($t2);
        $t = $repository->createQuery()->findOneBySlug('hello-world', 1);
        $this->assertEquals($t1, $t, 'findOneBySlug() returns a single object matching the slug in the given scope');

        $t = $repository->createQuery()->findOneBySlug('hello-world', 2);
        $this->assertNull($t, 'findOneBySlug() searches for the slug in the given scope');
    }

    public function testNumberOfQueriesForMakeUniqueSlug()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity13');
        $repository->deleteAll();

        $con = $this->getConfiguration()->getConnectionManager(\Map\Entity13EntityMap::DATABASE_NAME)->getReadConnection();

        $expectedCount = 4;

        for ($i=0; $i < 5; $i++) {
            $nbQuery = $con->getQueryCount();

            $t = new \Entity13();
            $t->setTitle('Hello, World');
            $repository->save($t, $con);

            $this->assertLessThanOrEqual($expectedCount, $con->getQueryCount() - $nbQuery, "no more than $expectedCount query to get a slug when it already exist");
        }
    }

    public function testSlugRegexp()
    {
        $repository = $this->getConfiguration()->getRepository('\Entity13');
        $repository->deleteAll();
        $con = $this->getConfiguration()->getConnectionManager(\Map\Entity13EntityMap::DATABASE_NAME)->getReadConnection();

        for ($i=0; $i < 3; $i++) {
            $t = new \Entity13();
            $t->setTitle('Hello, World');
            $repository->save($t, $con);
        }
        $this->assertEquals('hello-world-2', $t->getSlug());

        $t = new \Entity13();
        $t->setTitle('World');
        $repository->save($t, $con);

        $this->assertEquals('world', $t->getSlug());

        $t = new \Entity13();
        $t->setTitle('World');
        $repository->save($t, $con);

        $this->assertEquals('world-1', $t->getSlug());

        $t = new \Entity13();
        $t->setTitle('Hello, World');
        $repository->save($t, $con);

        $this->assertEquals('hello-world-3', $t->getSlug());

        $t = new \Entity13();
        $t->setTitle('World');
        $repository->save($t, $con);

        $this->assertEquals('world-2', $t->getSlug());

        $t = new \Entity13();
        $t->setTitle('World 000');
        $repository->save($t, $con);

        $this->assertEquals('world-000', $t->getSlug());

        $t = new \Entity13();
        $t->setTitle('World');
        $repository->save($t, $con);

        $this->assertEquals('world-3', $t->getSlug(), 'world-000 is considered as world-0');

        $t = new \Entity13();
        $t->setTitle('World');
        $repository->save($t, $con);

        $this->assertEquals('world-4', $t->getSlug());
    }
}
