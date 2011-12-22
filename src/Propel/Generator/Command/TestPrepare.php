<?php

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Command\Command;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestPrepare extends Command
{
    /**
     * @var string
     */
    const DEFAULT_VENDOR    = 'mysql';

    /**
     * @var string
     */
    const DEFAULT_DB_SERVER = '127.0.0.1';

    /**
     * @var string
     */
    const DEFAULT_DB_USER   = 'root';

    /**
     * @var string
     */
    const DEFAULT_DB_PASSWD = '';

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
                new InputOption('vendor', null, InputOption::VALUE_REQUIRED, 'The database vendor', self::DEFAULT_VENDOR),
                new InputOption('server', 's', InputOption::VALUE_REQUIRED, 'The database server', self::DEFAULT_DB_SERVER),
                new InputOption('user', 'u', InputOption::VALUE_REQUIRED, 'The database user', self::DEFAULT_DB_USER),
                new InputOption('password', 'p', InputOption::VALUE_REQUIRED, 'The database password', self::DEFAULT_DB_PASSWD),
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
                $this->buildFixtures('tests/Fixtures/'.$fixturesDir, $input, $output);
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

        $output->write(sprintf('Building fixtures in <info>%-40s</info> ', $fixturesDir));

        chdir($fixturesDir);

        $distributionFiles = array(
            'build.properties.dist' => 'build.properties',
            'runtime-conf.xml.dist' => 'runtime-conf.xml',
        );

        foreach ($distributionFiles as $sourceFile => $targetFile) {
            if (is_file($sourceFile)) {
                $content = file_get_contents($sourceFile);

                $content = str_replace('##DATABASE_VENDOR##', $input->getOption('vendor'), $content);
                $content = str_replace('##DATABASE_SERVER##', $input->getOption('server'), $content);
                $content = str_replace('##DATABASE_USER##', $input->getOption('user'), $content);
                $content = str_replace('##DATABASE_PASSWORD##', $input->getOption('password'), $content);

                file_put_contents($targetFile, $content);
            } else {
                $output->writeln(sprintf('<error>No "%s" file found, skipped.</error>', $sourceFile));

                return;
            }
        }

        if (is_file('schema.xml')) {
            shell_exec(sprintf('"%s" convert-conf', $this->propelgen));

            // use new commands
            $in = new ArrayInput(array(
                'command'	    => 'sql:build',
                '--input-dir'   => '.',
                '--output-dir'  => 'build/sql/',
                '--platform'    => ucfirst($input->getOption('vendor')) . 'Platform',
                '--verbose'		=> $input->getOption('verbose'),
            ));

            $command = $this->getApplication()->find('sql:build');
            $command->run($in, $output);

            $in = new ArrayInput(array(
                'command'       => 'model:build',
                '--input-dir'   => '.',
                '--output-dir'  => 'build/classes/',
                '--platform'    => ucfirst($input->getOption('vendor')) . 'Platform',
                '--verbose'		=> $input->getOption('verbose'),
            ));

            $command = $this->getApplication()->find('model:build');
            $command->run($in, $output);
        }

        shell_exec(sprintf('"%s" insert-sql', $this->propelgen));

        $output->writeln('OK');

        chdir($this->root);
    }
}
