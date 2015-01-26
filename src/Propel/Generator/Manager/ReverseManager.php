<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Database;
use Propel\Generator\Schema\Dumper\DumperInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class ReverseManager extends AbstractManager
{
    /**
     * @var string|null
     */
    private $schemaName;
    /**
     * DOM document produced.
     *
     * @var \DOMDocument
     */
    protected $xml;

    /**
     * The document root element.
     *
     * @var \DOMElement
     */
    protected $databaseNode;

    /**
     * Hashtable of columns that have primary keys.
     *
     * @var array
     */
    protected $primaryKeys;

    /**
     * Whether to use same name for phpName or not.
     *
     * @var boolean
     */
    protected $samePhpName;

    /**
     * Whether to add vendor info or not.
     *
     * @var boolean
     */
    protected $addVendorInfo;

    /**
     * The schema dumper.
     *
     * @var DumperInterface
     */
    private $schemaDumper;

    /**
     * Constructor.
     *
     * @param DumperInterface $schemaDumper
     */
    public function __construct(DumperInterface $schemaDumper)
    {
        $this->schemaDumper = $schemaDumper;
    }

    /**
     * Gets the (optional) schema name to use.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    /**
     * Sets the name of a database schema to use (optional).
     *
     * @param string $schemaName
     */
    public function setSchemaName($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    /**
     * Gets the datasource name.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * Sets the datasource name.
     * This will be used as the <database name=""> value in the generated
     * schema.xml
     *
     * @param string $databaseName
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * Sets whether to use the column name as phpName without any translation.
     *
     * @param boolean $samePhpName
     */
    public function setSamePhpName($samePhpName)
    {
        $this->samePhpName = (boolean) $samePhpName;
    }

    /**
     * Sets whether to add vendor info to the schema.
     *
     * @param boolean $addVendorInfo
     */
    public function setAddVendorInfo($addVendorInfo)
    {
        $this->addVendorInfo = (Boolean) $addVendorInfo;
    }

    /**
     * Returns whether to use the column name as phpName without any
     * translation.
     *
     * @return boolean
     */
    public function isSamePhpName()
    {
        return $this->samePhpName;
    }

    public function reverse()
    {
        if (!$this->getDatabaseName()) {
            throw new BuildException('The databaseName attribute is required for schema reverse engineering');
        }

        try {
            $database = $this->buildModel();
            $schema   = $this->schemaDumper->dump($database);

            $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $this->getSchemaName() . '.xml';
            $this->log('Writing XML file to ' . $file);

            file_put_contents($file, $schema);
        } catch (\Exception $e) {
            $this->log(sprintf('<error>There was an error building XML from metadata: %s</error>', $e->getMessage()));
            throw $e;
        }

        return true;
    }

    /**
     * Builds the model classes from the database schema.
     *
     * @return Database The built-out Database (with all tables, etc.)
     */
    protected function buildModel()
    {
        $config     = $this->getGeneratorConfig();
        $connection = $this->getConnection();
        $databaseName = $config->getConfigProperty('reverse.connection');


        $database = new Database($this->getDatabaseName());
        $database->setPlatform($config->getConfiguredPlatform($connection), $databaseName);
        $database->setDefaultIdMethod(IdMethod::NATIVE);

        $buildConnection = $config->getBuildConnection($databaseName);
        $this->log(sprintf('Reading database structure of database `%s` using dsn `%s`', $this->getDatabaseName(), $buildConnection['dsn']));

        $parser   = $config->getConfiguredSchemaParser($connection, $databaseName);
        $this->log(sprintf('SchemaParser `%s` chosen', get_class($parser)));
        $nbTables = $parser->parse($database);

        $this->log(sprintf('Successfully reverse engineered %d tables', $nbTables));

        return $database;
    }

    /**
     * @return ConnectionInterface
     *
     * @throws BuildException if there isn't a configured connection for reverse
     */
    protected function getConnection()
    {
        $generatorConfig = $this->getGeneratorConfig();
        $database = $generatorConfig->getConfigProperty('reverse.connection');

        if (null === $database) {
            throw new BuildException('No configured connection. Please add a connection to your configuration file
            or pass a `connection` option to your command line.');
        }

        return $generatorConfig->getConnection($database);
    }
}
