<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;
use Propel\Runtime\Propel;

/**
 * Runtime configuration converter
 * From array to PHP string
 */
class ArrayToPhpConverter
{
    /**
     * Create a PHP configuration from an array
     *
     * @param array $c The array configuration
     *
     * @return string
     */
    public static function convert($c)
    {
        $runtimeVersion = Propel::VERSION;

        $conf = '';
        $conf .= "\$configuration = \\Propel\\Runtime\\Configuration::getCurrentConfigurationOrCreate();

\$configuration->checkVersion('{$runtimeVersion}');";
        // set datasources
        if (isset($c['connections'])) {
            foreach ($c['connections'] as $name => $params) {
                if (!is_array($params)) {
                    continue;
                }

                // set adapters
                if (isset($params['adapter'])) {
                    $conf .= "
\$configuration->setAdapterClass('{$name}', '{$params['adapter']}');";
                }

                // set connection settings
                if (isset($params['slaves'])) {
                    $conf .= "
\$manager = new \\Propel\\Runtime\\Connection\\ConnectionManagerMasterSlave(\$configuration->getAdapter('{$name}'));
\$manager->setReadConfiguration(" . var_export($params['slaves'], true). ");";
                } elseif (isset($params['dsn'])) {
                    $conf .= "
\$manager = new \\Propel\\Runtime\\Connection\\ConnectionManagerSingle(\$configuration->getAdapter('{$name}'));";
                } else {
                    continue;
                }

                if (isset($params['dsn'])) {
                    $masterConfigurationSetter = isset($params['slaves']) ? 'setWriteConfiguration' : 'setConfiguration';
                    $connection = $params;
                    unset($connection['adapter']);
                    unset($connection['slaves']);
                    $conf .= "
\$manager->{$masterConfigurationSetter}(". var_export($connection, true) . ");";
                }

                $conf .= "
\$manager->setName('{$name}');
\$configuration->setConnectionManager('{$name}', \$manager);";
            }

            // set default datasource
            if (isset($c['defaultConnection'])) {
                $defaultDatasource = $c['defaultConnection'];
            } elseif (isset($c['connections']) && is_array($c['connections'])) {
                // fallback to the first datasource
                $datasourceNames = array_keys($c['connections']);
                $defaultDatasource = $datasourceNames[0];
            }

            $conf .= "
\$configuration->setDefaultDatasource('{$defaultDatasource}');";
        }

        // set profiler
        if (isset($c['profiler'])) {
            $profilerConf = $c['profiler'];
            if (isset($profilerConf['classname'])) {
                $conf .= "
\$configuration->setProfilerClass('{$profilerConf['classname']}');";
                unset($profilerConf['classname']);
            }

            if ($profilerConf) {
                $conf .= "
\$configuration->setProfilerConfiguration(" . var_export($profilerConf, true) . ");";
            }
            unset($c['profiler']);
        }

        // set logger
        if (isset($c['log']) && count($c['log']) > 0) {
            foreach ($c['log'] as $key => $logger) {
                $conf .= "
\$configuration->setLoggerConfiguration('{$key}', " . var_export($logger, true) . ");";
            }
            unset($c['log']);
        }

        // register all known entity classes

        if (isset($c['databaseToEntities'])) {
            foreach ($c['databaseToEntities'] as $database => $entityClasses) {
                $entities = var_export($entityClasses, true);
                $conf .= "
\$configuration->registerEntity('$database', $entities);";
            }
        }

        $conf .= '
return $configuration;';
        return preg_replace('/[ \t]*$/m', '', $conf);
    }
}
