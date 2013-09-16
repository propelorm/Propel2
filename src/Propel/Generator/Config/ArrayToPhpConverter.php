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
        $conf .= "\$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();";
        $conf .= "
\$serviceContainer->checkVersion('{$runtimeVersion}');";
        // set datasources
        if (isset($c['datasources'])) {
            foreach ($c['datasources'] as $name => $params) {
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
\$manager->setReadConfiguration(" . var_export($params['slaves'], true). ");";
                } elseif (isset($params['connection'])) {
                    $conf .= "
\$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();";
                } else {
                    continue;
                }

                if (isset($params['connection'])) {
                    $masterConfigurationSetter = isset($params['slaves']) ? 'setWriteConfiguration' : 'setConfiguration';
                    $conf .= "
\$manager->{$masterConfigurationSetter}(". var_export($params['connection'], true) . ");";
                }

                $conf .= "
\$manager->setName('{$name}');
\$serviceContainer->setConnectionManager('{$name}', \$manager);";
            }

            // set default datasource
            if (isset($c['datasources']['default'])) {
                $defaultDatasource = $c['datasources']['default'];
            } elseif (isset($c['datasources']) && is_array($c['datasources'])) {
                // fallback to the first datasource
                $datasourceNames = array_keys($c['datasources']);
                $defaultDatasource = $datasourceNames[0];
            }

            $conf .= "
\$serviceContainer->setDefaultDatasource('{$defaultDatasource}');";
        }

        // set profiler
        if (isset($c['profiler'])) {
            $profilerConf = $c['profiler'];
            if (isset($profilerConf['class'])) {
                $conf .= "
\$serviceContainer->setProfilerClass('{$profilerConf['class']}');";
                unset($profilerConf['class']);
            }
            if ($profilerConf) {
                $conf .= "
\$serviceContainer->setProfilerConfiguration(" . var_export($profilerConf, true) . ");";
            }
            unset($c['profiler']);
        }

        // set logger
        if (isset($c['log']) && isset($c['log']['logger'])) {
            $loggerConfiguration = $c['log']['logger'];
            // is it a single logger or a list of loggers?
            if (isset($loggerConfiguration[0])) {
                foreach ($loggerConfiguration as $singleLoggerConfiguration) {
                    $conf .= self::getLoggerCode($singleLoggerConfiguration);
                }
            } else {
                $conf .= self::getLoggerCode($loggerConfiguration);
            }
            unset($c['log']);
        }

        return preg_replace('/[ \t]*$/m', '', $conf);
    }

    protected static function getLoggerCode($conf)
    {
        $name = 'default';
        if (isset($conf['name'])) {
            $name = $conf['name'];
            unset($conf['name']);
        }

        return "
\$serviceContainer->setLoggerConfiguration('{$name}', " . var_export($conf, true) . ");";
    }
}
