<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Map\StuffTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;
use Stuff;
use StuffQuery;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class GeneratedPKLessQueryBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
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
     * @expectedException \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage The Stuff object has no primary key
     *
     * @return void
     */
    public function testFindPkThrowsAnError()
    {
        StuffQuery::create()->findPk(42);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage The Stuff object has no primary key
     *
     * @return void
     */
    public function testBuildPkeyCriteria()
    {
        $stuff = new Stuff();
        $stuff->buildPkeyCriteria();
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage The Stuff object has no primary key
     *
     * @return void
     */
    public function testTableMapDoDelete()
    {
        StuffTableMap::doDelete([]);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage The Stuff object has no primary key
     *
     * @return void
     */
    public function testFindPksThrowsAnError()
    {
        StuffQuery::create()->findPks([42, 24]);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage The Stuff object has no primary key
     *
     * @return void
     */
    public function testFilterByPrimaryKeyThrowsAnError()
    {
        StuffQuery::create()->filterByPrimaryKey(42);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     * @expectedExceptionMessage The Stuff object has no primary key
     *
     * @return void
     */
    public function testFilterByPrimaryKeysThrowsAnError()
    {
        StuffQuery::create()->filterByPrimaryKeys(42);
    }
}
