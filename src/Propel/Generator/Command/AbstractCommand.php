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
use Propel\Generator\Exception\RuntimeException;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    const DEFAULT_INPUT_DIRECTORY   = '.';
    const DEFAULT_PLATFORM          = 'MysqlPlatform';

    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('platform',  null, InputOption::VALUE_REQUIRED,  'The platform', self::DEFAULT_PLATFORM)
            ->addOption('input-dir', null, InputOption::VALUE_REQUIRED,  'The input directory', self::DEFAULT_INPUT_DIRECTORY)
        ;
    }

    /**
     * Returns a new `GeneratorConfig` object with your `$properties` merged with
     * the build.properties in the `input-dir` folder.
     *
     * @param array $properties
     * @param       $input
     *
     * @return GeneratorConfig
     */
    protected function getGeneratorConfig(array $properties, InputInterface $input = null)
    {
        $options = $properties;
        if ($input && $input->hasOption('input-dir')) {
            $options = array_merge(
                $properties,
                $this->getBuildProperties($input->getOption('input-dir') . '/build.properties')
            );
        }

        return new GeneratorConfig($options);
    }

    protected function getBuildProperties($file)
    {
        $properties = array();

        if (file_exists($file)) {
            if (false === $lines = @file($file)) {
                throw new RuntimeException(sprintf('Unable to parse contents of "%s".', $file));
            }

            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line) || in_array($line[0], array('#', ';'))) {
                    continue;
                }

                $pos = strpos($line, '=');
                $properties[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
            }
        }

        return $properties;
    }

    /**
     * Find every schema files.
     *
     * @param  string|array $directory Path to the input directory
     * @return array        List of schema files
     */
    protected function getSchemas($directory)
    {
        $finder = new Finder();

        return iterator_to_array($finder
            ->name('*schema.xml')
            ->in($directory)
            ->depth(0)
            ->files()
        );
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
}
