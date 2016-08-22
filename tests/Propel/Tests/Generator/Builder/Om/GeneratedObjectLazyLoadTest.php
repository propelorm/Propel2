<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Util\QuickBuilder;

use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests the generated Object classes for lazy load fields.
 *
 */
class GeneratedObjectLazyLoadTest extends TestCase
{
    /** @var  ConnectionWrapper */
    private $con;

    /** @var  Configuration */
    private $config;

    public function setUp()
    {
        if (!class_exists('\LazyLoadEntity')) {
            $schema = <<<EOF
<database name="lazy_load_entity_1">
    <entity name="LazyLoadEntity">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="VARCHAR" size="100" />
        <field name="bar" type="VARCHAR" size="100" lazyLoad="true" />
        <field name="baz" type="VARCHAR" size="100" defaultValue="world" lazyLoad="true" />
    </entity>
</database>
EOF;
            //QuickBuilder::debugClassesForEntity($schema, 'lazy_load_active_record');
            $this->config = QuickBuilder::buildSchema($schema);
        }

        if (null === $this->config) {
            $this->config = Configuration::getCurrentConfiguration();
        }

        $this->con = $this->config->getConnectionManager("lazy_load_entity_1")->getWriteConnection();
    }

    public function testNormalFieldsRequireNoQueryOnGetter()
    {
        $this->con->useDebug(true);
        $obj = new \LazyLoadEntity();
        $obj->setFoo('hello');
        $this->config->getRepository('\LazyLoadEntity')->save($obj, $this->con);
        $this->config->getSession()->clearFirstLevelCache();
        $obj2 = \LazyLoadEntityQuery::create()->findPk($obj->getId(), $this->con);
        $count = $this->con->getQueryCount();
        $this->assertEquals('hello', $obj2->getFoo());
        $this->assertEquals($count, $this->con->getQueryCount());
    }

    public function testLazyLoadedFieldsRequireAnAdditionalQueryOnGetter()
    {
        $this->con->useDebug(true);
        $obj = new \LazyLoadEntity();
        $obj->setBar('hello');
        $this->config->getRepository('\LazyLoadEntity')->save($obj, $this->con);
        $this->config->getSession()->clearFirstLevelCache();
        $obj2 = \LazyLoadEntityQuery::create()->findPk($obj->getId(), $this->con);
        $count = $this->con->getQueryCount();
        $this->assertEquals('hello', $obj2->getBar($this->con));
        $this->assertEquals($count + 1, $this->con->getQueryCount());
    }

    public function testLazyLoadedFieldsWithDefaultRequireAnAdditionalQueryOnGetter()
    {
        $this->con->useDebug(true);
        $obj = new \LazyLoadEntity();
        $obj->setBaz('hello');
        $this->config->getRepository('\LazyLoadEntity')->save($obj, $this->con);
        $this->config->getSession()->clearFirstLevelCache();
        $obj2 = \LazyLoadEntityQuery::create()->findPk($obj->getId(), $this->con);
        $count = $this->con->getQueryCount();
        $this->assertEquals('hello', $obj2->getBaz($this->con));
        $this->assertEquals($count + 1, $this->con->getQueryCount());
    }
}
