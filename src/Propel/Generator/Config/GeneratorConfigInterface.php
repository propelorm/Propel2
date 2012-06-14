<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Generator\Model\Table;

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
     * @param  \PDO              $con
     * @return PlatformInterface
     */
    public function getConfiguredPlatform(\PDO $con = null, $database = null);
}
