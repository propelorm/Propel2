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
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;

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
     * Connection infos
     */
    protected $connection;

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

    public function setConnection($connection)
    {
        $this->connection = $connection;
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

            return false;
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

        $this->log('Reading database structure...');

        $database = new Database($this->getDatabaseName());
        $database->setPlatform($config->getConfiguredPlatform($connection));
        $database->setDefaultIdMethod(IdMethod::NATIVE);

        $parser   = $config->getConfiguredSchemaParser($connection);
        $nbTables = $parser->parse($database);

        $this->log(sprintf('Successfully reverse engineered %d tables', $nbTables));

        return $database;
    }

    /**
     * @return ConnectionInterface
     */
    protected function getConnection()
    {
        $buildConnection = $this->connection;

        // Set user + password to null if they are empty strings or missing
        $username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
        $password = isset($buildConnection['password']) ? $buildConnection['password'] : null;

        $con = ConnectionFactory::create(array('dsn' => $buildConnection['dsn'], 'user' => $username, 'password' => $password), AdapterFactory::create($buildConnection['adapter']));

        return $con;
    }
}
