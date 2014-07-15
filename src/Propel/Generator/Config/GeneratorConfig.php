<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Common\Config\ConfigurationManager;
use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\ClassNotFoundException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Generator\Util\BehaviorLocator;

/**
 * A class that holds build properties and provide a class loading mechanism for
 * the generator.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Cristiano Cinotti
 */
class GeneratorConfig extends ConfigurationManager implements GeneratorConfigInterface
{
    /**
     * @var BehaviorLocator
     */
    protected $behaviorLocator = null;

    /**
     * Connections configured in the `generator` section of the configuration file
     *
     * @var array
     */
    protected $buildConnections = null;

    /**
     * Creates and configures a new Platform class.
     *
     * @param  ConnectionInterface $con
     * @param  string              $database
     * @return PlatformInterface
     *
     * @throws Propel\Generator\Exception\ClassNotFoundException if the platform class doesn't exists
     * @throws Propel\Generator\Exception\BuildException         if the class isn't an implementation of PlatformInterface
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, $database = null)
    {
        if (null !== $database) {
            $clazz = '\\Propel\\Generator\\Platform\\' . ucfirst($this->getBuildConnection($database)['adapter']) . 'Platform';
        } else {
            $clazz = $this->get()['generator']['platformClass'];
        }

        $platform = $this->getInstance($clazz);
        $platform->setConnection($con);
        $platform->setGeneratorConfig($this);

        return $platform;
    }

    /**
     * Creates and configures a new SchemaParser class for specified platform.
     * @param  ConnectionInterface   $con
     * @return SchemaParserInterface
     *
     * @throws Propel\Generator\Exception\ClassNotFoundException if the class doesn't exists
     * @throws Propel\Generator\Exception\BuildException         if the class isn't an implementation of SchemaParserInterface
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null)
    {
        $clazz = $this->get()['migrations']['parserClass'];

        if (null === $clazz) {
            $clazz = '\\Propel\\Generator\\Reverse\\' . ucfirst($this->getBuildConnection()['adapter']) . 'SchemaParser';
        }

        $parser = $this->getInstance($clazz, null, '\\Propel\\Generator\\Reverse\\SchemaParserInterface');
        $parser->setConnection($con);
        $parser->setMigrationTable($this->get()['migrations']['tableName']);
        $parser->setGeneratorConfig($this);

        return $parser;
    }

    /**
     * Returns a configured data model builder class for specified table and
     * based on type ('object', 'query', 'tableMap' etc.).
     *
     * @param  Table            $table
     * @param  string           $type
     * @return DataModelBuilder
     *
     * @throws Propel\Generator\Exception\ClassNotFoundException if the type of builder is wrong and the builder class doesn't exists
     */
    public function getConfiguredBuilder(Table $table, $type)
    {
        $classname = $this->getConfigProperty('generator.objectModel.builders.' . $type);

        $builder = $this->getInstance($classname, $table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer()
    {
        $classname = $this->get()['generator']['objectModel']['pluralizerClass'];

        return $this->getInstance($classname, null, '\\Propel\\Common\\Pluralizer\\PluralizerInterface');
    }

    /**
     * Return an array of all configured connection properties, from `generator` and `reverse` 
     * sections of the configuration.
     *
     * @return array
     */
    public function getBuildConnections()
    {
        if (null === $this->buildConnections) {
            $connectionNames = $this->get()['generator']['connections'];

            $reverseConnection = $this->getConfigProperty('reverse.connection');
            if (null !== $reverseConnection && !in_array($reverseConnection, $connectionNames)) {
                $connectionNames[] = $reverseConnection;
            }

            foreach ($connectionNames as $name) {
                $this->buildConnections[$name] = $this->getConfigProperty('database.connections.' . $name);
            }
        }

        return $this->buildConnections;
    }

    /**
     * Return the connection properties array, of a given database name.
     * If the database name is null, it returns the default connection properties
     *
     * @param  string $databaseName
     * @return array
     *
     * @throws Propel\Generator\Exception\InvalidArgumentException if wrong database name
     */
    public function getBuildConnection($databaseName = null)
    {
        if (null === $databaseName) {
            $databaseName = $this->get()['generator']['defaultConnection'];
        }

        if (!array_key_exists($databaseName, $this->getBuildConnections())) {
            throw new InvalidArgumentException("Invalid database name: no configured connection named `$databaseName`.");
         }

        return $this->getBuildConnections()[$databaseName];
    }

    /**
     * Return a connection object of a given database name
     *
     * @param  string              $database
     * @return ConnectionInterface
     */
    public function getConnection($database = null)
    {
        $buildConnection = $this->getBuildConnection($database);

        //Still useful ?
        //$dsn = str_replace("@DB@", $database, $buildConnection['dsn']);
        $dsn = $buildConnection['dsn'];

        // Set user + password to null if they are empty strings or missing
        $username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
        $password = isset($buildConnection['password']) && $buildConnection['password'] ? $buildConnection['password'] : null;

        $con = ConnectionFactory::create(array('dsn' => $dsn, 'user' => $username, 'password' => $password), AdapterFactory::create($buildConnection['adapter']));

        return $con;
    }

    public function getBehaviorLocator()
    {
        if (!$this->behaviorLocator) {
            $this->behaviorLocator = new BehaviorLocator($this);
        }

        return $this->behaviorLocator;
    }

    /**
     * Return an instance of $className
     *
     * @param $className The name of the class to return an instance
     * @param $interfaceName The name of the interface to be implemented by the returned class
     *
     * @throws Propel\Generator\Exception\ClassNotFoundException   if the class doesn't exists
     * @throws Propel\Generator\Exception\InvalidArgumentException if the interface doesn't exists
     * @throws Propel\Generator\Exception\BuildException           if the class isn't an implementation of the given interface
     */
    private function getInstance($className, $arguments = null, $interfaceName = null)
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException("Class $className not found.");
        }

        $object = new $className($arguments);

        if (null !== $interfaceName) {
            if (!interface_exists($interfaceName)) {
                throw new InvalidArgumentException("Interface $interfaceName does not exists.");
            }

            if (!$object instanceof $interfaceName) {
                throw new BuildException("Specified class ($className) does not implement $interfaceName interface.");
            }
        }

        return $object;
    }
}
