<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Propel;

use MyNameSpace\QuickBuildFoo1;
use MyNameSpace\QuickBuildFoo1Query;
use MyNameSpace\Map\QuickBuildFoo1TableMap;

use MyNameSpace2\QuickBuildFoo2;
use MyNameSpace2\QuickBuildFoo2Query;
use \Propel\Tests\TestCase;

/**
 *
 */
class QuickBuilderTest extends TestCase
{
    public function testGetPlatform()
    {
        $builder = new QuickBuilder();
        $builder->setPlatform(new MysqlPlatform());
        $this->assertTrue($builder->getPlatform() instanceof MysqlPlatform);
        $builder = new QuickBuilder();
        $this->assertTrue($builder->getPlatform() instanceof SqlitePlatform);
    }

    public function simpleSchemaProvider()
    {
        $xmlSchema = <<<EOF
<database name="test_quick_build_2" namespace="MyNameSpace">
    <table name="quick_build_foo_1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);

        return array(array($builder));
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testGetDatabase($builder)
    {
        $database = $builder->getDatabase();
        $this->assertEquals('test_quick_build_2', $database->getName());
        $this->assertEquals(1, count($database->getTables()));
        $this->assertEquals(2, count($database->getTable('quick_build_foo_1')->getColumns()));
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testGetSQL($builder)
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
     */
    public function testGetClasses($builder)
    {
        $script = $builder->getClasses();
        $this->assertContains('class QuickBuildFoo1 extends BaseQuickBuildFoo1', $script);
        $this->assertContains('class QuickBuildFoo1Query extends BaseQuickBuildFoo1Query', $script);
        $this->assertContains('class QuickBuildFoo1 implements ActiveRecordInterface', $script);
        $this->assertContains('class QuickBuildFoo1Query extends ModelCriteria', $script);
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testGetClassesLimitedClassTargets($builder)
    {
        $script = $builder->getClasses(array('tablemap', 'object', 'query'));
        $this->assertNotContains('class QuickBuildFoo1 extends BaseQuickBuildFoo1', $script);
        $this->assertNotContains('class QuickBuildFoo1Query extends BaseQuickBuildFoo1Query', $script);
        $this->assertContains('class QuickBuildFoo1 implements ActiveRecordInterface', $script);
        $this->assertContains('class QuickBuildFoo1Query extends ModelCriteria', $script);
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testBuildClasses($builder)
    {
        $builder->buildClasses();
        $foo = new QuickBuildFoo1();
        $this->assertTrue($foo instanceof ActiveRecordInterface);
        $this->assertTrue(QuickBuildFoo1TableMap::getTableMap() instanceof \MyNameSpace\Map\QuickBuildFoo1TableMap);
    }

    public function testBuild()
    {
        $xmlSchema = <<<EOF
<database name="test_quick_build_2" namespace="MyNameSpace2">
    <table name="quick_build_foo_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
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

}
