<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Platform\DefaultPlatform;

/**
 * Utility class for QueryBuilder.
 */
class TestableQueryBuilder extends QueryBuilder
{
    /**
     * Build a TestableQueryBuilder for a table given in schema xml format.
     *
     * @param string $schemaXml
     * @param string $tableName
     *
     * @return self
     */
    public static function forTableFromXml(string $schemaXml, string $tableName): self
    {
        $reader = new SchemaReader();
        $schema = $reader->parseString($schemaXml);
        $table = $schema->getDatabase()->getTable($tableName);
        $builder = new static($table);
        $builder->setGeneratorConfig(new QuickGeneratorConfig());
        $builder->setPlatform(new DefaultPlatform());

        return $builder;
    }

    /**
     * Call a (usually protected) script builder function by name and return the result.
     *
     * @param string $scriptBuilderFunctionName
     *
     * @return string
     */
    public function buildScript(string $scriptBuilderFunctionName): string
    {
        $script = '';
        $this->$scriptBuilderFunctionName($script);

        return $script;
    }
}
