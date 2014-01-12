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
use Propel\Tests\TestCase;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class GeneratedPKLessQueryBuilderTest extends TestCase
{
    public function setUp()
    {
        if (class_exists('Stuff')) {
            return;
        }

        $schema = <<<SCHEMA
<database name="primarykey_less_test">
    <table name="stuff">
        <column name="key" type="VARCHAR" />
        <column name="value" type="VARCHAR" />
    </table>
</database>
SCHEMA;

        QuickBuilder::buildSchema($schema);
    }

    /**
     * @expectedException           \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage    The Stuff object has no primary key
     */
    public function testFindPkThrowsAnError()
    {
        \StuffQuery::create()->findPk(42);
    }

    /**
     * @expectedException           \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage    The Stuff object has no primary key
     */
    public function testBuildPkeyCriteria()
    {
        $stuff = new \Stuff();
        $stuff->buildPkeyCriteria();
    }

    /**
     * @expectedException           \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage    The Stuff object has no primary key
     */
    public function testTableMapDoDelete()
    {
        \Map\StuffTableMap::doDelete([]);
    }

    /**
     * @expectedException           \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage    The Stuff object has no primary key
     */
    public function testFindPksThrowsAnError()
    {
        \StuffQuery::create()->findPks(array(42, 24));
    }

    /**
     * @expectedException           \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage    The Stuff object has no primary key
     */
    public function testFilterByPrimaryKeyThrowsAnError()
    {
        \StuffQuery::create()->filterByPrimaryKey(42);
    }

    /**
     * @expectedException           \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage    The Stuff object has no primary key
     */
    public function testFilterByPrimaryKeysThrowsAnError()
    {
        \StuffQuery::create()->filterByPrimaryKeys(42);
    }
}
