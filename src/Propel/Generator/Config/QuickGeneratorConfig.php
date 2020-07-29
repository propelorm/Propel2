<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Config;

use Propel\Common\Config\ConfigurationManager;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Table;
use Propel\Generator\Util\BehaviorLocator;
use Propel\Runtime\Connection\ConnectionInterface;

class QuickGeneratorConfig extends ConfigurationManager implements GeneratorConfigInterface
{
    /**
     * @var \Propel\Generator\Util\BehaviorLocator|null
     */
    protected $behaviorLocator;

    /**
     * @param array|null $extraConf
     */
    public function __construct($extraConf = [])
    {
        if ($extraConf === null) {
            $extraConf = [];
        }

        //Creates a GeneratorConfig based on Propel default values plus the following
        $configs = [
           'propel' => [
               'database' => [
                   'connections' => [
                       'default' => [
                           'adapter' => 'sqlite',
                           'classname' => 'Propel\Runtime\Connection\DebugPDO',
                           'dsn' => 'sqlite::memory:',
                           'user' => '',
                           'password' => '',
                       ],
                   ],
               ],
               'runtime' => [
                   'defaultConnection' => 'default',
                   'connections' => ['default'],
               ],
               'generator' => [
                   'defaultConnection' => 'default',
                   'connections' => ['default'],
               ],
           ],
        ];

        $configs = array_replace_recursive($configs, $extraConf);
        $this->process($configs);
    }

    /**
     * Gets a configured data model builder class for specified table and based
     * on type ('ddl', 'sql', etc.).
     *
     * @param \Propel\Generator\Model\Table $table
     * @param string $type
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return \Propel\Generator\Builder\DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, $type)
    {
        $class = $this->getConfigProperty('generator.objectModel.builders.' . $type);

        if ($class === null) {
            throw new InvalidArgumentException("Invalid data model builder type `$type`");
        }

        $builder = new $class($table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Returns a configured Pluralizer class.
     *
     * @return \Propel\Common\Pluralizer\PluralizerInterface
     */
    public function getConfiguredPluralizer()
    {
        return new StandardEnglishPluralizer();
    }

    /**
     * @inheritDoc
     */
    public function getConfiguredPlatform(?ConnectionInterface $con = null, $database = null)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getConfiguredSchemaParser(?ConnectionInterface $con = null, $database = null)
    {
        return null;
    }

    /**
     * @return \Propel\Generator\Util\BehaviorLocator
     */
    public function getBehaviorLocator()
    {
        if (!$this->behaviorLocator) {
            $this->behaviorLocator = new BehaviorLocator($this);
        }

        return $this->behaviorLocator;
    }
}
