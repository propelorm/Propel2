<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\RuntimeException;
use Propel\Generator\Model\Table;
use \Propel\Runtime\Connection\ConnectionInterface;

class QuickGeneratorConfig implements GeneratorConfigInterface
{
    protected $builders = array(
        'object'                => '\Propel\Generator\Builder\Om\ObjectBuilder',
        'objectstub'            => '\Propel\Generator\Builder\Om\ExtensionObjectBuilder',
        'objectmultiextend'     => '\Propel\Generator\Builder\Om\MultiExtendObjectBuilder',
        'tablemap'              => '\Propel\Generator\Builder\Om\TableMapBuilder',
        'query'                 => '\Propel\Generator\Builder\Om\QueryBuilder',
        'querystub'             => '\Propel\Generator\Builder\Om\ExtensionQueryBuilder',
        'queryinheritance'      => '\Propel\Generator\Builder\Om\QueryInheritanceBuilder',
        'queryinheritancestub'  => '\Propel\Generator\Builder\Om\ExtensionQueryInheritanceBuilder',
        'interface'             => '\Propel\Generator\Builder\Om\InterfaceBuilder',
    );

    protected $buildProperties = array();

    public function __construct()
    {
        $this->setBuildProperties($this->parsePseudoIniFile(__DIR__ . '/../../../../tools/generator/default.properties'));
    }

    /**
     * Why would Phing use ini while it so fun to invent a new format? (sic)
     * parse_ini_file() doesn't work for Phing property files
     */
    protected function parsePseudoIniFile($filepath)
    {
        $properties = array();
        if (false === ($lines = @file($filepath))) {
            throw new RuntimeException(sprintf('Unable to parse contents of %s.', $filepath));
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || '#' ===  $line{0} || ';' === $line{0}) {
                continue;
            }

            $pos = strpos($line, '=');
            $property = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ('true' === $value) {
                $value = true;
            } elseif ('false' === $value) {
                $value = false;
            }

            $properties[$property] = $value;
        }

        return $properties;
    }

    /**
     * Gets a configured data model builder class for specified table and based
     * on type ('ddl', 'sql', etc.).
     *
     * @param  Table            $table
     * @param  string           $type
     * @return DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, $type)
    {
        $class = $this->builders[$type];
        $builder = new $class($table);
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
        return new StandardEnglishPluralizer();
    }

    /**
     * Parses the passed-in properties, renaming and saving eligible properties
     * in this object.
     *
     * Renames the propel.xxx properties to just xxx and renames any xxx.yyy
     * properties to xxxYyy as PHP doesn't like the xxx.yyy syntax.
     *
     * @param array|\Traversable $props
     */
    public function setBuildProperties($props)
    {
        $this->buildProperties = array();

        foreach ($props as $key => $propValue) {
            if (strpos($key, "propel.") === 0) {
                $newKey = substr($key, strlen("propel."));
                $j = strpos($newKey, '.');
                while (false !== $j) {
                    $newKey = substr($newKey, 0, $j) . ucfirst(substr($newKey, $j + 1));
                    $j = strpos($newKey, '.');
                }
                $this->setBuildProperty($newKey, $propValue);
            }
        }
    }

    /**
     * Returns a specific propel (renamed) property from the build.
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
     * {@inheritdoc}
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, $database = null)
    {
        return null;
    }
}
