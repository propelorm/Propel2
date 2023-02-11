<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Versionable;

use Propel\Generator\Util\QuickBuilder;
use VersionableBehaviorTest10Query;

/**
 * Tests for VersionableBehavior class
 *
 * @author François Zaninotto
 */
class VersionableBehaviorQueryBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('VersionableBehaviorTest10')) {
            $schema = <<<EOF
<database name="versionable_behavior_test_10">
    <table name="versionable_behavior_test_10">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
</database>>
EOF;
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testIsVersioningEnabled()
    {
        $this->assertTrue(VersionableBehaviorTest10Query::isVersioningEnabled());
        VersionableBehaviorTest10Query::disableVersioning();
        $this->assertFalse(VersionableBehaviorTest10Query::isVersioningEnabled());
        VersionableBehaviorTest10Query::enableVersioning();
        $this->assertTrue(VersionableBehaviorTest10Query::isVersioningEnabled());
    }
}
