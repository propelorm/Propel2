<?php

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class PrepareTests extends Command
{
    /**
     * @var array
     */
    protected $projects = array(
        'bookstore',
        'bookstore-packaged',
        'namespaced',
        'reverse/mysql',
        'schemas',
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('vendor', InputArgument::OPTIONAL, 'The database vendor', 'mysql'),
                new InputArgument('user', InputArgument::OPTIONAL, 'The database user', 'root'),
                new InputArgument('password', InputArgument::OPTIONAL, 'The database password', ''),
            ))
            ->setName('test:prepare')
            ->setDescription('Prepare unit tests by building fixtures')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->projects as $project) {

        }
    }
}
