<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Manager\ModelManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Florian Klein <florian.klein@free.fr>
 * @author William Durand <william.durand1@gmail.com>
 */
class ModelBuildCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('mysql-engine', null, InputOption::VALUE_REQUIRED, 'MySQL engine (MyISAM, InnoDB, ...)')
            ->addOption('schema-dir', null, InputOption::VALUE_REQUIRED, 'The directory where the schema files are placed')
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'The output directory')
            ->addOption('object-class', null, InputOption::VALUE_REQUIRED, 'The object class generator name')
            ->addOption('object-stub-class', null, InputOption::VALUE_REQUIRED, 'The object stub class generator name')
            ->addOption('object-multiextend-class', null, InputOption::VALUE_REQUIRED, 'The object multiextend class generator name')
            ->addOption('query-class', null, InputOption::VALUE_REQUIRED, 'The query class generator name')
            ->addOption('query-stub-class', null, InputOption::VALUE_REQUIRED, 'The query stub class generator name')
            ->addOption('query-inheritance-class', null, InputOption::VALUE_REQUIRED, 'The query inheritance class generator name')
            ->addOption('query-inheritance-stub-class', null, InputOption::VALUE_REQUIRED, 'The query inheritance stub class generator name')
            ->addOption('tablemap-class', null, InputOption::VALUE_REQUIRED, 'The tablemap class generator name')
            ->addOption('pluralizer-class', null, InputOption::VALUE_REQUIRED, 'The pluralizer class name')
            ->addOption('enable-identifier-quoting', null, InputOption::VALUE_NONE, 'Identifier quoting may result in undesired behavior (especially in Postgres)')
            ->addOption('target-package', null, InputOption::VALUE_REQUIRED, '', '')
            ->addOption('disable-package-object-model', null, InputOption::VALUE_NONE, 'Disable schema database merging (packageObjectModel)')
            ->addOption('disable-namespace-auto-package', null, InputOption::VALUE_NONE, 'Disable namespace auto-packaging')
            ->addOption('composer-dir', null, InputOption::VALUE_REQUIRED, 'Directory in which your composer.json resides', null)
            ->setName('model:build')
            ->setAliases(['build'])
            ->setDescription('Build the model classes based on Propel XML schemas');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configOptions = [];
        $inputOptions = $input->getOptions();

        foreach ($inputOptions as $key => $option) {
            if ($option !== null) {
                switch ($key) {
                    case 'schema-dir':
                        $configOptions['propel']['paths']['schemaDir'] = $option;

                        break;
                    case 'output-dir':
                        $configOptions['propel']['paths']['phpDir'] = $option;

                        break;
                    case 'object-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['object'] = $option;

                        break;
                    case 'object-stub-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['objectstub'] = $option;

                        break;
                    case 'object-multiextend-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['objectmultiextend'] = $option;

                        break;
                    case 'query-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['query'] = $option;

                        break;
                    case 'query-stub-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['querystub'] = $option;

                        break;
                    case 'query-inheritance-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['queryinheritance'] = $option;

                        break;
                    case 'query-inheritance-stub-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['queryinheritancestub'] = $option;

                        break;
                    case 'tablemap-class':
                        $configOptions['propel']['generator']['objectModel']['builders']['tablemap'] = $option;

                        break;
                    case 'pluralizer-class':
                        $configOptions['propel']['generator']['objectModel']['pluralizerClass'] = $option;

                        break;
                    case 'composer-dir':
                        $configOptions['propel']['paths']['composerDir'] = $option;

                        break;
                    case 'disable-package-object-model':
                        if ($option) {
                            $configOptions['propel']['generator']['packageObjectModel'] = false;
                        }

                        break;
                    case 'disable-namespace-auto-package':
                        if ($option) {
                            $configOptions['propel']['generator']['namespaceAutoPackage'] = false;
                        }

                        break;
                    case 'mysql-engine':
                        $configOptions['propel']['database']['adapters']['mysql']['tableType'] = $option;

                        break;
                }
            }
        }

        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);
        $this->createDirectory($generatorConfig->getSection('paths')['phpDir']);

        $manager = new ModelManager();
        $manager->setFilesystem($this->getFilesystem());
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas($generatorConfig->getSection('paths')['schemaDir'], $generatorConfig->getSection('generator')['recursive']));
        $manager->setLoggerClosure(function ($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($generatorConfig->getSection('paths')['phpDir']);

        $manager->build();

        return static::CODE_SUCCESS;
    }
}
