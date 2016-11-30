<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Util;

use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Configuration;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionWrapper;

class QuickBuilder
{
    /**
     * The Xml.
     *
     * @var string
     */
    protected $schema;

    /**
     * The Database Schema.
     *
     * @var string
     */
    protected $schemaName;

    /**
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * @var GeneratorConfigInterface
     */
    protected $config;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var SchemaParserInterface
     */
    protected $parser;

    /**
     * @var array
     */
    protected $classTargets = array(
        'activerecordtrait',
        'object',
        'entitymap',
        'proxy',
        'query',
        'repository',
        'repositorystub',
        'querystub'
    );

    /**
     * Identifier quoting for reversed database.
     *
     * @var bool
     */
    protected $identifierQuoting = false;

    /**
     * Map from full entity class name to full entity map class name,
     * build in this quickBuilder. Can be used to register those tableMaps
     * to Propel\Runtime\Configuration, so queries/models are aware of it.
     *
     * @var string[]
     */
    protected $knownEntityClassNames = [];

    /**
     * @var Configuration
     */
    public static $configuration;

    /**
     * @param string $schema
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param string $schemaName
     */
    public function setSchemaName($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    /**
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    /**
     * @param \Propel\Generator\Reverse\SchemaParserInterface $parser
     */
    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return \Propel\Generator\Reverse\SchemaParserInterface
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Setter for the platform property
     *
     * @param PlatformInterface $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * Getter for the platform property
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        if (null === $this->platform) {
            $this->platform = new SqlitePlatform();
        }

        $this->platform->setIdentifierQuoting($this->identifierQuoting);

        return $this->platform;
    }

    /**
     * Setter for the config property
     *
     * @param GeneratorConfigInterface $config
     */
    public function setConfig(GeneratorConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Getter for the config property
     *
     * @return GeneratorConfigInterface
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = new QuickGeneratorConfig();
        }

        return $this->config;
    }

    /**
     * @return Configuration
     */
    public static function buildSchema($schema, $dsn = null, $user = null, $pass = null, $adapter = null)
    {
        $builder = new self;
        $builder->setSchema($schema);

        return $builder->build($dsn, $user, $pass, $adapter);
    }

    /**
     * @param string $dsn
     * @param string $user
     * @param string $pass
     * @param AdapterInterface $adapter
     * @param array $classTargets
     *
     * @return Configuration
     * @throws \Exception
     */
    public function build(
        $dsn = null,
        $user = null,
        $pass = null,
        AdapterInterface $adapter = null,
        array $classTargets = null
    ) {
        if (null === $dsn) {
            $sqliteFile = 'latest_quickbuilder_sqlite.db';
            $reflection = new \ReflectionClass('\Propel\Tests\TestCase');
            $sqliteFile = realpath(dirname($reflection->getFileName()) . '/../../') . '/' . $sqliteFile;
            if (file_exists($sqliteFile)) {
                unlink($sqliteFile);
            }
            $dsn = 'sqlite:' . $sqliteFile;
        }
        if (null === $adapter) {
            $adapter = new SqliteAdapter();
        }
        if (null === $classTargets) {
            $classTargets = $this->classTargets;
        }

        static::$configuration = Configuration::getCurrentConfigurationOrCreate();
        static::$configuration->closeConnections();

        $connectionConfiguration = [
            'dsn' => $dsn,
            'user' => $user,
            'password' => $pass,
            'classname' => '\Propel\Runtime\Connection\DebugPDO'
        ];

        if (static::$configuration->hasConnectionManager($this->getDatabase()->getName())) {
            //overwriting a connection with a wrong incompatible adapter could go horrible wrong, so we forbid it.
            throw new \InvalidArgumentException('Could not build due to already existing connection-manager ' . $this->getDatabase()->getName());
        }

        if (static::$configuration->hasAdapter($this->getDatabase()->getName())) {
            throw new \InvalidArgumentException('Could not build due to already existing an adapter ' . $this->getDatabase()->getName());
        }

        static::$configuration->setAdapter($this->getDatabase()->getName(), $adapter);
        static::$configuration->buildConnectionManager($this->getDatabase()->getName(), $connectionConfiguration);

        $this->buildSQL(static::$configuration->getConnectionManager($this->getDatabase()->getName())->getWriteConnection());
        $this->buildClasses($classTargets, true);

        $this->registerEntities(static::$configuration);

        return static::$configuration;
    }

    public function setAdapter($adapter)
    {
        static::$configuration->setAdapter($this->getDatabase()->getName(), $adapter);
    }

    public function getDatabase()
    {
        if (null === $this->database) {
            $reader = new SchemaReader();
            $reader->setGeneratorConfig($this->getConfig());
            $reader->setPlatform($this->getPlatform());
            $appData = $reader->parseString($this->schema);
            $this->database = $appData->getDatabase(); // does final initialization
        }

        $this->database->setPlatform($this->getPlatform());

        return $this->database;
    }

    public function buildSQL(ConnectionInterface $con)
    {
        $sql = $this->getSQL();
        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            if (strpos($statement, 'DROP') === 0) {
                // drop statements cause errors since the entity doesn't exist
//                continue;
            }
            try {
                static::$configuration->debug('buildSQL: ' . $statement);
                $stmt = $con->prepare($statement);
                if ($stmt instanceof \PDOStatement) {
                    // only execute if has no error
                    $stmt->execute();
                }
            } catch (\Exception $e) {
                echo implode("\n", $statements);
                throw new \Exception('SQL failed: ' . $statement, 0, $e);
            }
        }

        return count($statements);
    }

    public function updateDB(ConnectionInterface $con)
    {
        $database = $this->readConnectedDatabase();
        $diff = DatabaseComparator::computeDiff($database, $this->database);

        if (false === $diff) {
            return null;
        }
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            try {
                $stmt = $con->prepare($statement);
                $stmt->execute();
            } catch (\Exception $e) {
                //echo $sql; //uncomment for better debugging
                throw new BuildException(
                    sprintf(
                        "Can not execute SQL: \n%s\nFrom database: \n%s\n\nTo database: \n%s\n\nDiff:\n%s",
                        $statement,
                        $this->database,
                        $database,
                        $diff
                    ), null, $e
                );
            }
        }

        return $database;
    }

    /**
     * @return Database
     */
    public function readConnectedDatabase()
    {
        $this->getDatabase();
        $database = new Database();
        $database->setSchema($this->database->getSchema());
        $database->setName($this->database->getName());
        $database->setPlatform($this->getPlatform());
        $this->getParser()->parse($database);

        return $database;
    }

    public function getSQL()
    {
        return $this->getPlatform()->getAddEntitiesDDL($this->getDatabase());
    }

    public function getBuildName($classTargets = null)
    {
        $entitys = [];
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if (count($entitys) > 3) {
                break;
            }
            $entitys[] = $entity->getName();
        }
        $name = implode('_', $entitys);
        if (!$classTargets || count($classTargets) == 5) {
            $name .= '-all';
        } else {
            $name .= '-' . implode('_', $classTargets);
        }

        return $name;
    }

    /**
     * @param array $classTargets array('entitymap', 'object', 'query', 'activerecordtrait', 'querystub')
     * @param bool  $separate     pass true to get for each class a own file. better for debugging.
     */
    public function buildClasses(array $classTargets = null, $separate = false)
    {
        $classes = $classTargets === null ? $this->classTargets : $classTargets;

        $dirHash = substr(sha1(getcwd()), 0, 10);
        $dir = sys_get_temp_dir() . "/propelQuickBuild-$dirHash/";

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $includes = [];
        $allCode = '';
        $allCodeName = [];
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if (5 > count($allCodeName)) {
                $allCodeName[] = $entity->getName();
            }

            if ($separate) {
                foreach ($classes as $class) {
                    $code = $this->getClassesForEntity($entity, [$class]);
                    $tempFile = $dir
                        . str_replace('\\', '-', $entity->getFullClassName())
                        . "-$class"
                        . '.php';
                    file_put_contents($tempFile, "<?php\n" . $code);
                    $includes[] = $tempFile;
                }

                if ($entity->hasAdditionalBuilders()) {
                    $code = $this->getClassesFromAdditionalBuilders($entity);
                    $tempFile = $dir
                        . str_replace('\\', '-', $entity->getFullClassName())
                        . 'additional.php';
                    file_put_contents($tempFile, "<?php\n" . $code);
                    $includes[] = $tempFile;
                }
            } else {
                $code = $this->getClassesForEntity($entity, $classes);
                if ($entity->hasAdditionalBuilders()) {
                    $code .= $this->getClassesFromAdditionalBuilders($entity);
                }
                $allCode .= $code;
            }
        }
        if ($separate) {
            foreach ($includes as $tempFile) {
                include($tempFile);
            }
        } else {
            $tempFile = $dir . join('_', $allCodeName) . '.php';
            file_put_contents($tempFile, "<?php\n" . $allCode);
            include($tempFile);
        }
    }

    public function getClasses(array $classTargets = null)
    {
        $script = '';
        foreach ($this->getDatabase()->getEntities() as $entity) {
            $script .= $this->getClassesForEntity($entity, $classTargets);
        }

        return $script;
    }

    /**
     * @param Configuration $configuration
     */
    public function registerEntities(Configuration $configuration = null)
    {
        if (!$configuration) {
            $configuration = Configuration::getCurrentConfiguration();
        }

        foreach ($this->knownEntityClassNames as $databaseName => $entityNames) {
            $configuration->registerEntity($databaseName, $entityNames);
        }
    }

    public function getClassesForEntity(Entity $entity, array $classTargets = null)
    {
        if (null === $classTargets) {
            $classTargets = $this->classTargets;
        }

        $script = '';

        foreach ($classTargets as $target) {
            $builder = $this->getConfig()->getConfiguredBuilder($entity, $target);
            if ($builder instanceof EntityMapBuilder) {
                $dbName = $builder->getEntity()->getDatabase()->getName();
                $fullEntityClassName = $builder->getObjectBuilder()->getFullClassName();
                $this->knownEntityClassNames[$dbName][] = $fullEntityClassName;
            }
            $source = $builder->build();
            $script .= $this->fixNamespaceDeclarations($source);
        }

        if ($col = $entity->getChildrenField()) {
            if ($col->isEnumeratedClasses()) {
                foreach ($col->getChildren() as $child) {
                    if ($child->getAncestor()) {
                        $builder = $this->getConfig()->getConfiguredBuilder($entity, 'queryinheritance');
                        $builder->setChild($child);
                        $class = $builder->build();
                        $script .= $this->fixNamespaceDeclarations($class);

                        foreach (array('objectmultiextend', 'queryinheritancestub') as $target) {
                            $builder = $this->getConfig()->getConfiguredBuilder($entity, $target);
                            $builder->setChild($child);
                            $class = $builder->build();
                            $script .= $this->fixNamespaceDeclarations($class);
                        }
                    }
                }
            }
        }

        $script = str_replace('<?php', '', $script);

        return $script;
    }

    public static function debugClassesForEntity($schema, $entityName)
    {
        $builder = new self;
        $builder->setSchema($schema);
        foreach ($builder->getDatabase()->getEntities() as $entity) {
            if ($entity->getName() == $entityName) {
                echo $builder->getClassesForEntity($entity);
            }
        }
    }

    /**
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/ClassLoader/ClassCollectionLoader.php
     */
    public function fixNamespaceDeclarations($source)
    {
        $source = $this->forceNamespace($source);

        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        $inNamespace = false;
        $tokens = token_get_all($source);

        for ($i = 0, $max = count($tokens); $i < $max; $i++) {
            $token = $tokens[$i];
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                // strip comments
                $output .= $token[1];
            } elseif (T_NAMESPACE === $token[0]) {
                if ($inNamespace) {
                    $output .= "}\n";
                }
                $output .= $token[1];

                // namespace name and whitespaces
                while (($t = $tokens[++$i]) && is_array($t) && in_array(
                        $t[0],
                        array(T_WHITESPACE, T_NS_SEPARATOR, T_STRING)
                    )) {
                    $output .= $t[1];
                }
                if (is_string($t) && '{' === $t) {
                    $inNamespace = false;
                    --$i;
                } else {
                    $output .= "\n{";
                    $inNamespace = true;
                }
            } else {
                $output .= $token[1];
            }
        }

        if ($inNamespace) {
            $output .= "}\n";
        }

        return $output;
    }

    /**
     * Prevent generated class without namespace to fail.
     *
     * @param  string $code
     *
     * @return string
     */
    protected function forceNamespace($code)
    {
        if (0 === preg_match('/\nnamespace/', $code)) {
            return "\nnamespace\n{\n" . $code . "\n}\n";
        }

        return $code;
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $identifierQuoting
     */
    public function setIdentifierQuoting($identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
    }

    /**
     * @param $entity
     *
     * @return string
     */
    protected function getClassesFromAdditionalBuilders($entity)
    {
        if ($entity->hasAdditionalBuilders()) {
            foreach ($entity->getAdditionalBuilders() as $builderClass) {
                $builder = new $builderClass($entity);
                $builder->setGeneratorConfig($this->getConfig());
                $code = $builder->build();
                $code = str_replace('<?php', '', $code);

                return $this->fixNamespaceDeclarations($code);
            }
        }
    }
}
