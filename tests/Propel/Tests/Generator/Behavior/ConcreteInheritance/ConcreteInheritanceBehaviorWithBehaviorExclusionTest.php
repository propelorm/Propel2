<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior;

use ConcreteInheritanceBehaviorWithBehaviorExclusionTest\Map\ConcreteInheritanceChildTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests for ConcreteInheritanceBehavior class
 *
 * @author Pierre Minnieur
 */
class ConcreteInheritanceBehaviorWithBehaviorExclusionTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists('ConcreteInheritanceBehaviorWithBehaviorExclusionTest\\ConcreteInheritanceParentQuery')) {
            $schema = <<<EOF
<database name="concrete_inheritance_behavior_exclusion" namespace="ConcreteInheritanceBehaviorWithBehaviorExclusionTest">
    <table name="concrete_inheritance_parent" allowPkInsert="true">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <behavior name="sluggable">
            <parameter name="scope_column" value="title"/>
        </behavior>
        <behavior name="timestampable"/>
        <index>
            <index-column name="title"/>
        </index>
    </table>
    <table name="concrete_inheritance_child" allowPkInsert="true">
        <column name="body" type="longvarchar"/>
        <column name="author_id" required="false" type="INTEGER"/>
        <behavior name="concrete_inheritance">
            <parameter name="extends" value="concrete_inheritance_parent"/>
            <parameter name="copy_data_to_child" value="slug"/>
            <parameter name="exclude_behaviors" value="sluggable"/>
        </behavior>
    </table>
</database>
EOF;

            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testParentBehaviorExclusion()
    {
        $behaviors = ConcreteInheritanceChildTableMap::getTableMap()->getBehaviors();
        $this->assertFalse(array_key_exists('sluggable', $behaviors), '');
        $this->assertTrue(array_key_exists('timestampable', $behaviors), '');
    }
}
