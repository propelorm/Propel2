<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license         MIT License
 */

namespace Propel\Generator\Config;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Builder\Util\DefaultEnglishPluralizer;
use Propel\Generator\Model\Table;


use \PDO;
use \Exception;

/**
 *
 * @package         propel.generator.config
 */
class QuickGeneratorConfig implements GeneratorConfigInterface
{
    protected $builders = array(
        'peer'                  => '\Propel\Generator\Builder\Om\PHP5PeerBuilder',
        'object'                => '\Propel\Generator\Builder\Om\PHP5ObjectBuilder',
        'objectstub'            => '\Propel\Generator\Builder\Om\PHP5ExtensionObjectBuilder',
        'peerstub'              => '\Propel\Generator\Builder\Om\PHP5ExtensionPeerBuilder',
        'objectmultiextend'     => '\Propel\Generator\Builder\Om\PHP5MultiExtendObjectBuilder',
        'tablemap'              => '\Propel\Generator\Builder\Om\PHP5TableMapBuilder',
        'query'                 => '\Propel\Generator\Builder\Om\QueryBuilder',
        'querystub'             => '\Propel\Generator\Builder\Om\ExtensionQueryBuilder',
        'queryinheritance'      => '\Propel\Generator\Builder\Om\QueryInheritanceBuilder',
        'queryinheritancestub'  => '\Propel\Generator\Builder\Om\ExtensionQueryInheritanceBuilder',
        'interface'             => '\Propel\Generator\Builder\Om\PHP5InterfaceBuilder',
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
        if (($lines = @file($filepath)) === false) {
            throw new Exception("Unable to parse contents of $filepath");
        }
        foreach($lines as $line) {
                $line = trim($line);
                if ($line == "" || $line{0} == '#' || $line{0} == ';') continue;
                $pos = strpos($line, '=');
                $property = trim(substr($line, 0, $pos));
                $value = trim(substr($line, $pos + 1));
                if ($value === "true") {
                    $value = true;
                } elseif ($value === "false") {
                    $value = false;
                }
                $properties[$property] = $value;
        }

        return $properties;
    }

    /**
     * Gets a configured data model builder class for specified table and based on type.
     *
     * @param             Table $table
     * @param             string $type The type of builder ('ddl', 'sql', etc.)
     * @return         DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, $type)
    {
        $class = $this->builders[$type];
        $builder = new $class($table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
    * Gets a configured Pluralizer class.
    *
    * @return     Pluralizer
    */
    public function getConfiguredPluralizer()
    {
        return new DefaultEnglishPluralizer();
    }

    /**
     * Parses the passed-in properties, renaming and saving eligible properties in this object.
     *
     * Renames the propel.xxx properties to just xxx and renames any xxx.yyy properties
     * to xxxYyy as PHP doesn't like the xxx.yyy syntax.
     *
     * @param             mixed $props Array or Iterator
     */
    public function setBuildProperties($props)
    {
        $this->buildProperties = array();

        $renamedPropelProps = array();
        foreach ($props as $key => $propValue) {
            if (strpos($key, "propel.") === 0) {
                $newKey = substr($key, strlen("propel."));
                $j = strpos($newKey, '.');
                while ($j !== false) {
                    $newKey =     substr($newKey, 0, $j) . ucfirst(substr($newKey, $j + 1));
                    $j = strpos($newKey, '.');
                }
                $this->setBuildProperty($newKey, $propValue);
            }
        }
    }

    /**
     * Gets a specific propel (renamed) property from the build.
     *
     * @param             string $name
     * @return         mixed
     */
    public function getBuildProperty($name)
    {
        return isset($this->buildProperties[$name]) ? $this->buildProperties[$name] : null;
    }

    /**
     * Sets a specific propel (renamed) property from the build.
     *
     * @param      string $name
     * @param      mixed $value
     */
    public function setBuildProperty($name, $value)
    {
        $this->buildProperties[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguredPlatform(PDO $con = null, $database = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguredBehavior($name)
    {
        $propname = 'behavior' . ucfirst(strtolower($name)) . 'Class';

        if ($class = $this->getBuildProperty($propname)) {
            return $class;
        }

        return null;
    }
}
