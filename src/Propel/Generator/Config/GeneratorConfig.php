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
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Common\Types\FieldTypeInterface;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\ClassNotFoundException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\SqlDefaultPlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Generator\Reverse\SqlSchemaParserInterface;
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
     * @param  string              $platform
     * @param  ConnectionInterface $con
     *
     * @return PlatformInterface
     *
     * @throws \Propel\Generator\Exception\ClassNotFoundException if the platform class doesn't exists
     * @throws \Propel\Generator\Exception\BuildException         if the class isn't an implementation of
     *                                                            PlatformInterface
     */
    public function createPlatform($platform, ConnectionInterface $con = null)
    {
        if (!$platform) {
            //todo, place it into configuration?
            $platform = 'mysql';
        }

        $classes = [
            $platform,
            '\\Propel\\Generator\\Platform\\' . $platform,
            '\\Propel\\Generator\\Platform\\' . ucfirst($platform),
            '\\Propel\\Generator\\Platform\\' . ucfirst(strtolower($platform)) . 'Platform',
        ];

        $platformClass = null;

        foreach ($classes as $class) {
            if (class_exists($class)) {
                $platformClass = $class;
                break;
            }
        }

        if (null === $platformClass) {
            throw new BuildException(sprintf('Platform `%s` not found.', $platform));
        }

        /** @var SqlDefaultPlatform $platform */
        $platform = new $platformClass;
        $platform->setConnection($con);
        $platform->setGeneratorConfig($this);

        return $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function createPlatformForDatabase($name = null, ConnectionInterface $con = null)
    {
        if (isset($this->get()['generator']['platformClass'])) {
            $class = $this->get()['generator']['platformClass'];
            return new $class;
        }

        $buildConnection = $this->getBuildConnection($name);

        return $this->createPlatform($buildConnection['adapter'], $con);
    }

    /**
     * Creates and configures a new SchemaParser class for specified platform.
     *
     * @param  ConnectionInterface $con
     *
     * @return SchemaParserInterface
     *
     * @throws \Propel\Generator\Exception\ClassNotFoundException if the class doesn't exists
     * @throws \Propel\Generator\Exception\BuildException         if the class isn't an implementation of
     *                                                            SchemaParserInterface
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null)
    {
        $clazz = $this->get()['migrations']['parserClass'];

        if (null === $clazz) {
            $clazz = '\\Propel\\Generator\\Reverse\\' . ucfirst(
                    $this->getBuildConnection()['adapter']
                ) . 'SchemaParser';
        }

        /** @var SchemaParserInterface $parser */
        $parser = $this->getInstance($clazz, null, '\\Propel\\Generator\\Reverse\\SchemaParserInterface');
        $parser->setConnection($con);
        if ($parser instanceof SqlSchemaParserInterface) {
            $parser->setMigrationTable($this->get()['migrations']['tableName']);
        }
        $parser->setGeneratorConfig($this);

        return $parser;
    }

    /**
     * @param string $name
     *
     * @return FieldTypeInterface|BuildableFieldTypeInterface
     */
    public function getFieldType($name)
    {
        $name = strtolower($name);
        $types = $this->get()['types'];

        if (!isset($types[$name])) {
            throw new \InvalidArgumentException(sprintf('Could not find field type %s', $name));
        }

        $class = $types[$name];

        return new $class;
    }

    /**
     * Returns a configured data model builder class for specified table and
     * based on type ('object', 'query', 'tableMap' etc.).
     *
     * @param  Entity $entity
     * @param  string $type
     *
     * @return DataModelBuilder
     *
     * @throws \Propel\Generator\Exception\ClassNotFoundException if the type of builder is wrong and the builder class
     *                                                            doesn't exists
     */
    public function getConfiguredBuilder(Entity $entity, $type)
    {
        $className = $this->getConfigProperty('generator.objectModel.builders.' . $type);
        if (!$className) {
            throw new InvalidArgumentException(sprintf('Builder for `%s` not found.', $type));
        }

        $builder = $this->getInstance($className, $entity);
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
                if ($definition = $this->getConfigProperty('database.connections.' . $name)) {
                    $this->buildConnections[$name] = $definition;
                }
            }
        }

        return $this->buildConnections;
    }

    /**
     * Return the connection properties array, of a given database name.
     * If the database name is null, it returns the default connection properties
     *
     * @param  string $databaseName
     *
     * @return array
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException if wrong database name
     */
    public function getBuildConnection($databaseName = null)
    {
        if (!$databaseName && isset($this->get()['generator']['defaultConnection'])) {
            $databaseName = $this->get()['generator']['defaultConnection'];
        }

        if (!array_key_exists($databaseName, $this->getBuildConnections())) {
            throw new InvalidArgumentException(
                "Invalid database name: no configured connection named `$databaseName`."
            );
        }

        return $this->getBuildConnections()[$databaseName];
    }

    /**
     * Return a connection object of a given database name
     *
     * @param  string $database
     *
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

        $con = ConnectionFactory::create(
            array('dsn' => $dsn, 'user' => $username, 'password' => $password),
            AdapterFactory::create($buildConnection['adapter'])
        );

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
     * @param string $className     The name of the class to return an instance
     * @param array  $arguments     The name of the interface to be implemented by the returned class
     * @param string $interfaceName The name of the interface to be implemented by the returned class
     *
     * @throws \Propel\Generator\Exception\ClassNotFoundException   if the class doesn't exists
     * @throws \Propel\Generator\Exception\InvalidArgumentException if the interface doesn't exists
     * @throws \Propel\Generator\Exception\BuildException           if the class isn't an implementation of the given
     *                                                              interface
     *
     * @return AbstractBuilder
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
