<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\ConfigStore;

use Propel\Generator\Behavior\AutoAddPk\AutoAddPkBehavior;
use Propel\Generator\Behavior\ConfigStore\ConfigurationItem;
use Propel\Generator\Behavior\ConfigStore\ConfigurationStore;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Model\Database;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 */
class ConfigOperationBehaviorTest extends TestCase
{
    public function tearDown(): void
    {
        $this->setStoredConfigurations();
    }

    /**
     * @return void
     */
    public function testStoreConfiguration(): void
    {
        $schemaXml = <<<EOT
        <database>
            <behavior name="config_store" behavior="foo" id="foo_conf" additional="any">
                <parameter name="param1" value="value1"/>
            </behavior>
        </database>
EOT;
        $this->buildSchemaXml($schemaXml);

        $expected = [
            'foo_conf' => new ConfigurationItem(
                ['name' => 'foo', 'additional' => 'any'],
                ['param1' => 'value1']
            )
        ];
        $preconfigurations = $this->getStoredConfigurations();

        $this->assertEquals($expected, $preconfigurations);
    }

    /**
     * @return void
     */
    public function testLoadBehavior(): void
    {
        $schemaXml = <<<EOT
        <database>
            <behavior
                name="config_store"
                behavior="auto_add_pk"
                id="le_auto_add"
                store_attribute="storeAttribute value"
                override_attribute="inital attribute value"
            >
                <parameter name="param1" value="value1"/>
                <parameter name="override parameter" value="inital parameter value"/>
            </behavior>
            <table name="table">
                <behavior
                    name="config_load"
                    ref="le_auto_add"
                    id="id1"
                    load_attribute="loadAttribute value"
                    override_attribute="overridden attribute value"
                >
                    <parameter name="param2" value="value2"/>
                    <parameter name="override parameter" value="overridden parameter value"/>
                </behavior>
            </table>
        </database>
EOT;
        $table = $this->buildSchemaXml($schemaXml)->getTable('table');
        $behavior = $table->getBehavior('auto_add_pk');
        $this->assertNotNull($behavior, 'Should have created behavior');

        $expectedAttributes = [
            'name' => 'auto_add_pk',
            'store_attribute' => 'storeAttribute value',
            'load_attribute' => 'loadAttribute value',
            'override_attribute' => 'overridden attribute value'
        ];
        $this->assertEqualsCanonicalizing($expectedAttributes, $behavior->getAttributes(), 'Behavior should inherit attributes');

        $expectedParameters = array_merge(
            (new AutoAddPkBehavior())->getParameters(), // default parameters
            [
                'param1' => 'value1',
                'param2' => 'value2',
                'override parameter' => 'overridden parameter value',
            ]
        );
        $this->assertEqualsCanonicalizing($expectedParameters, $behavior->getParameters(), 'Behavior should inherit parameters');

        $this->assertNotNull($table->getColumn('id'), 'Should have applied behavior');
    }

    /**
     * @return void
     */
    public function testStoreCannotOmitId(): void
    {
        $schemaXml = <<<EOT
        <database>
            <behavior name="config_store" behavior="foo"/>
        </database>
EOT;
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('config_store behavior: required parameter \'id\' is missing.');
        $this->buildSchemaXml($schemaXml);
    }

    /**
     * @return void
     */
    public function testCannotStoreSameRefTwice(): void
    {
        $schemaXml = <<<EOT
        <database>
            <behavior name="config_store" behavior="foo" id="foo1"/>
            <table name="table">
                <behavior name="config_store" behavior="foo" id="foo1"/>
            </table>
        </database>
EOT;
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('config_store behavior for \'foo\': key \'foo1\' is already in use.');
        $this->buildSchemaXml($schemaXml);
    }

    /**
     * @return void
     */
    public function testLoadMultipleBehaviors(): void
    {
        $aggregateParams = '
        <parameter name="name" value="agg_col" />
        <parameter name="foreign_table" value="table" />
        ';
        $schemaXml = <<<EOT
        <database>
            <behavior name="config_store" behavior="aggregate_column" id="aggregate1">$aggregateParams</behavior>
            <behavior name="config_store" behavior="aggregate_column" id="aggregate2">$aggregateParams</behavior>
            <table name="table">
                <behavior name="config_load" ref="aggregate1" id="b1" multiple="1" />
                <behavior name="config_load" ref="aggregate1" id="b2" multiple="1" />
                <behavior name="config_load" ref="aggregate2" id="b3" multiple="1" />
            </table>
        </database>
EOT;
        $database = $this->buildSchemaXml($schemaXml);
        $behaviors = $database->getTable('table')->getBehaviors();
        $loadedBehaviors = array_filter($behaviors, fn($key) => preg_match('/^aggregate[12]_[0-9a-f]{13}$/', $key), ARRAY_FILTER_USE_KEY);
        $this->assertCount(3, $loadedBehaviors);
    }

    /**
     * @return array
     */
    protected function getStoredConfigurations(): array
    {
        $class = new \ReflectionClass(ConfigurationStore::class);

        return $class->getStaticPropertyValue('preconfigurations');
    }

    /**
     * @param array
     * 
     * @return void
     */
    protected function setStoredConfigurations(array $configurations = []): void
    {
        $class = new \ReflectionClass(ConfigurationStore::class);

        $class->setStaticPropertyValue('preconfigurations', $configurations);
    }

    /**
     * @param string $schemaXml
     * 
     * @return Database
     */
    public function buildSchemaXml(string $schemaXml): Database
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schemaXml);

        return $builder->getDatabase();
    }
}
