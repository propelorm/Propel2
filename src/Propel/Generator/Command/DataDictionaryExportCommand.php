<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Manager\DataDictionaryExportManager;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Charles Crossan <crossan007@gmail.com>
 */
class DataDictionaryExportCommand extends AbstractCommand
{
    /**
     * @var string
     */
    public const DEFAULT_OUTPUT_DIRECTORY = 'generated-datadictionary';

    /**
     * @var string
     */
    protected const OPTION_OUTPUT_DIR = 'output-dir';

    /**
     * @var string
     */
    protected const OPTION_SCHEMA_DIR = 'schema-dir';

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption(static::OPTION_OUTPUT_DIR, null, InputOption::VALUE_REQUIRED, 'The output directory', static::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption(static::OPTION_SCHEMA_DIR, null, InputOption::VALUE_REQUIRED, 'The directory where the schema files are placed')
            ->setName('datadictionary:export')
            ->setAliases(['datadictionary', 'md'])
            ->setDescription('Generate Data Dictionary files (.md)');
    }

    /**
     * {@inheritDoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \Symfony\Component\Console\Exception\MissingInputException
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configOptions = [];
        if ($this->hasInputOption(static::OPTION_SCHEMA_DIR, $input)) {
            $configOptions['propel']['paths']['schemaDir'] = $input->getOption(static::OPTION_SCHEMA_DIR);
        }
        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);
        $manager = new DataDictionaryExportManager();
        $manager->setGeneratorConfig($generatorConfig);
        $schemaDir = $generatorConfig->getConfigProperty('paths.schemaDir');
        if (!$schemaDir) {
            throw new MissingInputException('Path to schema directory is missing. Use the --' . static::OPTION_SCHEMA_DIR . ' option or the propel.paths.schemaDir configuration property to set it.');
        }
        $recursive = $generatorConfig->getConfigProperty('generator.recursive');
        $schemas = $this->getSchemas($schemaDir, $recursive);
        $manager->setSchemas($schemas);
        $manager->setLoggerClosure(function ($message) use ($input, $output): void {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });

        $outputDir = $input->getOption(static::OPTION_OUTPUT_DIR);
        $this->createDirectory($outputDir);
        $manager->setWorkingDirectory($outputDir);

        $manager->build();

        $output->writeln(sprintf('<info>Generated data dictionary file at %s.</info>', realpath($outputDir)));

        return static::CODE_SUCCESS;
    }
}
