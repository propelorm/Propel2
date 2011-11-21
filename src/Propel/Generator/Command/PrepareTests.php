<?php

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\InputOption;
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
    protected $fixturesDirs = array(
        'bookstore',
        'bookstore-packaged',
        'namespaced',
        'reverse/mysql',
        'schemas',
    );

    /**
     * @var string
     */
    protected $root = null;

    /**
     * @var string
     */
    protected $propelgen = null;

    public function __construct()
    {
        parent::__construct();

        $this->root      = realpath(__DIR__.'/../../../../');
        $this->propelgen = $this->root.'/tools/generator/bin/propel-gen';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('vendor', null, InputOption::VALUE_REQUIRED, 'The database vendor', 'mysql'),
                new InputOption('user', 'u', InputOption::VALUE_REQUIRED, 'The database user', 'root'),
                new InputOption('password', 'p', InputOption::VALUE_REQUIRED, 'The database password', ''),
                new InputOption('fixtures-dir', null, InputOption::VALUE_REQUIRED, 'A fixture directory to build', null),
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
        if (null !== $fixturesDir = $input->getOption('fixtures-dir')) {
            $this->buildFixtures($fixturesDir, $input, $output);
        } else {
            foreach ($this->fixturesDirs as $fixturesDir) {
                $this->buildFixtures($this->root.'/tests/Fixtures/'.$fixturesDir, $input, $output);
            }
        }
    }

    /**
     * @param string $fixturesDir
     */
    protected function buildFixtures($fixturesDir, InputInterface $input, OutputInterface $output)
    {
        if (!file_exists($fixturesDir)) {
            $output->writeln(sprintf('<error>Directory "%s" not found.</error>', $fixturesDir));
            return;
        }

        $output->write(sprintf('Building fixtures in <info>%s</info>... ', $fixturesDir));

        chdir($fixturesDir);

        if (is_file('build.properties.dist')) {
            $content = file_get_contents('build.properties.dist');

            $content = str_replace('##DATABASE_VENDOR##', $input->getOption('vendor'), $content);
            $content = str_replace('##DATABASE_USER##', $input->getOption('user'), $content);
            $content = str_replace('##DATABASE_PASSWORD##', $input->getOption('password'), $content);

            file_put_contents('build.properties', $content);
        } else {
            $output->writeln('<error>No "build.properties.dist" file found, skipped.</error>');
            return;
        }

        if (is_file('runtime-conf.xml.dist')) {
            $content = file_get_contents('runtime-conf.xml.dist');

            $content = str_replace('##DATABASE_VENDOR##', $input->getOption('vendor'), $content);
            $content = str_replace('##DATABASE_USER##', $input->getOption('user'), $content);
            $content = str_replace('##DATABASE_PASSWORD##', $input->getOption('password'), $content);

            file_put_contents('runtime-conf.xml', $content);
        } else {
            $output->writeln('<error>No "runtime-conf.xml.dist" file found, skipped.</error>');
            return;
        }


        shell_exec(sprintf('%s main', $this->propelgen));
        shell_exec(sprintf('%s insert-sql', $this->propelgen));

        unlink('build.properties');
        unlink('runtime-conf.xml');

        $output->writeln('done.');

        chdir($this->root);
    }
}
