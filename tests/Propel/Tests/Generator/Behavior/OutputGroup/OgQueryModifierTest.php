<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\OutputGroup;

use Propel\Generator\Behavior\OutputGroup\OgQueryModifier;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Tests\TestCase;


/**
 */
class OgQueryModifierTest extends TestCase
{
    /**
     * @param string $databaseXml
     * @param string $tableName
     *
     * @return \Propel\Generator\Behavior\OutputGroup\OgQueryModifier
     */
    public function buildTableModifierForSchema(string $databaseXml, string $tableName): OgQueryModifier
    {
        $reader = new SchemaReader(new DefaultPlatform());
        $schema = $reader->parseString($databaseXml);
        $database = $schema->getDatabase();
        $table = $database->getTable($tableName);
        $behavior = $database->getBehavior('output_group');
        $behavior->setTable($table);

        return new OgQueryModifier($behavior);
    }

    /**
     * @return void
     */
    public function testAddMethodSignaturesToDocBlock()
    {
        $databaseXml = '
        <database>
            <behavior name="output_group">
                <parameter name="object_collection_class" value="\Custom\Collector" />
            </behavior>

            <table name="table">
                <column name="pk" primaryKey="true" />
                <column name="unique" />
                <column name="col2" />
                <column name="col3" />
                <unique>
                    <unique-column name="unique"/>
                </unique>
            </table>
        </database>
        ';

        $modifier = $this->buildTableModifierForSchema($databaseXml, 'table');

        $script = <<<EOT
/**
 * asdf
 */
abstract class FooQuery
{     
EOT;
        $actual = $this->callMethod($modifier, 'addMethodSignaturesToDocBlock', [$script]);
        $expected = <<<EOT
/**
 * asdf
 *
 * @method \Custom\Collector find(?ConnectionInterface \$con = null)
 * @method \Custom\Collector findPks(array \$keys, ?ConnectionInterface \$con = null)
 * @method \Custom\Collector findBy(string \$column, \$value, ?ConnectionInterface \$con = null)
 * @method \Custom\Collector findByArray(\$conditions, ?ConnectionInterface \$con = null)
 * 
 * @method \Custom\Collector findByCol2(string|array<string> \$col2)
 * @method \Custom\Collector findByCol3(string|array<string> \$col3)
 *
 */
abstract class FooQuery
{     
EOT;

        $this->assertEquals($expected, $actual);
    }
}
