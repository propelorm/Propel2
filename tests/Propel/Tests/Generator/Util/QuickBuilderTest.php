<?php declare(strict_types=1);

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Util;

use MyNameSpace\Map\QuickBuildFoo1TableMap;
use MyNameSpace\QuickBuildFoo1;
use MyNameSpace2\QuickBuildFoo2;
use MyNameSpace2\QuickBuildFoo2Query;
use MyNameSpace3\QuickBuildFoo3;
use MyNameSpace3\QuickBuildFoo3Query;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

class QuickBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetPlatform(): void
    {
        $builder = new QuickBuilder();
        $builder->setPlatform(new MysqlPlatform());
        $this->assertTrue($builder->getPlatform() instanceof MysqlPlatform);
        $builder = new QuickBuilder();
        $this->assertTrue($builder->getPlatform() instanceof SqlitePlatform);
    }

    public function simpleSchemaProvider(): array
    {
        $xmlSchema = <<<EOF
<database name="test_quick_build_2" namespace="MyNameSpace">
    <table name="quick_build_foo_1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);

        return [[$builder]];
    }

    /**
     * @dataProvider simpleSchemaProvider
     *
     * @return void
     */
    public function testGetDatabase($builder): void
    {
        $database = $builder->getDatabase();
        $this->assertEquals('test_quick_build_2', $database->getName());
        $this->assertEquals(1, count($database->getTables()));
        $this->assertEquals(2, count($database->getTable('quick_build_foo_1')->getColumns()));
    }

    /**
     * @dataProvider simpleSchemaProvider
     *
     * @return void
     */
    public function testGetSQL($builder): void
    {
        $expected = <<<EOF

-----------------------------------------------------------------------
-- quick_build_foo_1
-----------------------------------------------------------------------

DROP TABLE IF EXISTS quick_build_foo_1;

CREATE TABLE quick_build_foo_1
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    UNIQUE (id)
);

EOF;
        $this->assertEquals($expected, $builder->getSQL());
    }

    /**
     * @dataProvider simpleSchemaProvider
     *
     * @return void
     */
    public function testGetClasses($builder): void
    {
        $script = $builder->getClasses();
        $this->assertStringContainsString('class QuickBuildFoo1 extends BaseQuickBuildFoo1', $script);
        $this->assertStringContainsString('class QuickBuildFoo1Query extends BaseQuickBuildFoo1Query', $script);
        $this->assertStringContainsString('class QuickBuildFoo1 implements ActiveRecordInterface', $script);
        $this->assertStringContainsString('class QuickBuildFoo1Query extends ModelCriteria', $script);
    }

    /**
     * @dataProvider simpleSchemaProvider
     *
     * @return void
     */
    public function testGetClassesLimitedClassTargets($builder): void
    {
        $script = $builder->getClasses(['tablemap', 'object', 'query']);
        $this->assertStringNotContainsString('class QuickBuildFoo1 extends BaseQuickBuildFoo1', $script);
        $this->assertStringNotContainsString('class QuickBuildFoo1Query extends BaseQuickBuildFoo1Query', $script);
        $this->assertStringContainsString('class QuickBuildFoo1 implements ActiveRecordInterface', $script);
        $this->assertStringContainsString('class QuickBuildFoo1Query extends ModelCriteria', $script);
    }

    /**
     * @dataProvider simpleSchemaProvider
     *
     * @return void
     */
    public function testBuildClasses($builder): void
    {
        $builder->buildClasses();
        $foo = new QuickBuildFoo1();
        $this->assertTrue($foo instanceof ActiveRecordInterface);
        $this->assertTrue(QuickBuildFoo1TableMap::getTableMap() instanceof QuickBuildFoo1TableMap);
    }

    /**
     * @return void
     */
    public function testBuild(): void
    {
        $xmlSchema = <<<EOF
<database name="test_quick_build_2" namespace="MyNameSpace2">
    <table name="quick_build_foo_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);
        $builder->build();
        $this->assertEquals(0, QuickBuildFoo2Query::create()->count());
        $foo = new QuickBuildFoo2();
        $foo->setBar(3);
        $foo->save();
        $this->assertEquals(1, QuickBuildFoo2Query::create()->count());
        $this->assertEquals($foo, QuickBuildFoo2Query::create()->findOne());
    }

    public function testBuildOnPhysicalFilesystem(): void
    {
        $xmlSchema = <<<EOF
<database name="test_quick_build_3" namespace="MyNameSpace3">
    <table name="quick_build_foo_3">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);
        $builder->setVfs(false);
        $builder->build();
        $this->assertEquals(0, QuickBuildFoo3Query::create()->count());
        $foo = new QuickBuildFoo3();
        $foo->setBar(3);
        $foo->save();
        $this->assertEquals(1, QuickBuildFoo3Query::create()->count());
        $this->assertEquals($foo, QuickBuildFoo3Query::create()->findOne());

        $this->assertDirectoryExists(
            sys_get_temp_dir() . '/propelQuickBuild-' . Propel::VERSION . '-' . substr(sha1(getcwd()), 0, 10));
    }
}
