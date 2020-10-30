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

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class GeneratedPKLessTableMapTest extends TestCase
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
    public function testGetPrimaryKeyHashFromRowReturnsNull()
    {
        $this->assertNull(StuffTableMap::getPrimaryKeyHashFromRow($row = []));
    }
}
