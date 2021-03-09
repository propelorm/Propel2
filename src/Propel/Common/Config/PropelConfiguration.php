<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config;

use InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class PropelConfiguration
 *
 * This class performs validation of configuration array and assign default values
 */
class PropelConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $isBeforeSymfony5 = method_exists(TreeBuilder::class, 'root');

        if ($isBeforeSymfony5) {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('propel');
        } else {
            $treeBuilder = new TreeBuilder('propel');
            $rootNode = $treeBuilder->getRootNode();
        }

        $this->addGeneralSection($rootNode);
        $this->addExcludeTablesSection($rootNode);
        $this->addPathsSection($rootNode);
        $this->addDatabaseSection($rootNode);
        $this->addMigrationsSection($rootNode);
        $this->addReverseSection($rootNode);
        $this->addRuntimeSection($rootNode);
        $this->addGeneratorSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addGeneralSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('general')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('project')->defaultValue('')->end()
                        ->scalarNode('version')->defaultValue('2.0.0-dev')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addPathsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('paths')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('projectDir')->defaultValue(getcwd())->end()
                        ->scalarNode('schemaDir')->defaultValue(getcwd())->end()
                        ->scalarNode('outputDir')->defaultValue(getcwd())->end()
                        ->scalarNode('phpDir')->defaultValue(getcwd() . '/generated-classes')->end()
                        ->scalarNode('phpConfDir')->defaultValue(getcwd() . '/generated-conf')->end()
                        ->scalarNode('sqlDir')->defaultValue(getcwd() . '/generated-sql')->end()
                        ->scalarNode('migrationDir')->defaultValue(getcwd() . '/generated-migrations')->end()
                        ->scalarNode('composerDir')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addDatabaseSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('database')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('connections')
                            ->isRequired()
                            ->validate()
                            ->always()
                                ->then(function ($connections) {
                                    foreach ($connections as $name => $connection) {
                                        if (strpos($name, '.') !== false) {
                                            throw new InvalidArgumentException('Dots are not allowed in connection names');
                                        }
                                    }

                                    return $connections;
                                })
                            ->end()
                            ->requiresAtLeastOneElement()
                            ->normalizeKeys(false)
                            ->prototype('array')
                            ->fixXmlConfig('slave')
                            ->fixXmlConfig('model_path')
                                ->children()
                                    ->scalarNode('classname')->defaultValue('\Propel\Runtime\Connection\ConnectionWrapper')->end()
                                    ->enumNode('adapter')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->values(['mysql', 'pgsql', 'sqlite', 'mssql', 'sqlsrv', 'oracle'])
                                    ->end()
                                    ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('user')->isRequired()->end()
                                    ->scalarNode('password')->isRequired()->treatNullLike('')->end()
                                    ->arrayNode('options')
                                        ->children()
                                            ->booleanNode('ATTR_PERSISTENT')->defaultFalse()->end()
                                            ->scalarNode('MYSQL_ATTR_SSL_CA')->end()
                                            ->scalarNode('MYSQL_ATTR_SSL_CERT')->end()
                                            ->scalarNode('MYSQL_ATTR_SSL_KEY')->end()
                                            ->booleanNode('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')->end()
                                            ->scalarNode('MYSQL_ATTR_MAX_BUFFER_SIZE')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('attributes')
                                        ->children()
                                            ->booleanNode('ATTR_EMULATE_PREPARES')->defaultFalse()->end()
                                            ->integerNode('ATTR_TIMEOUT')->min(1)->defaultValue(30)->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('settings')
                                    ->fixXmlConfig('query', 'queries')
                                        ->children()
                                            ->scalarNode('charset')->defaultValue('utf8')->end()
                                            ->arrayNode('queries')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('model_paths')
                                        ->defaultValue(['src', 'vendor'])
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->arrayNode('slaves')
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('dsn')->end()
                                                ->scalarNode('user')->end()
                                                ->scalarNode('password')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('adapters')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('mysql')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('tableType')->defaultValue('InnoDB')->treatNullLike('InnoDB')->end()
                                        ->scalarNode('tableEngineKeyword')->defaultValue('ENGINE')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('sqlite')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('foreignKey')->end()
                                        ->scalarNode('tableAlteringWorkaround')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('oracle')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('autoincrementSequencePattern')->defaultValue('${table}_SEQ')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end() //adapters
                    ->end()
                ->end() //database
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addMigrationsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('migrations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('samePhpName')->defaultFalse()->end()
                        ->booleanNode('addVendorInfo')->defaultFalse()->end()
                        ->scalarNode('tableName')->defaultValue('propel_migration')->end()
                        ->scalarNode('parserClass')->defaultNull()->end()
                    ->end()
                ->end() //migrations
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addReverseSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('reverse')
                    ->children()
                        ->scalarNode('connection')->end()
                        ->scalarNode('parserClass')->end()
                    ->end()
                ->end() //reverse
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addExcludeTablesSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('exclude_tables')
                    ->prototype('scalar')->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addRuntimeSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('runtime')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('connection')
                    ->children()
                        ->scalarNode('defaultConnection')->end()
                        ->arrayNode('connections')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('log')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->end()
                                    ->scalarNode('facility')->end()
                                    ->scalarNode('ident')->end()
                                    ->scalarNode('path')->end()
                                    ->enumNode('level')->values([100, 200, 250, 300, 400, 500, 550, 600])->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('profiler')
                            ->children()
                                ->scalarNode('classname')->defaultValue('\Propel\Runtime\Util\Profiler')->end()
                                ->floatNode('slowTreshold')->defaultValue(0.1)->end()
                                ->arrayNode('details')
                                    ->children()
                                        ->arrayNode('time')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('name')->defaultValue('Time')->end()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('mem')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('name')->defaultValue('Memory')->end()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memDelta')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('name')->defaultValue('Memory Delta')->end()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('memPeak')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('name')->defaultValue('Memory Peak')->end()
                                                ->integerNode('precision')->min(0)->defaultValue(3)->end()
                                                ->integerNode('pad')->min(0)->defaultValue(8)->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('innerGlue')->defaultValue(': ')->end()
                                ->scalarNode('outerGlue')->defaultValue(' | ')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() //runtime
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     *
     * @return void
     */
    protected function addGeneratorSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('generator')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('connection')
                    ->children()
                        ->scalarNode('defaultConnection')->end()
                        ->scalarNode('tablePrefix')->end()
                        ->scalarNode('platformClass')->defaultNull()->end()
                        ->scalarNode('targetPackage')->end()
                        ->booleanNode('packageObjectModel')->defaultTrue()->end()
                        ->booleanNode('namespaceAutoPackage')->defaultTrue()->end()
                        ->booleanNode('recursive')->defaultFalse()->end()
                        ->arrayNode('connections')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('schema')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('basename')->defaultValue('schema')->end()
                                ->booleanNode('autoPrefix')->defaultFalse()->end()
                                ->booleanNode('autoPackage')->defaultFalse()->end()
                                ->booleanNode('autoNamespace')->defaultFalse()->end()
                                ->booleanNode('transform')->defaultFalse()->end()
                            ->end()
                        ->end() //schema
                        ->arrayNode('dateTime')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('useDateTimeClass')->defaultTrue()->end()
                                ->scalarNode('dateTimeClass')->defaultValue('DateTime')->end()
                                ->scalarNode('defaultTimeStampFormat')->end()
                                ->scalarNode('defaultTimeFormat')->end()
                                ->scalarNode('defaultDateFormat')->end()
                            ->end()
                        ->end()
                        ->arrayNode('objectModel')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('addGenericAccessors')->defaultTrue()->end()
                                ->booleanNode('addGenericMutators')->defaultTrue()->end()
                                ->booleanNode('emulateForeignKeyConstraints')->defaultFalse()->end()
                                ->booleanNode('addClassLevelComment')->defaultTrue()->end()
                                ->scalarNode('defaultKeyType')->defaultValue('phpName')->end()
                                ->booleanNode('addSaveMethod')->defaultTrue()->end()
                                ->scalarNode('namespaceMap')->defaultValue('Map')->end()
                                ->booleanNode('addTimeStamp')->defaultFalse()->end()
                                ->booleanNode('addHooks')->defaultTrue()->end()
                                ->scalarNode('classPrefix')->defaultNull()->end()
                                ->booleanNode('useLeftJoinsInDoJoinMethods')->defaultTrue()->end()
                                ->scalarNode('pluralizerClass')->defaultValue('\Propel\Common\Pluralizer\StandardEnglishPluralizer')->end()
                                ->scalarNode('entityNotFoundExceptionClass')->defaultValue('\Propel\Runtime\Exception\EntityNotFoundException')->end()
                                ->arrayNode('builders')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('object')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ObjectBuilder')->end()
                                        ->scalarNode('objectstub')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ExtensionObjectBuilder')->end()
                                        ->scalarNode('objectmultiextend')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\MultiExtendObjectBuilder')->end()
                                        ->scalarNode('query')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\QueryBuilder')->end()
                                        ->scalarNode('querystub')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ExtensionQueryBuilder')->end()
                                        ->scalarNode('queryinheritance')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\QueryInheritanceBuilder')->end()
                                        ->scalarNode('queryinheritancestub')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\ExtensionQueryInheritanceBuilder')->end()
                                        ->scalarNode('tablemap')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\TableMapBuilder')->end()
                                        ->scalarNode('interface')->cannotBeEmpty()->defaultValue('\Propel\Generator\Builder\Om\InterfaceBuilder')->end()
                                        ->scalarNode('datasql')->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end() //objectModel
                    ->end()
                ->end() //generator
            ->end();
    }
}
