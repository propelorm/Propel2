<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Base class for all Platform tests
 */
abstract class PlatformTestBase extends TestCaseFixturesDatabase
{
    protected function getDatabaseFromSchema($schema)
    {
        $platform = $this->getPlatform();
        require_once __DIR__ . '/../../../../Fixtures/platform/build/conf/platform-conf.php';
        $platform->setConnection(Propel::getConnection('platform'));
        $xtad = new SchemaReader($platform);
        $appData = $xtad->parseString($schema);

        return $appData->getDatabase();
    }

    protected function getTableFromSchema($schema, $tableName = 'foo')
    {
        return $this->getDatabaseFromSchema($schema)->getTable($tableName);
    }
}
