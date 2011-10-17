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
use Propel\Runtime\Connection\PropelPDO;

use \PDO;

class PropelQuickBuilder
{
    protected $schema, $platform, $config, $database;

    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * Setter for the platform property
     *
     * @param PropelPlatformInterface $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * Getter for the platform property
     *
     * @return PropelPlatformInterface
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
            $adapter = new \Propel\Runtime\Adapter\DBSQLite();
        }
        $con = new PropelPDO($dsn, $user, $pass);
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
            $appData = $xtad->parseString($this->schema);
            $this->database = $appData->getDatabase(); // does final initialization
        }
        return $this->database;
    }

    public function buildSQL(PDO $con)
    {
        $statements = PropelSQLParser::parseString($this->getSQL());
        foreach ($statements as $statement) {
            if (strpos($statement, 'DROP') === 0) {
                // drop statements cause errors since the table doesn't exist
                continue;
            }
            $stmt = $con->prepare($statement);
            if ($stmt instanceof PDOStatement) {
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
            $script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
        }

        if ($col = $table->getChildrenColumn()) {
            if ($col->isEnumeratedClasses()) {
                foreach ($col->getChildren() as $child) {
                    if ($child->getAncestor()) {
                        $builder = $this->getConfig()->getConfiguredBuilder('queryinheritance', $target);
                        $builder->setChild($child);
                        $script .= $builder->build();
                    }
                    foreach (array('objectmultiextend', 'queryinheritancestub') as $target) {
                        $builder = $this->getConfig()->getConfiguredBuilder($table, $target);
                        $builder->setChild($child);
                        $script .= $builder->build();
                    }
                }
            }
        }

        if ($table->getInterface()) {
            $script .= $this->getConfig()->getConfiguredBuilder('interface', $target)->build();
        }

        if ($table->treeMode()) {
            switch($table->treeMode()) {
            case 'NestedSet':
                foreach (array('nestedsetpeer', 'nestedset') as $target) {
                    $script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
                }
                break;
            case 'MaterializedPath':
                foreach (array('nodepeer', 'node') as $target) {
                    $script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
                }
                foreach (array('nodepeerstub', 'nodestub') as $target) {
                    $script .= $this->getConfig()->getConfiguredBuilder($table, $target)->build();
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
                $script .= $builder->build();
            }
        }

        // remove extra <?php
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
}
