<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Util;

use Exception;
use PDO;
use PDOStatement;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Propel;

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
     * @var \Propel\Generator\Platform\PlatformInterface
     */
    protected $platform;

    /**
     * @var \Propel\Generator\Config\GeneratorConfigInterface
     */
    protected $config;

    /**
     * @var \Propel\Generator\Model\Database
     */
    protected $database;

    /**
     * @var \Propel\Generator\Reverse\SchemaParserInterface
     */
    protected $parser;

    /**
     * @var array
     */
    protected $classTargets = ['tablemap', 'object', 'query', 'objectstub', 'querystub'];

    /**
     * Identifier quoting for reversed database.
     *
     * @var bool
     */
    protected $identifierQuoting = false;

    /**
     * @param string $schema
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return void
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * Getter for the platform property
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform()
    {
        if ($this->platform === null) {
            $this->platform = new SqlitePlatform();
        }

        $this->platform->setIdentifierQuoting($this->identifierQuoting);

        return $this->platform;
    }

    /**
     * Setter for the config property
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $config
     *
     * @return void
     */
    public function setConfig(GeneratorConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Getter for the config property
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface
     */
    public function getConfig()
    {
        if ($this->config === null) {
            $this->config = new QuickGeneratorConfig();
        }

        return $this->config;
    }

    /**
     * @param string $schema
     * @param string|null $dsn
     * @param string|null $user
     * @param string|null $pass
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionWrapper
     */
    public static function buildSchema($schema, $dsn = null, $user = null, $pass = null, $adapter = null)
    {
        $builder = new self();
        $builder->setSchema($schema);

        return $builder->build($dsn, $user, $pass, $adapter);
    }

    /**
     * @param string|null $dsn
     * @param string|null $user
     * @param string|null $pass
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     * @param array|null $classTargets
     *
     * @return \Propel\Runtime\Connection\ConnectionWrapper
     */
    public function build($dsn = null, $user = null, $pass = null, $adapter = null, ?array $classTargets = null)
    {
        if ($dsn === null) {
            $dsn = 'sqlite::memory:';
        }
        if ($adapter === null) {
            $adapter = new SqliteAdapter();
        }
        if ($classTargets === null) {
            $classTargets = $this->classTargets;
        }
        $pdo = new PdoConnection($dsn, $user, $pass);
        $con = new ConnectionWrapper($pdo);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        /** @var \Propel\Runtime\Adapter\Pdo\SqliteAdapter $adapter */
        $adapter->initConnection($con, []);
        $this->buildSQL($con);
        $this->buildClasses($classTargets);
        $name = $this->getDatabase()->getName();
        Propel::getServiceContainer()->setAdapter($name, $adapter);
        Propel::getServiceContainer()->setConnection($name, $con);

        return $con;
    }

    /**
     * @return \Propel\Generator\Model\Database|null
     */
    public function getDatabase()
    {
        if ($this->database === null) {
            $xtad = new SchemaReader($this->getPlatform());
            $xtad->setGeneratorConfig($this->getConfig());
            $appData = $xtad->parseString($this->schema);
            $this->database = $appData->getDatabase(); // does final initialization
        }

        return $this->database;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @throws \Exception
     *
     * @return int
     */
    public function buildSQL(ConnectionInterface $con)
    {
        $sql = $this->getSQL();
        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            if (strpos($statement, 'DROP') === 0) {
                // drop statements cause errors since the table doesn't exist
                continue;
            }
            try {
                $stmt = $con->prepare($statement);
                if ($stmt instanceof PDOStatement) {
                    // only execute if has no error
                    $stmt->execute();
                }
            } catch (Exception $e) {
                throw new Exception('SQL failed: ' . $statement, 0, $e);
            }
        }

        return count($statements);
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return \Propel\Generator\Model\Database|null
     */
    public function updateDB(ConnectionInterface $con)
    {
        $database = $this->readConnectedDatabase();
        $diff = DatabaseComparator::computeDiff($database, $this->database);

        if ($diff === false) {
            return null;
        }
        /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
        $platform = $this->database->getPlatform();
        $sql = $platform->getModifyDatabaseDDL($diff);

        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            try {
                $stmt = $con->prepare($statement);
                $stmt->execute();
            } catch (Exception $e) {
                //echo $sql; //uncomment for better debugging
                throw new BuildException(sprintf(
                    "Can not execute SQL: \n%s\nFrom database: \n%s\n\nTo database: \n%s\n\nDiff:\n%s",
                    $statement,
                    $this->database,
                    $database,
                    $diff
                ), null, $e);
            }
        }

        return $database;
    }

    /**
     * @return \Propel\Generator\Model\Database
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

    /**
     * @return string
     */
    public function getSQL()
    {
        /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
        $platform = $this->getPlatform();

        return $platform->getAddTablesDDL($this->getDatabase());
    }

    /**
     * @param string[]|null $classTargets
     *
     * @return string
     */
    public function getBuildName($classTargets = null)
    {
        $tables = [];
        foreach ($this->getDatabase()->getTables() as $table) {
            if (count($tables) > 3) {
                break;
            }
            $tables[] = $table->getName();
        }
        $name = implode('_', $tables);
        if (!$classTargets || count($classTargets) === 5) {
            $name .= '-all';
        } else {
            $name .= '-' . implode('_', $classTargets);
        }

        return $name;
    }

    /**
     * @param string[]|null $classTargets array('tablemap', 'object', 'query', 'objectstub', 'querystub')
     * @param bool $separate pass true to get for each class a own file. better for debugging.
     *
     * @return void
     */
    public function buildClasses(?array $classTargets = null, $separate = false)
    {
        $classes = $classTargets === null ? ['tablemap', 'object', 'query', 'objectstub', 'querystub'] : $classTargets;

        $dirHash = substr(sha1(getcwd()), 0, 10);
        $dir = sys_get_temp_dir() . '/propelQuickBuild-' . Propel::VERSION . "-$dirHash/";

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $includes = [];
        $allCode = '';
        $allCodeName = [];
        foreach ($this->getDatabase()->getTables() as $table) {
            if (5 > count($allCodeName)) {
                $allCodeName[] = $table->getPhpName();
            }

            if ($separate) {
                foreach ($classes as $class) {
                    $code = $this->getClassesForTable($table, [$class]);
                        $tempFile = $dir
                            . str_replace('\\', '-', $table->getPhpName())
                            . "-$class"
                            . '.php';
                        file_put_contents($tempFile, "<?php\n" . $code);
                        $includes[] = $tempFile;
                }
            } else {
                $code = $this->getClassesForTable($table, $classes);
                $allCode .= $code;
            }
        }
        if ($separate) {
            foreach ($includes as $tempFile) {
                include($tempFile);
            }
        } else {
            $tempFile = $dir . implode('_', $allCodeName) . '.php';
            file_put_contents($tempFile, "<?php\n" . $allCode);
            include($tempFile);
        }
    }

    /**
     * @param string[]|null $classTargets
     *
     * @return string
     */
    public function getClasses(?array $classTargets = null)
    {
        $script = '';
        foreach ($this->getDatabase()->getTables() as $table) {
            $script .= $this->getClassesForTable($table, $classTargets);
        }

        return $script;
    }

    /**
     * @param \Propel\Generator\Model\Table $table
     * @param string[]|null $classTargets
     *
     * @return string
     */
    public function getClassesForTable(Table $table, ?array $classTargets = null)
    {
        if ($classTargets === null) {
            $classTargets = $this->classTargets;
        }

        $script = '';

        foreach ($classTargets as $target) {
            /** @var \Propel\Generator\Builder\Om\AbstractOMBuilder $abstractBuilder */
            $abstractBuilder = $this->getConfig()->getConfiguredBuilder($table, $target);
            $class = $abstractBuilder->build();
            $script .= $this->fixNamespaceDeclarations($class);
        }

        if ($col = $table->getChildrenColumn()) {
            if ($col->isEnumeratedClasses()) {
                foreach ($col->getChildren() as $child) {
                    if ($child->getAncestor()) {
                        /** @var \Propel\Generator\Builder\Om\QueryInheritanceBuilder $builder */
                        $builder = $this->getConfig()->getConfiguredBuilder($table, 'queryinheritance');
                        $builder->setChild($child);
                        $class = $builder->build();
                        $script .= $this->fixNamespaceDeclarations($class);

                        foreach (['objectmultiextend', 'queryinheritancestub'] as $target) {
                            /** @var \Propel\Generator\Builder\Om\MultiExtendObjectBuilder $builder */
                            $builder = $this->getConfig()->getConfiguredBuilder($table, $target);
                            $builder->setChild($child);
                            $class = $builder->build();
                            $script .= $this->fixNamespaceDeclarations($class);
                        }
                    }
                }
            }
        }

        if ($table->getInterface()) {
            $interface = $this->getConfig()->getConfiguredBuilder($table, 'interface')->build();
            $script .= $this->fixNamespaceDeclarations($interface);
        }

        if ($table->hasAdditionalBuilders()) {
            foreach ($table->getAdditionalBuilders() as $builderClass) {
                $builder = new $builderClass($table);
                $class = $builder->build();
                $script .= $this->fixNamespaceDeclarations($class);
            }
        }

        $script = str_replace('<?php', '', $script);

        return $script;
    }

    /**
     * @param string $schema
     * @param string $tableName
     *
     * @return void
     */
    public static function debugClassesForTable($schema, $tableName)
    {
        $builder = new self();
        $builder->setSchema($schema);
        foreach ($builder->getDatabase()->getTables() as $table) {
            if ($table->getName() == $tableName) {
                echo $builder->getClassesForTable($table);
            }
        }
    }

    /**
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/ClassLoader/ClassCollectionLoader.php
     *
     * @param string $source
     *
     * @return string
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
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                // strip comments
                $output .= $token[1];
            } elseif ($token[0] === T_NAMESPACE) {
                if ($inNamespace) {
                    $output .= "}\n";
                }
                $output .= $token[1];

                // namespace name and whitespaces
                while (($t = $tokens[++$i]) && is_array($t) && in_array($t[0], [T_WHITESPACE, T_NS_SEPARATOR, T_STRING])) {
                    $output .= $t[1];
                }
                if (is_string($t) && $t === '{') {
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
     * @param string $code
     *
     * @return string
     */
    protected function forceNamespace($code)
    {
        if (preg_match('/\nnamespace/', $code) === 0) {
            $use = array_filter(explode(PHP_EOL, $code), function ($string) {
                return substr($string, 0, 5) === 'use \\';
            });

            $code = str_replace($use, '', $code);

            return "\nnamespace\n{\n" . $code . "\n}\n";
        }

        return $code;
    }

    /**
     * @return bool
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param bool $identifierQuoting
     *
     * @return void
     */
    public function setIdentifierQuoting($identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
    }
}
