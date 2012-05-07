<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Database;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Interface for reverse engineering schema parsers.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
interface SchemaParserInterface
{
    /**
     * Gets the database connection.
     * @return     ConnectionInterface
     */
    function getConnection();

    /**
     * Sets the database connection.
     *
     * @param      ConnectionInterface $dbh
     */
    function setConnection(ConnectionInterface $dbh);

    /**
     * Sets the GeneratorConfig to use in the parsing.
     *
     * @param      GeneratorConfigInterface $config
     */
    function setGeneratorConfig(GeneratorConfigInterface $config);

    /**
     * Gets a specific propel (renamed) property from the build.
     *
     * @param      string $name
     * @return     mixed
     */
    function getBuildProperty($name);

    /**
     * Gets array of warning messages.
     * @return     array string[]
     */
    function getWarnings();

    /**
     * Parse the schema and populate passed-in Database model object.
     *
     * @param      Database $database
     * @return     int number of generated tables
     */
    function parse(Database $database);
}