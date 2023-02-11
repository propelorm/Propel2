<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Database;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Interface for reverse engineering schema parsers.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
interface SchemaParserInterface
{
    /**
     * Gets the database connection.
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getConnection(): ConnectionInterface;

    /**
     * Sets the database connection.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $dbh
     *
     * @return void
     */
    public function setConnection(ConnectionInterface $dbh): void;

    /**
     * Sets the GeneratorConfig to use in the parsing.
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $config
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config): void;

    /**
     * Gets array of warning messages.
     *
     * @return array<string>
     */
    public function getWarnings(): array;

    /**
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform(): PlatformInterface;

    /**
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return void
     */
    public function setPlatform(PlatformInterface $platform): void;

    /**
     * Parse the schema and populate passed-in Database model object.
     *
     * @param \Propel\Generator\Model\Database $database
     * @param array<\Propel\Generator\Model\Table> $additionalTables additional tables to parse and add to $database
     *
     * @return int Number of generated tables
     */
    public function parse(Database $database, array $additionalTables = []): int;
}
