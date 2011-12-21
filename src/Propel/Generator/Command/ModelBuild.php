<?php

namespace Propel\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Finder\Finder;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\ModelManager;
use Propel\Generator\Util\Filesystem;

/**
 * @author Florian Klein <florian.klein@free.fr>
 * @author William Durand <william.durand1@gmail.com>
 */
class ModelBuild extends Command
{
    const DEFAULT_OUTPUT_DIRECTORY                  = 'generated-classes';

    const DEFAULT_PEER_BUILDER                      = '\Propel\Generator\Builder\Om\PeerBuilder';

    const DEFAULT_PEER_STUB_BUILDER                 = '\Propel\Generator\Builder\Om\ExtensionPeerBuilder';

    const DEFAULT_OBJECT_BUILDER                    = '\Propel\Generator\Builder\Om\ObjectBuilder';

    const DEFAULT_OBJECT_STUB_BUILDER               = '\Propel\Generator\Builder\Om\ExtensionObjectBuilder';

    const DEFAULT_MULTIEXTEND_OBJECT_BUILDER        = '\Propel\Generator\Builder\Om\MultiExtendObjectBuilder';

    const DEFAULT_QUERY_BUILDER                     = '\Propel\Generator\Builder\Om\QueryBuilder';

    const DEFAULT_QUERY_STUB_BUILDER                = '\Propel\Generator\Builder\Om\ExtensionQueryBuilder';

    const DEFAULT_QUERY_INHERITANCE_BUILDER         = '\Propel\Generator\Builder\Om\QueryInheritanceBuilder';

    const DEFAULT_QUERY_INHERITANCE_STUB_BUILDER    = '\Propel\Generator\Builder\Om\ExtensionQueryInheritanceBuilder';

    const DEFAULT_TABLEMAP_BUILDER                  = '\Propel\Generator\Builder\Om\TableMapBuilder';

    const DEFAULT_PLURALIZER                        = '\Propel\Generator\Builder\Util\DefaultEnglishPluralizer';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('output-dir',   null, InputOption::VALUE_REQUIRED,  'The output directory', self::DEFAULT_OUTPUT_DIRECTORY),
            ))
            ->addOption('peer-class', null, InputOption::VALUE_REQUIRED,
                'The peer class generator name', self::DEFAULT_PEER_BUILDER)
            ->addOption('peer-stub-class', null, InputOption::VALUE_REQUIRED,
                'The peer stub class generator name', self::DEFAULT_PEER_STUB_BUILDER)
            ->addOption('object-class', null, InputOption::VALUE_REQUIRED,
                'The object class generator name', self::DEFAULT_OBJECT_BUILDER)
            ->addOption('object-stub-class', null, InputOption::VALUE_REQUIRED,
                'The object stub class generator name', self::DEFAULT_OBJECT_STUB_BUILDER)
            ->addOption('object-multiextend-class', null, InputOption::VALUE_REQUIRED,
                'The object multiextend class generator name', self::DEFAULT_MULTIEXTEND_OBJECT_BUILDER)
            ->addOption('query-class', null, InputOption::VALUE_REQUIRED,
                'The query class generator name', self::DEFAULT_QUERY_BUILDER)
            ->addOption('query-stub-class', null, InputOption::VALUE_REQUIRED,
                'The query stub class generator name', self::DEFAULT_QUERY_STUB_BUILDER)
            ->addOption('query-inheritance-class', null, InputOption::VALUE_REQUIRED,
                'The query inheritance class generator name', self::DEFAULT_QUERY_INHERITANCE_BUILDER)
            ->addOption('query-inheritance-stub-class', null, InputOption::VALUE_REQUIRED,
                'The query inheritance stub class generator name', self::DEFAULT_QUERY_INHERITANCE_STUB_BUILDER)
            ->addOption('tablemap-class', null, InputOption::VALUE_REQUIRED,
                'The tablemap class generator name', self::DEFAULT_TABLEMAP_BUILDER)
            ->addOption('pluralizer-class', null, InputOption::VALUE_REQUIRED,
                'The pluralizer class name', self::DEFAULT_PLURALIZER)
            ->addOption('disable-identifier-quoting', null, InputOption::VALUE_NONE,
                'Identifier quoting may result in undesired behavior (especially in Postgres)')
            ->setName('model:build')
            ->setDescription('Build the model classes based on Propel XML schemas')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::configure();

        $generatorConfig = new GeneratorConfig(array(
            'propel.platform.class'                     => $input->getOption('platform'),
            'propel.builder.peer.class'                 => $input->getOption('peer-class'),
            'propel.builder.peerstub.class'             => $input->getOption('peer-stub-class'),
            'propel.builder.object.class'               => $input->getOption('object-class'),
            'propel.builder.objectstub.class'           => $input->getOption('object-stub-class'),
            'propel.builder.objectmultiextend.class'    => $input->getOption('object-multiextend-class'),
            'propel.builder.query.class'                => $input->getOption('query-class'),
            'propel.builder.querystub.class'            => $input->getOption('query-stub-class'),
            'propel.builder.queryinheritance.class'     => $input->getOption('query-inheritance-class'),
            'propel.builder.queryinheritancestub.class' => $input->getOption('query-inheritance-stub-class'),
            'propel.builder.tablemap.class'             => $input->getOption('tablemap-class'),
            'propel.builder.pluralizer.class'           => $input->getOption('pluralizer-class'),
            'propel.disableIdentifierQuoting'           => $input->getOption('disable-identifier-quoting'),
        ));

        $filesystem = new Filesystem();
        $filesystem->mkdir($input->getOption('output-dir'));

        $manager = new ModelManager();
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas());
        $manager->setLoggerClosure(function($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        $manager->build();
    }
}
