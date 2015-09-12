<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Propel\Generator\Config\GeneratorConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    const DEFAULT_CONFIG_DIRECTORY   = '.';

    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform to use. Define a full qualified class name or mysql|pgsql|sqlite|mssql|oracle.')
            ->addOption('config-dir', null, InputOption::VALUE_REQUIRED,  'The directory where the configuration file is placed.', self::DEFAULT_CONFIG_DIRECTORY)
            ->addOption('recursive', null, InputOption::VALUE_NONE, 'Search recursive for *schema.xml inside the input directory')
        ;
    }

    /**
     * Returns a new `GeneratorConfig` object with your `$properties` merged with
     * the configuration properties in the `config-dir` folder.
     *
     * @param array $properties Properties to add to the configuration. They usually come from command line.
     * @param       $input
     *
     * @return GeneratorConfig
     */
    protected function getGeneratorConfig(array $properties = null, InputInterface $input = null)
    {
        if (null === $input) {
            return new GeneratorConfig(null, $properties);
        }

        if ($this->hasInputOption('platform', $input)) {
            $properties['propel']['generator']['platformClass'] = $input->getOption('platform');
        }

        return new GeneratorConfig($input->getOption('config-dir'), $properties);
    }

    /**
     * Find every schema files.
     *
     * @param string|array $directory Path to the input directory
     * @param bool         $recursive Search for file inside the input directory and all subdirectories
     *
     * @return array List of schema files
     */
    protected function getSchemas($directory, $recursive = false)
    {
        $finder = new Finder();
        $finder
            ->name('*schema.xml')
            ->sortByName()
            ->in($directory);
        if (!$recursive) {
            $finder->depth(0);
        }

        return iterator_to_array($finder->files());
    }

    /**
     * Returns a Filesystem instance.
     *
     * @return Filesystem
     */
    protected function getFilesystem()
    {
        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }

    protected function createDirectory($directory)
    {
        $filesystem = $this->getFilesystem();

        try {
            $filesystem->mkdir($directory);
        } catch (IOException $e) {
            throw new \RuntimeException(sprintf('Unable to write the "%s" directory', $directory), 0, $e);
        }
    }

    /**
     * Parse a connection string and return an array with name, dsn and extra informations
     *
     * @parama string $connection The connection string
     *
     * @return array
     */
    protected function parseConnection($connection)
    {
        $pos  = strpos($connection, '=');
        $name = substr($connection, 0, $pos);
        $dsn  = substr($connection, $pos + 1, strlen($connection));

        $pos  = strpos($dsn, ':');
        $adapter = substr($dsn, 0, $pos);

        $extras = array();
        foreach (explode(';', $dsn) as $element) {
            $parts = preg_split('/=/', $element);

            if (2 === count($parts)) {
                $extras[strtolower($parts[0])] = $parts[1];
            }
        }
        $extras['adapter'] = $adapter;

        return array($name, $dsn, $extras);
    }

    /**
     * Parse a connection string and return an array of properties to pass to GeneratorConfig constructor.
     * The connection must be in the following format: 
     * `bookstore=mysql:host=127.0.0.1;dbname=test;user=root;password=foobar`
     * where "bookstore" is your propel database name (used in your schema.xml).
     *
     * @param string $connection The connection string
     * @param string $section The section where the connection must be registered in (generator, runtime...)
     *
     * @return array
     */
    protected function connectionToProperties($connection, $section = null)
    {        
        list($name, $dsn, $infos) = $this->parseConnection($connection);
        $config['propel']['database']['connections'][$name]['classname'] = '\Propel\Runtime\Connection\ConnectionWrapper';
        $config['propel']['database']['connections'][$name]['adapter'] = strtolower($infos['adapter']);
        $config['propel']['database']['connections'][$name]['dsn'] = $dsn;
        $config['propel']['database']['connections'][$name]['user'] = isset($infos['user']) && $infos['user'] ? $infos['user'] : null;
        $config['propel']['database']['connections'][$name]['password'] = isset($infos['password']) ? $infos['password'] : null;
        
        if (null === $section) {
            $section = 'generator';
        }
        
        if ('reverse' === $section) {
            $config['propel']['reverse']['connection'] = $name;
        } else {
            $config['propel'][$section]['connections'][] = $name;
        }

        return $config;
    }

    /**
     * Check if a given input option exists and it isn't null.
     *
     * @param string $option The name of the input option to check
     * @param \Symfony\Component\Console\Input\InputInterface $input object
     *
     * @return boolean
     */
    protected function hasInputOption($option, $input)
    {
        return $input->hasOption($option) && null !== $input->getOption($option);
    }
    
    /**
     * Check if a given input argument exists and it isn't null.
     *
     * @param string $argument The name of the input argument to check
     * @param \Symfony\Component\Console\Input\InputInterface $input object
     *
     * @return boolean
     */
    protected function hasInputArgument($argument, $input)
    {
        return $input->hasArgument($argument) && null !== $input->getArgument($argument);
    }
}
