<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Connection\ConnectionInterface;

interface GeneratorConfigInterface
{
    /**
     * Returns a configured data model builder class for specified table and
     * based on type ('ddl', 'sql', etc.).
     *
     * @param  Table            $table
     * @param  string           $type
     * @return DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, $type);

    /**
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer();

    /**
     * Returns a specific propel (renamed) property from the build.
     *
     * @param  string $name
     * @return mixed
     */
    public function getBuildProperty($name);

    /**
     * Sets a specific propel (renamed) property from the build.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setBuildProperty($name, $value);

    /**
     * Creates and configures a new Platform class.
     *
     * @param  ConnectionInterface $con
     * @param  string              $database
     * @return PlatformInterface
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, $database = null);
}
