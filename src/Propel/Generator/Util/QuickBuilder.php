<?php declare(strict_types = 1);

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Util;

use Exception;
use PDO;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Propel;

class QuickBuilder
{
    use VfsTrait;

    /**
     * The Xml.
     *
     * @var string
     */
    protected $schema = '';

    /**
     * The Database Schema.
     *
     * @var string
     */
    protected $schemaName = '';

    /**
     * @var \Propel\Generator\Platform\PlatformInterface|null
     */
    protected $platform;

    /**
     * @var \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    protected $config;

    /**
     * @var \Propel\Generator\Model\Database|null
     */
    protected $database;

    /**
     * @var \Propel\Generator\Reverse\SchemaParserInterface|null
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
     * If use the virtual or physical filesystem.
     * Default to virtual.
     *
     * @var bool
     */
    protected $vfs = true;

    /**
     * @param string $schema
     *
     * @return void
     */
    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * @param string $schemaName
     *
     * @return void
     */
    public function setSchemaName(string $schemaName): void
    {
        $this->schemaName = $schemaName;
    }

    /**
     * @return string
     */
    public function getSchemaName(): string
    {
        return $this->schemaName;
    }

    /**
     * @param \Propel\Generator\Reverse\SchemaParserInterface $parser
     *
     * @return void
     */
    public function setParser(SchemaParserInterface $parser): void
    {
        $this->parser = $parser;
    }

    /**
     * @return \Propel\Generator\Reverse\SchemaParserInterface|null
     */
    public function getParser(): ?SchemaParserInterface
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
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * Getter for the platform property
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform(): PlatformInterface
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
    public function setConfig(GeneratorConfigInterface $config): void
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
     * @return bool
     */
    public function isVfs(): bool
    {
        return $this->vfs;
    }

    /**
     * @param bool $vfs
     *
     * @return void
     */
    public function setVfs(bool $vfs): void
    {
        $this->vfs = $vfs;
    }

    /**
     * @param string $schema
     * @param string|null $dsn
     * @param string|null $user
     * @param string|null $pass
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     * @param bool $vfs
     *
     * @return \Propel\Runtime\Connection\ConnectionWrapper
     */
    public static function buildSchema(
        string $schema,
        ?string $dsn = null,
        ?string $user = null,
        ?string $pass = null,
        ?AdapterInterface $adapter = null,
        bool $vfs = true
    ): ConnectionWrapper {
        $builder = new self();
        $builder->setSchema($schema);
        $builder->setVfs($vfs);

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
    public function build(
        ?string $dsn = null,
        ?string $user = null,
        ?string $pass = null,
        ?AdapterInterface $adapter = null,
        ?array $classTargets = null
    ): ConnectionWrapper {
        $dsn = $dsn ?? 'sqlite::memory:';
        $adapter = $adapter ?? new SqliteAdapter();
        $classTargets = $classTargets ?? $this->classTargets;

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
    public function getDatabase(): ?Database
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
     * @return int the number of statements executed
     */
    public function buildSQL(ConnectionInterface $con): int
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
                if ($stmt instanceof StatementInterface) {
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
    public function updateDB(ConnectionInterface $con): ?Database
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
    public function readConnectedDatabase(): Database
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
    public function getSQL(): string
    {
        /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
        $platform = $this->getPlatform();

        return $platform->getAddTablesDDL($this->getDatabase());
    }

    /**
     * @param array|null $classTargets
     *
     * @return string
     */
    public function getBuildName(?array $classTargets = null): string
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
     * Build the classes files and include them.
     *
     * When generated to virtual filesystem, the classes reside in a unique file. When they're are built to
     * physical filesystem, which is supposed to be for debugging purpose, the classes reside on separate file,
     * for easier debug.
     *
     * @param string[]|null $classTargets array('tablemap', 'object', 'query', 'objectstub', 'querystub')
     *
     * @return void
     */
    public function buildClasses(?array $classTargets = null): void
    {
        $classes = $classTargets ?? ['tablemap', 'object', 'query', 'objectstub', 'querystub'];

        $includes = $this->isVfs() ? $this->buildClassesToVirtual($classes, $this->getDatabase()->getTables())
            : $this->buildClassesToPhysical($classes, $this->getDatabase()->getTables());

        foreach ($includes as $tempFile) {
            include($tempFile);
        }
    }

    /**
     * @param string[]|null $classTargets
     *
     * @return string
     */
    public function getClasses(?array $classTargets = null): string
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
    public function getClassesForTable(Table $table, ?array $classTargets = null): string
    {
        $classTargets = $classTargets ?? $this->classTargets;
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
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/ClassLoader/ClassCollectionLoader.php
     *
     * @param string $source
     *
     * @return string
     */
    public function fixNamespaceDeclarations(string $source): string
    {
        $cooperativeLexems = [T_WHITESPACE, T_NS_SEPARATOR, T_STRING];

        if (PHP_VERSION_ID >= 80000) {
            $cooperativeLexems = array_merge($cooperativeLexems, [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED]);
        }

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
                while (($t = $tokens[++$i]) && is_array($t) && in_array($t[0], $cooperativeLexems)) {
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
    protected function forceNamespace(string $code): string
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
    public function isIdentifierQuotingEnabled(): bool
    {
        return $this->identifierQuoting;
    }

    /**
     * @param bool $identifierQuoting
     *
     * @return void
     */
    public function setIdentifierQuoting(bool $identifierQuoting): void
    {
        $this->identifierQuoting = $identifierQuoting;
    }

    /**
     * Create separate classes to write to physical filesystem.
     *
     * @param string[] $classes
     * @param \Propel\Generator\Model\Table[] $tables Array of Table objects
     *
     * @return string[] The files to include
     */
    private function buildClassesToPhysical(array $classes, array $tables): array
    {
        $includes = [];
        $dirName = sys_get_temp_dir()
            . '/propelQuickBuild-' . Propel::VERSION . '-' . substr(sha1(getcwd()), 0, 10) . '/';
        if (!is_dir($dirName)) {
            mkdir($dirName);
        }
        foreach ($tables as $table) {
            foreach ($classes as $class) {
                $code = $this->getClassesForTable($table, [$class]);
                $tempFile = $dirName . str_replace('\\', '-', $table->getPhpName()) . "-$class.php";
                file_put_contents($tempFile, "<?php\n" . $code);
                $includes[] = $tempFile;
            }
        }

        return $includes;
    }

    /**
     * Create an all-classes file to write to virtual filesystem.
     *
     * @param string[] $classes
     * @param \Propel\Generator\Model\Table[] $tables Array of Table objects
     *
     * @return string[] The one element array, containing the file to include
     */
    private function buildClassesToVirtual(array $classes, array $tables): array
    {
        $allCode = '';
        $allCodeName = [];
        $includes = [];

        foreach ($tables as $table) {
            if (5 > count($allCodeName)) {
                $allCodeName[] = $table->getPhpName();
            }
            $allCode .= $this->getClassesForTable($table, $classes);
        }

        $tempFile = $this->newFile('propelQuickBuild/' . implode('_', $allCodeName) . '.php');
        file_put_contents($tempFile->url(), "<?php\n" . $allCode);
        $includes[] = $tempFile->url();

        return $includes;
    }
}
