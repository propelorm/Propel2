<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Config\GeneratorConfig;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var string
     */
    public const DEFAULT_CONFIG_DIRECTORY = '.';

    /**
     * @var int
     */
    public const CODE_SUCCESS = 0;

    /**
     * @var int
     */
    public const CODE_ERROR = 1;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem|null
     */
    protected $filesystem;

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption('platform', null, InputOption::VALUE_REQUIRED, 'The platform to use. Define a full qualified class name or mysql|pgsql|sqlite|mssql|oracle.')
            ->addOption('config-dir', null, InputOption::VALUE_REQUIRED, 'The directory where the configuration file is placed.', self::DEFAULT_CONFIG_DIRECTORY)
            ->addOption('recursive', null, InputOption::VALUE_NONE, 'Search recursive for *schema.xml inside the input directory');
    }

    /**
     * Returns a new `GeneratorConfig` object with your `$properties` merged with
     * the configuration properties in the `config-dir` folder.
     *
     * @param array|null $properties Properties to add to the configuration. They usually come from command line.
     * @param \Symfony\Component\Console\Input\InputInterface|null $input
     *
     * @return \Propel\Generator\Config\GeneratorConfig
     */
    protected function getGeneratorConfig(?array $properties = null, ?InputInterface $input = null): GeneratorConfig
    {
        if ($input === null) {
            return new GeneratorConfig(null, $properties);
        }

        if ($this->hasInputOption('platform', $input)) {
            $properties['propel']['generator']['platformClass'] = $input->getOption('platform');
        }

        if ($input->hasParameterOption('--recursive')) {
            $properties['propel']['generator']['recursive'] = $input->getOption('recursive');
        }

        return new GeneratorConfig($input->getOption('config-dir'), $properties);
    }

    /**
     * Find every schema files.
     *
     * @param array<string>|string $directory Path to the input directory
     * @param bool $recursive Search for file inside the input directory and all subdirectories
     *
     * @return array List of schema files
     */
    protected function getSchemas($directory, bool $recursive = false): array
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
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    protected function getFilesystem(): Filesystem
    {
        if ($this->filesystem === null) {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }

    /**
     * @param string $directory
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function createDirectory(string $directory): void
    {
        $filesystem = $this->getFilesystem();

        try {
            $filesystem->mkdir($directory);
        } catch (IOException $e) {
            throw new RuntimeException(sprintf('Unable to write the "%s" directory', $directory), 0, $e);
        }
    }

    /**
     * Parses a connection string and returns an array with name, DSN and extra information.
     *
     * @param string $connection The connection string
     *
     * @return array
     */
    protected function parseConnection(string $connection): array
    {
        $length = strpos($connection, '=') ?: null;
        $name = substr($connection, 0, $length);
        $dsn = substr($connection, $length + 1, strlen($connection));

        $length = strpos($dsn, ':') ?: null;
        $adapter = substr($dsn, 0, $length);

        $extras = [];
        foreach (explode(';', $dsn) as $element) {
            $parts = explode('=', $element);
            if (count($parts) === 2) {
                $extras[strtolower($parts[0])] = urldecode($parts[1]);
            }
        }
        $extras['adapter'] = $adapter;

        return [$name, $dsn, $extras];
    }

    /**
     * Parse a connection string and return an array of properties to pass to GeneratorConfig constructor.
     * The connection must be in the following format:
     * `bookstore=mysql:host=127.0.0.1;dbname=test;user=root;password=foobar`
     * where "bookstore" is your propel database name (used in your schema.xml).
     *
     * @param string $connection The connection string
     * @param string|null $section The section where the connection must be registered in (generator, runtime...)
     *
     * @return array
     */
    protected function connectionToProperties(string $connection, ?string $section = null): array
    {
        [$name, $dsn, $infos] = $this->parseConnection($connection);
        $config['propel']['database']['connections'][$name]['classname'] = '\Propel\Runtime\Connection\ConnectionWrapper';
        $config['propel']['database']['connections'][$name]['adapter'] = strtolower($infos['adapter']);
        $config['propel']['database']['connections'][$name]['dsn'] = $dsn;
        $config['propel']['database']['connections'][$name]['user'] = isset($infos['user']) && $infos['user'] ? $infos['user'] : null;
        $config['propel']['database']['connections'][$name]['password'] = $infos['password'] ?? null;

        if ($section === null) {
            $section = 'generator';
        }

        if ($section === 'reverse') {
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
     * @return bool
     */
    protected function hasInputOption(string $option, InputInterface $input): bool
    {
        return $input->hasOption($option) && $input->getOption($option) !== null;
    }

    /**
     * Check if a given input argument exists and it isn't null.
     *
     * @param string $argument The name of the input argument to check
     * @param \Symfony\Component\Console\Input\InputInterface $input object
     *
     * @return bool
     */
    protected function hasInputArgument(string $argument, InputInterface $input): bool
    {
        return $input->hasArgument($argument) && $input->getArgument($argument) !== null;
    }
}
