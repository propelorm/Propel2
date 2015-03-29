<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

/**
 * provider for mysql platform migration unit tests
 */
class MysqlPlatformMigrationTestProvider extends PlatformMigrationTestProvider {


    public function providerForTestGetAddColumnFirstDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="bar" type="INTEGER" />
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
    </table>
</database>
EOF;
        $column = $this->getDatabaseFromSchema($schema)->getTable('foo')->getColumn('bar');

        return array(array($column));
    }
}