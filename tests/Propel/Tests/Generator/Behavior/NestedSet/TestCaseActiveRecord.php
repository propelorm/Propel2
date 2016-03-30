<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\NestedSet;

use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Configuration;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class TestCaseActiveRecord extends TestCase
{
    public function setUp()
    {
        if (!class_exists('\NestedSetEntity9')) {
            $schema = <<<XML
<database name="bookstore-behavior" defaultIdMethod="native" activeRecord="true">
    <entity name="NestedSetEntity9">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />

        <behavior name="nested_set" />
    </entity>

    <entity name="NestedSetEntity10">
        <field name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <field name="title" type="VARCHAR" size="100" primaryString="true" />
        <field name="my_left_field" type="INTEGER" required="false"/>
        <field name="my_right_field" type="INTEGER" required="false"/>
        <field name="my_level_field" type="INTEGER" required="false"/>
        <field name="my_scope_field" type="INTEGER" required="false"/>

        <behavior name="nested_set">
            <parameter name="left_field" value="my_left_field" />
            <parameter name="right_field" value="my_right_field" />
            <parameter name="level_field" value="my_level_field" />
            <parameter name="use_scope" value="true" />
            <parameter name="scope_field" value="my_scope_field" />
            <parameter name="method_proxies" value="true" />
        </behavior>
    </entity>
</database>
XML;
            $this->con = QuickBuilder::buildSchema($schema);
        } else {
            $this->con = Configuration::getCurrentConfiguration();
        }
    }
}
