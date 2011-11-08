<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Util;

use Propel\Generator\Builder\Util\XmlToAppData;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Table;

use Propel\Runtime\Propel;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionPdo;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\StatementInterface;

use \PDO;

class QuickBuilder
{
    protected $schema, $platform, $config, $database;

    public function setSchema($schema)
    {
        $this->schema = $schema;
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
            $this->platform = new \Propel\Generator\Platform\SqlitePlatform();
        }

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
            $this->config = new \Propel\Generator\Config\QuickGeneratorConfig();
        }

        return $this->config;
    }

    public static function buildSchema($schema, $dsn = null, $user = null, $pass = null, $adapter = null)
    {
        $builder = new self;
        $builder->setSchema($schema);

        return $builder->build($dsn, $user, $pass, $adapter);
    }

    public function build($dsn = null, $user = null, $pass = null, $adapter = null)
    {
        if (null === $dsn) {
            $dsn = 'sqlite::memory:';
        }
        if (null === $adapter) {
            $adapter = new \Propel\Runtime\Adapter\SqliteAdapter();
        }
        $pdo = new ConnectionPdo($dsn, $user, $pass);
        $con = new ConnectionWrapper($pdo);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $this->buildSQL($con);
        $this->buildClasses();
        $name = $this->getDatabase()->getName();
        if (!Propel::isInit()) {
            Propel::setConfiguration(array('datasources' => array('default' => $name)));
        }
        Propel::setDB($name, $adapter);
        Propel::setConnection($name, $con, Propel::CONNECTION_READ);
        Propel::setConnection($name, $con, Propel::CONNECTION_WRITE);

        return $con;
    }

    public function getDatabase()
    {
        if (null === $this->database) {
            $xtad = new XmlToAppData($this->getPlatform());
            $xtad->setGeneratorConfig($this->getConfig());
            $appData = $xtad->parseString($this->schema);
            $this->database = $appData->getDatabase(); // does final initialization
        }

        return $this->database;
    }

    public function buildSQL(ConnectionInterface $con)
    {
        $statements = SqlParser::parseString($this->getSQL());
        foreach ($statements as $statement) {
            if (strpos($statement, 'DROP') === 0) {
                // drop statements cause errors since the table doesn't exist
                continue;
            }
            $stmt = $con->prepare($statement);
            if ($stmt instanceof StatementInterface) {
                // only execute if has no error
                $stmt->execute();
            }
        }

        return count($statements);
    }

    public function getSQL()
    {
        return $this->getPlatform()->getAddTablesDDL($this->getDatabase());
    }

    public function buildClasses()
    {
        eval($this->getClasses());
    }

    public function getClasses()
    {
        $script = '';
        foreach ($this->getDatabase()->getTables() as $table) {
            $script .= $this->getClassesForTable($table);
        }

        return $script;
    }

    public function getClassesForTable(Table $table)
    {
        $script = '';

        foreach (array('tablemap', 'peer', 'object', 'query', 'peerstub', 'objectstub', 'querystub') as $target) {
            $class = $this->getConfig()->getConfiguredBuilder($table, $target)->build();
            $class = "\nnamespace\n{\n" . $class . "\n}\n";
            $script .= $this->fixNamespaceDeclarations($class);
        }

        if ($col = $table->getChildrenColumn()) {
            if ($col->isEnumeratedClasses()) {
                foreach ($col->getChildren() as $child) {
                    if ($child->getAncestor()) {
                        $builder = $this->getConfig()->getConfiguredBuilder('queryinheritance', $target);
                        $builder->setChild($child);
                        $class = $builder->build();
                        $class = "\nnamespace\n{\n" . $class . "\n}\n";
                        $script .= $this->fixNamespaceDeclarations($class);
                    }
                    foreach (array('objectmultiextend', 'queryinheritancestub') as $target) {
                        $builder = $this->getConfig()->getConfiguredBuilder($table, $target);
                        $builder->setChild($child);
                        $class = $builder->build();
                        $class = "\nnamespace\n{\n" . $class . "\n}\n";
                        $script .= $this->fixNamespaceDeclarations($class);
                    }
                }
            }
        }

        if ($table->getInterface()) {
            $interface = $this->getConfig()->getConfiguredBuilder('interface', $target)->build();
            $interface = "\nnamespace\n{\n" . $interface . "\n}\n";
            $script .= $this->fixNamespaceDeclarations($interface);
        }

        if ($table->treeMode()) {
            switch($table->treeMode()) {
            case 'NestedSet':
                foreach (array('nestedsetpeer', 'nestedset') as $target) {
                    $class = $this->getConfig()->getConfiguredBuilder($table, $target)->build();
                    $class = "\nnamespace\n{\n" . $class . "\n}\n";
                    $script .= $this->fixNamespaceDeclarations($class);
                }
                break;
            case 'MaterializedPath':
                foreach (array('nodepeer', 'node') as $target) {
                    $class = $this->getConfig()->getConfiguredBuilder($table, $target)->build();
                    $class = "\nnamespace\n{\n" . $class . "\n}\n";
                    $script .= $this->fixNamespaceDeclarations($class);
                }
                foreach (array('nodepeerstub', 'nodestub') as $target) {
                    $class = $this->getConfig()->getConfiguredBuilder($table, $target)->build();
                    $class = "\nnamespace\n{\n" . $class . "\n}\n";
                    $script .= $this->fixNamespaceDeclarations($class);
                }
                break;
            case 'AdjacencyList':
                // No implementation for this yet.
            default:
                break;
            }
        }

        if ($table->hasAdditionalBuilders()) {
            foreach ($table->getAdditionalBuilders() as $builderClass) {
                $builder = new $builderClass($table);
                $class = $builder->build();
                $class = "\nnamespace\n{\n" . $class . "\n}\n";
                $script .= $this->fixNamespaceDeclarations($class);
            }
        }

        $script = str_replace('<?php', '', $script);

        return $script;
    }

    public static function debugClassesForTable($schema, $tableName)
    {
        $builder = new self;
        $builder->setSchema($schema);
        foreach ($builder->getDatabase()->getTables() as $table) {
            if ($table->getName() == $tableName) {
                echo $builder->getClassesForTable($table);
            }
        }
    }

    /**
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/ClassLoader/ClassCollectionLoader.php
     */
    public function fixNamespaceDeclarations($source)
    {
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
                continue;
            } elseif (T_NAMESPACE === $token[0]) {
                if ($inNamespace) {
                    $output .= "}\n";
                }
                $output .= $token[1];

                // namespace name and whitespaces
                while (($t = $tokens[++$i]) && is_array($t) && in_array($t[0], array(T_WHITESPACE, T_NS_SEPARATOR, T_STRING))) {
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
}
