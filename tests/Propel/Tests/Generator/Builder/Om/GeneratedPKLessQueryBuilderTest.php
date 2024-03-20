<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Map\StuffTableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Exception\LogicException;
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
        <column name="key" type="VARCHAR"/>
        <column name="value" type="VARCHAR"/>
    </table>
</database>
SCHEMA;

        QuickBuilder::buildSchema($schema);
    }

    /**
     * @return void
     */
    public function testFindPkThrowsAnError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Stuff object has no primary key');

        StuffQuery::create()->findPk(42);
    }

    /**
     * @return void
     */
    public function testBuildPkeyCriteria()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Stuff object has no primary key');

        $stuff = new Stuff();
        $stuff->buildPkeyCriteria();
    }

    /**
     * @return void
     */
    public function testTableMapDoDelete()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Stuff object has no primary key');

        StuffTableMap::doDelete([]);
    }

    /**
     * @return void
     */
    public function testFindPksThrowsAnError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Stuff object has no primary key');

        StuffQuery::create()->findPks([42, 24]);
    }

    /**
     * @return void
     */
    public function testFilterByPrimaryKeyThrowsAnError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Stuff object has no primary key');

        StuffQuery::create()->filterByPrimaryKey(42);
    }

    /**
     * @return void
     */
    public function testFilterByPrimaryKeysThrowsAnError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Stuff object has no primary key');

        StuffQuery::create()->filterByPrimaryKeys(42);
    }
}
