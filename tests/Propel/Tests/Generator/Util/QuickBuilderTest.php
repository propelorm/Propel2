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
<database name="TestQuickBuild2" namespace="MyNameSpace">
    <entity name="QuickBuildFoo1" activeRecord="true">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($xmlSchema);

        return array(array($builder));
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testGetDatabase(QuickBuilder $builder)
    {
        $database = $builder->getDatabase();
        $this->assertEquals('TestQuickBuild2', $database->getName());
        $this->assertEquals(1, count($database->getEntities()));
        $this->assertEquals(2, count($database->getEntity('QuickBuildFoo1')->getFields()));
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testGetSQL(QuickBuilder $builder)
    {
        $expected = <<<EOF

-----------------------------------------------------------------------
-- quick_build_foo1
-----------------------------------------------------------------------

DROP TABLE IF EXISTS quick_build_foo1;

CREATE TABLE quick_build_foo1
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
    public function testGetClasses(QuickBuilder $builder)
    {
        $script = $builder->getClasses();
        $this->assertContains('class QuickBuildFoo1 {', $script);
        $this->assertContains('class QuickBuildFoo1Query extends BaseQuickBuildFoo1Query', $script);
    }

    /**
     * @dataProvider simpleSchemaProvider
     */
    public function testGetClassesLimitedClassTargets(QuickBuilder $builder)
    {
        $script = $builder->getClasses(array('entitymap', 'object'));
        $this->assertContains('class QuickBuildFoo1 {', $script);
        $this->assertNotContains('class QuickBuildFoo1Query extends BaseQuickBuildFoo1Query', $script);
    }

    public function testBuild()
    {
        $xmlSchema = <<<EOF
<database name="TestQuickBuild2" namespace="MyNameSpace2">
    <entity name="QuickBuildFoo2" activeRecord="true">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
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
