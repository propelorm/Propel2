<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        $conf .= "\$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();";
        $conf .= "
\$serviceContainer->checkVersion('{$runtimeVersion}');";
        // set datasources
        if (isset($c['connections'])) {
            foreach ($c['connections'] as $name => $params) {
                if (!is_array($params)) {
                    continue;
                }

                // set adapters
                if (isset($params['adapter'])) {
                    $conf .= "
\$serviceContainer->setAdapterClass('{$name}', '{$params['adapter']}');";
                }

                // set connection settings
                if (isset($params['slaves'])) {
                    $conf .= "
\$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave();
\$manager->setReadConfiguration(" . var_export($params['slaves'], true) . ');';
                } elseif (isset($params['dsn'])) {
                    $conf .= "
\$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();";
                } else {
                    continue;
                }

                if (isset($params['dsn'])) {
                    $masterConfigurationSetter = isset($params['slaves']) ? 'setWriteConfiguration' : 'setConfiguration';
                    $connection = $params;
                    unset($connection['adapter']);
                    unset($connection['slaves']);
                    $conf .= "
\$manager->{$masterConfigurationSetter}(" . var_export($connection, true) . ');';
                }

                $conf .= "
\$manager->setName('{$name}');
\$serviceContainer->setConnectionManager('{$name}', \$manager);";
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
\$serviceContainer->setDefaultDatasource('{$defaultDatasource}');";
        }

        // set profiler
        if (isset($c['profiler'])) {
            $profilerConf = $c['profiler'];
            if (isset($profilerConf['classname'])) {
                $conf .= "
\$serviceContainer->setProfilerClass('{$profilerConf['classname']}');";
                unset($profilerConf['classname']);
            }

            if ($profilerConf) {
                $conf .= "
\$serviceContainer->setProfilerConfiguration(" . var_export($profilerConf, true) . ');';
            }
            unset($c['profiler']);
        }

        // set logger
        if (isset($c['log']) && count($c['log']) > 0) {
            foreach ($c['log'] as $key => $logger) {
                $conf .= "
\$serviceContainer->setLoggerConfiguration('{$key}', " . var_export($logger, true) . ');';
            }
            unset($c['log']);
        }

        return preg_replace('/[ \t]*$/m', '', $conf);
    }
}
