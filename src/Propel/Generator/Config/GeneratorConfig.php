<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * A class that holds build properties and provide a class loading mechanism for
 * the generator.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratorConfig implements GeneratorConfigInterface
{

    /**
     * The build properties.
     *
     * @var array
     */
    private $buildProperties = array();

    protected $buildConnections = null;

    protected $defaultBuildConnection = null;

    /**
     * Construct a new GeneratorConfig.
     *
     * @param array|Traversable $props
     */
    public function __construct($props = null)
    {
        if ($props) {
            $this->setBuildProperties($props);
        }
    }

    /**
     * Returns the build properties.
     *
     * @return array
     */
    public function getBuildProperties()
    {
        return $this->buildProperties;
    }

    /**
     * Parses the passed-in properties, renaming and saving eligible properties in this object.
     *
     * Renames the propel.xxx properties to just xxx and renames any xxx.yyy properties
     * to xxxYyy as PHP doesn't like the xxx.yyy syntax.
     *
     * @param array|Traversable $props
     */
    public function setBuildProperties($props)
    {
        $this->buildProperties = array();

        foreach ($props as $key => $propValue) {
            if (strpos($key, "propel.") === 0) {
                $newKey = substr($key, strlen("propel."));
                $j = strpos($newKey, '.');
                while ($j !== false) {
                    $newKey =  substr($newKey, 0, $j) . ucfirst(substr($newKey, $j + 1));
                    $j = strpos($newKey, '.');
                }
                $this->setBuildProperty($newKey, $propValue);
            }
        }
    }

    /**
     * Returns a specific Propel (renamed) property from the build.
     *
     * @param  string $name
     * @return mixed
     */
    public function getBuildProperty($name)
    {
        return isset($this->buildProperties[$name]) ? $this->buildProperties[$name] : null;
    }

    /**
     * Sets a specific propel (renamed) property from the build.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setBuildProperty($name, $value)
    {
        $this->buildProperties[$name] = $value;
    }

    /**
     * Resolves and returns the class name based on the specified property
     * value. The name of the property holds the class path as a dot-path
     * notation.
     *
     * @param  string         $propname
     * @return string
     * @throws BuildException
     */
    public function getClassName($propname)
    {
        $classpath = $this->getBuildProperty($propname);
        if (null === $classpath) {
            throw new BuildException("Unable to find class path for '$propname' property.");
        }

        // This is a slight hack to workaround camel case inconsistencies for the DataSQL classes.
        // Basically, we want to turn ?.?.?.sqliteDataSQLBuilder into ?.?.?.SqliteDataSQLBuilder
        $lastdotpos = strrpos($classpath, '.');
        if ($lastdotpos !== false) {
            $classpath{$lastdotpos+1} = strtoupper($classpath{$lastdotpos+1});
        } else {
            // Allows to configure full classname instead of a dot-path notation
            if (class_exists($classpath)) {
                return $classpath;
            }
            $classpath = ucfirst($classpath);
        }

        if (empty($classpath)) {
            throw new BuildException("Unable to find class path for '$propname' property.");
        }

        return $classpath;
    }

    /**
     * Resolves and returns the builder class name.
     *
     * @param  string $type
     * @return string
     */
    public function getBuilderClassName($type)
    {
        $propname = 'builder' . ucfirst(strtolower($type)) . 'Class';

        return $this->getClassName($propname);
    }

    /**
     * Creates and configures a new Platform class.
     *
     * @param  ConnectionInterface $con
     * @param  string              $database
     * @return PlatformInterface
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, $database = null)
    {
        $buildConnection = $this->getBuildConnection($database);

        if (null !== $buildConnection['adapter']) {
            $clazz = '\\Propel\\Generator\\Platform\\' . ucfirst($buildConnection['adapter']) . 'Platform';
        } elseif ($this->getBuildProperty('platformClass')) {
            // propel.platform.class = platform.${propel.database}Platform by default
            $platformClass = preg_split("#\.#", $this->getBuildProperty('platformClass'));
            $platformClass = ucfirst($platformClass[count($platformClass) - 1]);
            $clazz = '\\Propel\\Generator\\Platform\\' . $platformClass;
        } else {
            return null;
        }

        $platform = new $clazz();

        if (!$platform instanceof PlatformInterface) {
            throw new BuildException("Specified platform class ($clazz) does not implement the PlatformInterface interface.");
        }

        $platform->setConnection($con);
        $platform->setGeneratorConfig($this);

        return $platform;
    }

    /**
     * Creates and configures a new SchemaParser class for specified platform.
     * @param  ConnectionInterface   $con
     * @return SchemaParserInterface
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null)
    {
        $clazz  = $this->getClassName("reverseParserClass");
        $parser = new $clazz();

        if (!$parser instanceof SchemaParserInterface) {
            throw new BuildException("Specified platform class ($clazz) does implement SchemaParserInterface interface.", $this->getLocation());
        }

        $parser->setConnection($con);
        $parser->setMigrationTable($this->getBuildProperty('migrationTable'));
        $parser->setGeneratorConfig($this);

        return $parser;
    }

    /**
     * Returns a configured data model builder class for specified table and
     * based on type ('ddl', 'sql', etc.).
     *
     * @param  Table            $table
     * @param  string           $type
     * @return DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, $type)
    {
        $classname = $table->getDatabase()->getPlatform()->getBuilderClass($type);
        if (!$classname) {
            $classname = $this->getBuilderClassName($type);
        }
        $builder   = new $classname($table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Returns a configured Pluralizer class.
     *
     * @return Pluralizer
     */
    public function getConfiguredPluralizer()
    {
        $classname = $this->getBuilderClassName('pluralizer');
        $pluralizer = new $classname();

        return $pluralizer;
    }

    public function setBuildConnections($buildConnections)
    {
        $this->buildConnections = $buildConnections;
    }

    /**
     * Returns all connections from the buildtime config.
     *
     * @param string $directory Relative to current working directory or absolute.
     *
     * @return array|null
     */
    public function getBuildConnections($directory = '.')
    {
        if (null === $this->buildConnections) {
            $buildTimeConfigPath = $this->getBuildProperty('buildtimeConfFile')
                ? $this->getBuildProperty('projectDir') . DIRECTORY_SEPARATOR . $this->getBuildProperty('buildtimeConfFile')
                : $directory . '/buildtime-conf.xml';
            if ($buildTimeConfigString = $this->getBuildProperty('buildtimeConf')) {
                // configuration passed as propel.buildtimeConf string
                // probably using the command line, which doesn't accept whitespace
                // therefore base64 encoded
                $this->parseBuildConnections(base64_decode($buildTimeConfigString));
            } elseif (file_exists($buildTimeConfigPath)) {
                // configuration stored in a buildtime-conf.xml file
                $this->parseBuildConnections(file_get_contents($buildTimeConfigPath));
            } else {
                $this->buildConnections = array();
            }
        }

        return $this->buildConnections;
    }

    protected function parseBuildConnections($xmlString)
    {
        $conf = simplexml_load_string($xmlString);
        $this->defaultBuildConnection = (string) $conf->propel->datasources['default'];
        $buildConnections = array();
        foreach ($conf->propel->datasources->datasource as $datasource) {
            $id = (string) $datasource['id'];
            $buildConnections[$id] = array(
                'adapter'  => (string) $datasource->adapter
            );
            foreach ((array) $datasource->connection as $key => $connection) {
                $buildConnections[$id][$key] = $connection;
            }
        }
        $this->buildConnections = $buildConnections;
    }

    public function getBuildConnection($databaseName = null)
    {
        $connections = $this->getBuildConnections();
        if (null === $databaseName) {
            $databaseName = $this->defaultBuildConnection;
        }
        if (isset($connections[$databaseName])) {
            return $connections[$databaseName];
        } else {
            // fallback to the single connection from build.properties
            return array(
                'adapter'  => $this->getBuildProperty('databaseAdapter'),
                'dsn'      => $this->getBuildProperty('databaseUrl'),
                'user'     => $this->getBuildProperty('databaseUser'),
                'password' => $this->getBuildProperty('databasePassword'),
            );
        }
    }

    public function getConnection($database)
    {
        $buildConnection = $this->getBuildConnection($database);
        $dsn = str_replace("@DB@", $database, $buildConnection['dsn']);

        // Set user + password to null if they are empty strings or missing
        $username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
        $password = isset($buildConnection['password']) && $buildConnection['password'] ? $buildConnection['password'] : null;

        $con = ConnectionFactory::create(array('dsn' => $dsn, 'user' => $username, 'password' => $password), AdapterFactory::create($buildConnection['adapter']));

        return $con;
    }
}
