<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Model\Database;
use Propel\Tests\TestCase;

/**
 * Base class for all Platform tests
 */
abstract class PlatformTestBase extends TestCase
{
    /**
     * @var Database
     */
    private $database;

    protected function getDatabaseFromSchema($schema)
    {
        $mockGenConf = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')->getMock();
        $mockGenConf->method('createPlatform')->willReturn($this->getPlatform());
        $xtad = new SchemaReader();
        $xtad->setGeneratorConfig($mockGenConf);
        $appData = $xtad->parseString($schema);
        $this->database = $appData->getDatabase();

        return $this->database;
    }

    protected function getEntityFromSchema($schema, $entityName = 'Foo')
    {
        return $this->getDatabaseFromSchema($schema)->getEntity($entityName);
    }

    protected function getDatabase()
    {
        return $this->database;
    }
}
