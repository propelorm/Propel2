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
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Table;
use \Propel\Runtime\Connection\ConnectionInterface;
use Propel\Generator\Util\BehaviorLocator;

class QuickGeneratorConfig extends ConfigurationManager implements GeneratorConfigInterface
{
    /**
     * @var BehaviorLocator
     */
    protected $behaviorLocator = null;

    public function __construct($extraConf = array())
    {
        if (null === $extraConf) {
            $extraConf = array();
        }

        //Creates a GeneratorConfig based on Propel default values plus the following
        $configs = array(
           'propel' => array(
               'database' => array(
                   'connections' => array(
                       'default' => array(
                           'adapter' => 'sqlite',
                           'classname' => 'Propel\Runtime\Connection\DebugPDO',
                           'dsn' => 'sqlite::memory:',
                           'user' => '',
                           'password' => ''
                       )
                   )
               ),
               'runtime' => array(
                   'defaultConnection' => 'default',
                   'connections' => array('default')
               ),
               'generator' => array(
                   'defaultConnection' => 'default',
                   'connections' => array('default')
               )
           )
        );

        $configs = array_replace_recursive($configs, $extraConf);
        $this->process($configs);
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
        $class = $this->getConfigProperty('generator.objectModel.builders.' . $type);

        if (null === $class) {
            throw new InvalidArgumentException("Invalid data model builder type `$type`");
        }

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
     * {@inheritdoc}
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, $database = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null, $database = null)
    {
        return null;
    }

    public function getBehaviorLocator()
    {
        if (!$this->behaviorLocator) {
            $this->behaviorLocator = new BehaviorLocator($this);
        }

        return $this->behaviorLocator;
    }
}
