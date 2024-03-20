<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Config;

/**
 * Runtime configuration converter
 * From array to PHP string
 */
class ArrayToPhpConverter
{
    /**
     * Create a PHP configuration from an array
     *
     * @param array<string, mixed> $c The array configuration
     *
     * @return string
     */
    public static function convert(array $c): string
    {
        $conf = [];
        // set datasources
        if (isset($c['connections'])) {
            foreach ($c['connections'] as $name => $params) {
                if (!is_array($params)) {
                    continue;
                }

                // set adapters
                if (isset($params['adapter'])) {
                    $conf[] = "\$serviceContainer->setAdapterClass('{$name}', '{$params['adapter']}');";
                }

                // set connection settings
                if (isset($params['slaves'])) {
                    $conf[] = "\$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave('{$name}');";
                    $conf[] = '$manager->setReadConfiguration(' . var_export($params['slaves'], true) . ');';
                } elseif (isset($params['dsn'])) {
                    $conf[] = "\$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle('{$name}');";
                } else {
                    continue;
                }

                if (isset($params['dsn'])) {
                    $masterConfigurationSetter = isset($params['slaves']) ? 'setWriteConfiguration' : 'setConfiguration';
                    $connection = $params;
                    unset($connection['adapter']);
                    unset($connection['slaves']);
                    $conf[] = "\$manager->{$masterConfigurationSetter}(" . var_export($connection, true) . ');';
                }

                $conf[] = '$serviceContainer->setConnectionManager($manager);';
            }

            // set default datasource
            if (isset($c['defaultConnection'])) {
                $defaultDatasource = $c['defaultConnection'];
            } elseif (is_array($c['connections'])) {
                // fallback to the first datasource
                $datasourceNames = array_keys($c['connections']);
                $defaultDatasource = $datasourceNames[0];
            }

            $conf[] = "\$serviceContainer->setDefaultDatasource('{$defaultDatasource}');";
        }

        // set profiler
        if (isset($c['profiler'])) {
            $profilerConf = $c['profiler'];
            if (isset($profilerConf['classname'])) {
                $conf[] = "\$serviceContainer->setProfilerClass('{$profilerConf['classname']}');";
                unset($profilerConf['classname']);
            }

            if ($profilerConf) {
                $conf[] = '$serviceContainer->setProfilerConfiguration(' . var_export($profilerConf, true) . ');';
            }
            unset($c['profiler']);
        }

        // set logger
        if (isset($c['log']) && count($c['log']) > 0) {
            foreach ($c['log'] as $key => $logger) {
                $conf[] = "\$serviceContainer->setLoggerConfiguration('{$key}', " . var_export($logger, true) . ');';
            }
            unset($c['log']);
        }

        $conf = implode(PHP_EOL, $conf);

        return preg_replace('/[ \t]*$/m', '', $conf);
    }
}
