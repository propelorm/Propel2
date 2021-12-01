<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Manager\MigrationManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Fredrik Wollsén <fredrik@neam.se>
 */
class MigrationCreateCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('schema-dir', null, InputOption::VALUE_REQUIRED, 'The directory where the schema files are placed')
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'The output directory where the migration files are located')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use. Example: \'bookstore=mysql:host=127.0.0.1;dbname=test;user=root;password=foobar\' where "bookstore" is your propel database name (used in your schema.xml)', [])
            ->addOption('editor', null, InputOption::VALUE_OPTIONAL, 'The text editor to use to open diff files', null)
            ->addOption('comment', 'm', InputOption::VALUE_OPTIONAL, 'A comment for the migration', '')
            ->addOption('suffix', null, InputOption::VALUE_OPTIONAL, 'A suffix for the migration class', '')
            ->setName('migration:create')
            ->setDescription('Create an empty migration class');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configOptions = [];

        if ($this->hasInputOption('connection', $input)) {
            foreach ($input->getOption('connection') as $conn) {
                $configOptions += $this->connectionToProperties($conn);
            }
        }

        if ($this->hasInputOption('schema-dir', $input)) {
            $configOptions['propel']['paths']['schemaDir'] = $input->getOption('schema-dir');
        }

        if ($this->hasInputOption('output-dir', $input)) {
            $configOptions['propel']['paths']['migrationDir'] = $input->getOption('output-dir');
        }

        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas($generatorConfig->getSection('paths')['schemaDir'], $generatorConfig->getSection('generator')['recursive']));

        $migrationsUp = [];
        $migrationsDown = [];
        foreach ($manager->getDatabases() as $appDatabase) {
            $name = $appDatabase->getName();
            $migrationsUp[$name] = '';
            $migrationsDown[$name] = '';
        }

        $timestamp = time();
        $migrationFileName = $manager->getMigrationFileName($timestamp, $input->getOption('suffix'));
        $migrationClassBody = $manager->getMigrationClassBody($migrationsUp, $migrationsDown, $timestamp, $input->getOption('comment'), $input->getOption('suffix'));

        $file = $generatorConfig->getSection('paths')['migrationDir'] . DIRECTORY_SEPARATOR . $migrationFileName;
        file_put_contents($file, $migrationClassBody);

        $output->writeln(sprintf('"%s" file successfully created.', $file));

        $editorCmd = $input->getOption('editor');
        if ($editorCmd !== null) {
            $output->writeln(sprintf('Using "%s" as text editor', $editorCmd));
            shell_exec($editorCmd . ' ' . escapeshellarg($file));
        } else {
            $output->writeln('Now add SQL statements and data migration code as necessary.');
            $output->writeln('Once the migration class is valid, call the "migrate" task to execute it.');
        }

        return static::CODE_SUCCESS;
    }
}
