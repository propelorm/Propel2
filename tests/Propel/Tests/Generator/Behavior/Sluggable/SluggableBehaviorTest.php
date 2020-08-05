<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Sluggable;

use Exception;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Behavior\Map\Table13TableMap;
use Propel\Tests\Bookstore\Behavior\Map\Table14TableMap;
use Propel\Tests\Bookstore\Behavior\Table13;
use Propel\Tests\Bookstore\Behavior\Table13Query;
use Propel\Tests\Bookstore\Behavior\Table14;
use Propel\Tests\Bookstore\Behavior\Table14Query;
use Propel\Tests\Bookstore\Behavior\TableWithScope;
use Propel\Tests\Bookstore\Behavior\TableWithScopeQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for SluggableBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class SluggableBehaviorTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        //prevent issue DSN not Found
        self::$isInitialized = false;
        parent::setUp();
        include_once(__DIR__ . '/SluggableBehaviorTestClasses.php');
    }

    /**
     * @return void
     */
    public function testParameters()
    {
        $table13 = Table13TableMap::getTableMap();
        $this->assertEquals(count($table13->getColumns()), 3, 'Sluggable adds one columns by default');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table13', 'getSlug'), 'Sluggable adds a slug column by default');
        $table14 = Table14TableMap::getTableMap();
        $this->assertEquals(count($table14->getColumns()), 3, 'Sluggable does not add a column when it already exists');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table14', 'getUrl'), 'Sluggable allows customization of slug_column name');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table14', 'getSlug'), 'Sluggable adds a standard getter for the slug column');
    }

    /**
     * @return void
     */
    public function testObjectGetter()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table13', 'getSlug'), 'Sluggable adds a getter for the slug column');
        $t = new Table13();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getSlug(), 'getSlug() returns the object slug');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table14', 'getSlug'), 'Sluggable adds a getter for the slug column, even if the column does not have the default name');
        $t = new Table14();
        $t->setUrl('foo');
        $this->assertEquals('foo', $t->getSlug(), 'getSlug() returns the object slug');
    }

    /**
     * @return void
     */
    public function testObjectSetter()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table13', 'setSlug'), 'Sluggable adds a setter for the slug column');
        $t = new Table13();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getSlug(), 'setSlug() sets the object slug');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table14', 'setSlug'), 'Sluggable adds a setter for the slug column, even if the column does not have the default name');
        $t = new Table14();
        $t->setSlug('foo');
        $this->assertEquals('foo', $t->getUrl(), 'setSlug() sets the object slug');
    }

    /**
     * @return void
     */
    public function testObjectCreateRawSlug()
    {
        $t = new TestableTable13();
        $this->assertEquals('n-a', $t->createRawSlug(), 'createRawSlug() returns an empty string for an empty object with no pattern');
        $t->setTitle('Hello, World');
        $this->assertEquals('hello-world', $t->createRawSlug(), 'createRawSlug() returns the cleaned up object string representation by default');

        $t = new TestableTable14();
        $this->assertEquals('/foo/n-a/bar', $t->createRawSlug(), 'createRawSlug() returns a slug for an empty object with a pattern');
        $t->setTitle('Hello, World');
        $this->assertEquals('/foo/hello-world/bar', $t->createRawSlug(), 'createRawSlug() returns a slug based on a pattern');
    }

    /**
     * @return string[][]
     */
    public static function cleanupSlugProvider()
    {
        return [
            ['', 'n-a'],
            ['foo', 'foo'],
            ['foo bar', 'foo-bar'],
            ['foo  bar', 'foo-bar'],
            ['FoO', 'foo'],
            ['fôo', 'foo'],
            [' foo ', 'foo'],
            ['f/o:o', 'f-o-o'],
            ['foo1', 'foo1'],
        ];
    }

    /**
     * @dataProvider cleanupSlugProvider
     *
     * @param string $in
     * @param string $out
     *
     * @return void
     */
    public function testObjectCleanupSlugPart($in, $out)
    {
        $t = new TestableTable13();
        $this->assertEquals($out, $t->cleanupSlugPart($in), 'cleanupSlugPart() cleans up the slug part');
    }

    /**
     * @return array
     */
    public static function limitSlugSizeProvider()
    {
        return [
            ['123', '123'],
            [str_repeat('*', 80), str_repeat('*', 80)],
            [str_repeat('*', 97), str_repeat('*', 97)],
            [str_repeat('*', 98), str_repeat('*', 97)],
            [str_repeat('*', 99), str_repeat('*', 97)],
            [str_repeat('*', 100), str_repeat('*', 97)],
            [str_repeat('*', 150), str_repeat('*', 97)],
        ];
    }

    /**
     * @dataProvider limitSlugSizeProvider
     *
     * @return void
     */
    public function testObjectLimitSlugSize($in, $out)
    {
        $t = new TestableTable14();
        $this->assertEquals($out, $t->limitSlugSize($in), 'limitSlugsize() limits the slug size');
    }

    /**
     * @return void
     */
    public function testObjectMakeSlugUnique()
    {
        Table13Query::create()->deleteAll();
        $t = new TestableTable13();
        $this->assertEquals('', $t->makeSlugUnique(''), 'makeSlugUnique() returns the input slug when the input is empty');
        $this->assertEquals('foo', $t->makeSlugUnique('foo'), 'makeSlugUnique() returns the input slug when the table is empty');
        $t->setSlug('foo');
        $t->save();
        $t = new TestableTable13();
        $this->assertEquals('bar', $t->makeSlugUnique('bar'), 'makeSlugUnique() returns the input slug when the table does not contain a similar slug');
        $t->save();
        $t = new TestableTable13();
        $this->assertEquals('foo-1', $t->makeSlugUnique('foo'), 'makeSlugUnique() returns an incremented input when it already exists');
        $t->setSlug('foo-1');
        $t->save();
        $t = new TestableTable13();
        $this->assertEquals('foo-2', $t->makeSlugUnique('foo'), 'makeSlugUnique() returns an incremented input when it already exists');
    }

    /**
     * @return void
     */
    public function testObjectCreateSlug()
    {
        Table13Query::create()->deleteAll();
        $t = new TestableTable13();
        $this->assertEquals('n-a', $t->createSlug(), 'createSlug() returns n-a for an empty object');
        $t->setTitle('Hello, World!');
        $this->assertEquals('hello-world', $t->createSlug(), 'createSlug() returns a cleaned up slug');
        $t->setSlug('hello-world');
        $t->save();
        $t = new TestableTable13();
        $t->setTitle('Hello; wOrld');
        $this->assertEquals('hello-world-1', $t->createSlug(), 'createSlug() returns a unique slug');

        Table14Query::create()->deleteAll();
        $t = new TestableTable14();
        $this->assertEquals('/foo/n-a/bar', $t->createSlug(), 'createSlug() returns a slug for an empty object with a pattern');
        $t->setTitle('Hello, World!');
        $this->assertEquals('/foo/hello-world/bar', $t->createSlug(), 'createSlug() returns a cleaned up slug');
        $t->setSlug('/foo/hello-world/bar');
        $t->save();
        $t = new TestableTable14();
        $t->setTitle('Hello; wOrld:');
        $this->assertEquals('/foo/hello-world/bar/1', $t->createSlug(), 'createSlug() returns a unique slug');
    }

    /**
     * @return void
     */
    public function testObjectPreSave()
    {
        Table14Query::create()->deleteAll();
        $t = new Table14();
        $t->save();
        $this->assertEquals('/foo/n-a/bar', $t->getSlug(), 'preSave() sets a default slug for empty objects');
        $t = new Table14();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('/foo/hello-world/bar', $t->getSlug(), 'preSave() sets a cleaned up slug for objects');
        $t = new Table14();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('/foo/hello-world/bar/1', $t->getSlug(), 'preSave() sets a unique slug for objects');
        $t = new Table14();
        $t->setTitle('Hello, World');
        $t->setSlug('/foo/custom/bar');
        $t->save();
        $this->assertEquals('/foo/custom/bar', $t->getSlug(), 'preSave() uses the given slug if it exists');
        $t = new Table14();
        $t->setTitle('Hello, World');
        $t->setSlug('/foo/custom/bar');
        $t->save();
        $this->assertEquals('/foo/custom/bar/1', $t->getSlug(), 'preSave() uses the given slug if it exists and makes it unique');
    }

    /**
     * @return void
     */
    public function testObjectSlugLifecycle()
    {
        Table13Query::create()->deleteAll();
        $t = new Table13();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('hello-world', $t->getSlug(), 'preSave() creates a slug for new objects');
        $t->setSlug('hello-bar');
        $t->save();
        $this->assertEquals('hello-bar', $t->getSlug(), 'setSlug() allows to override default slug');
        $t->setSlug('');
        $t->save();
        $this->assertEquals('hello-world', $t->getSlug(), 'setSlug(null) relaunches the slug generation');

        Table14Query::create()->deleteAll();
        $t = new Table14();
        $t->setTitle('Hello, World2');
        $t->setSlug('hello-bar2');
        $t->save();
        $this->assertEquals('hello-bar2', $t->getSlug(), 'setSlug() allows to override default slug, even before save');
        $t->setSlug('');
        $t->save();
        $this->assertEquals('/foo/hello-world2/bar', $t->getSlug(), 'setSlug(null) relaunches the slug generation');
    }

    /**
     * @return void
     */
    public function testObjectSlugAutoUpdate()
    {
        Table13Query::create()->deleteAll();
        $t = new Table13();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('hello-world', $t->getSlug(), 'preSave() creates a slug for new objects');
        $t->setTitle('Hello, My World');
        $t->save();
        $this->assertEquals('hello-my-world', $t->getSlug(), 'preSave() autoupdates slug on object change');
        $t->setTitle('Hello, My Whole New World');
        $t->setSlug('hello-bar');
        $t->save();
        $this->assertEquals('hello-bar', $t->getSlug(), 'preSave() does not autoupdate slug when it was set by the user');
    }

    /**
     * @return void
     */
    public function testObjectSlugAutoUpdatePermanent()
    {
        Table14Query::create()->deleteAll();
        $t = new Table14();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('/foo/hello-world/bar', $t->getSlug(), 'preSave() creates a slug for new objects');
        $t->setTitle('Hello, My World');
        $t->save();
        $this->assertEquals('/foo/hello-world/bar', $t->getSlug(), 'preSave() does not autoupdate slug on object change for permanent slugs');
        $t->setSlug('hello-bar');
        $t->save();
        $this->assertEquals('hello-bar', $t->getSlug(), 'setSlug() still works for permanent slugs');
    }

    /**
     * @return void
     */
    public function testQueryFindOneBySlug()
    {
        $this->assertFalse(method_exists('\Propel\Tests\Bookstore\Behavior\Table13Query', 'findOneBySlug'), 'The generated query does not provide a findOneBySlug() method if the slug column is "slug".');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Behavior\Table14Query', 'findOneBySlug'), 'The generated query provides a findOneBySlug() method even if the slug column doesn\'t have the default name');

        Table14Query::create()->deleteAll();
        $t1 = new Table14();
        $t1->setTitle('Hello, World');
        $t1->save();
        $t2 = new Table14();
        $t2->setTitle('Hello, Cruel World');
        $t2->save();
        $t = Table14Query::create()->findOneBySlug('/foo/hello-world/bar');
        $this->assertEquals($t1, $t, 'findOneBySlug() returns a single object matching the slug');
    }

    /**
     * @return void
     */
    public function testUniqueViolationWithoutScope()
    {
        $this->markTestSkipped('Skipping...');

        TableWithScopeQuery::create()->deleteAll();
        $t = new TableWithScope();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('hello-world', $t->getSlug());

        $this->expectException(PropelException::class);

        $t = new TableWithScope();
        $t->setTitle('Hello, World');
        $t->save();
    }

    /**
     * @return void
     */
    public function testNoUniqueViolationWithScope()
    {
        TableWithScopeQuery::create()->deleteAll();
        $t = new TableWithScope();
        $t->setTitle('Hello, World');
        $t->save();
        $this->assertEquals('hello-world', $t->getSlug());

        try {
            $t = new TableWithScope();
            $t->setTitle('Hello, World');
            $t->setScope(1);
            $t->save();

            $this->assertEquals('hello-world', $t->getSlug());
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testNumberOfQueriesForMakeUniqSlug()
    {
        Table13Query::create()->deleteAll();
        $con = Propel::getServiceContainer()->getConnection(Table13TableMap::DATABASE_NAME);
        $adapter = Propel::getAdapter(Table13TableMap::DATABASE_NAME);

        $expectedCount = 4;
        if ($adapter instanceof PgsqlAdapter) {
            $expectedCount++; //because of the SELECT nextval(...) query
        }

        for ($i = 0; $i < 5; $i++) {
            $nbQuery = $con->getQueryCount();

            $t = new Table13();
            $t->setTitle('Hello, World');
            $t->save($con);

            $this->assertLessThanOrEqual($expectedCount, $con->getQueryCount() - $nbQuery, "no more than $expectedCount query to get a slug when it already exist");
        }
    }

    /**
     * @return void
     */
    public function testSlugRegexp()
    {
        Table13Query::create()->deleteAll();
        $con = Propel::getServiceContainer()->getConnection(Table13TableMap::DATABASE_NAME);

        for ($i = 0; $i < 3; $i++) {
            $t = new Table13();
            $t->setTitle('Hello, World');
            $t->save($con);
        }
        $this->assertEquals('hello-world-2', $t->getSlug());

        $t = new Table13();
        $t->setTitle('World');
        $t->save($con);

        $this->assertEquals('world', $t->getSlug());

        $t = new Table13();
        $t->setTitle('World');
        $t->save($con);

        $this->assertEquals('world-1', $t->getSlug());

        $t = new Table13();
        $t->setTitle('Hello, World');
        $t->save($con);

        $this->assertEquals('hello-world-3', $t->getSlug());

        $t = new Table13();
        $t->setTitle('World');
        $t->save($con);

        $this->assertEquals('world-2', $t->getSlug());

        $t = new Table13();
        $t->setTitle('World 000');
        $t->save($con);

        $this->assertEquals('world-000', $t->getSlug());

        $t = new Table13();
        $t->setTitle('World');
        $t->save($con);

        $this->assertEquals('world-101', $t->getSlug());

        $t = new Table13();
        $t->setTitle('World');
        $t->save($con);

        $this->assertEquals('world-102', $t->getSlug());
    }
}
