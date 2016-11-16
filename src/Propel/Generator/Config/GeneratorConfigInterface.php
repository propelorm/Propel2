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
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Common\Types\FieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Generator\Util\BehaviorLocator;

interface GeneratorConfigInterface
{
    /**
     * Returns a configured data model builder class for specified entity and
     * based on type ('ddl', 'sql', etc.).
     *
     * @param  Entity $entity
     * @param  string $type
     *
     * @return AbstractBuilder
     */
    public function getConfiguredBuilder(Entity $entity, $type);

    /**
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer();

    /**
     * Creates and configures a new Platform class.
     *
     * @param  string              $platform full or short class name
     * @param  ConnectionInterface $con
     *
     * @return PlatformInterface
     */
    public function createPlatform($platform, ConnectionInterface $con = null);

    /**
     * Returns the behavior locator.
     *
     * @return BehaviorLocator
     */
    public function getBehaviorLocator();

    /**
     * @param string $name
     *
     * @return FieldTypeInterface|BuildableFieldTypeInterface
     */
    public function getFieldType($name);

    /**
     * Return a specific configuration property.
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['entityType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.entityType</code>
     *
     * @param string $name The name of property, expressed as a dot separated level hierarchy
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     * @return mixed The configuration property
     */
    public function getConfigProperty($name);
}
