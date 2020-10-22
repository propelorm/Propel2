<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestPrepareCommand extends AbstractCommand
{
    /**
     * @var string
     */
    public const FIXTURES_DIR = 'tests/Fixtures';

    /**
     * @var string
     */
    public const DEFAULT_VENDOR = 'mysql';

    /**
     * @var string
     */
    public const DEFAULT_DSN = 'mysql:host=127.0.0.1;dbname=test';

    /**
     * @var string
     */
    public const DEFAULT_DB_USER = 'root';

    /**
     * @var string
     */
    public const DEFAULT_DB_PASSWD = '';

    /**
     * @var array
     */
    protected $fixtures = [
        //directory - array of connections
        'bookstore' => ['bookstore', 'bookstore-cms', 'bookstore-behavior'],
        'bookstore-packaged' => ['bookstore-packaged', 'bookstore-log'],
        'namespaced' => ['bookstore_namespaced'],
        'reverse/mysql' => ['reverse-bookstore'],
        'reverse/pgsql' => ['reverse-bookstore'],
        'schemas' => ['bookstore-schemas'],
        'migration' => ['migration'],
        'quoting' => ['quoting'],
    ];

    /**
     * @var string
     */
    protected $root;

    public function __construct()
    {
        parent::__construct();

        $this->root = (string)realpath(__DIR__ . '/../../../../');
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('vendor', null, InputOption::VALUE_REQUIRED, 'The database vendor', self::DEFAULT_VENDOR),
                new InputOption('dsn', null, InputOption::VALUE_REQUIRED, 'The data source name', self::DEFAULT_DSN),
                new InputOption('user', 'u', InputOption::VALUE_REQUIRED, 'The database user', self::DEFAULT_DB_USER),
                new InputOption('password', 'p', InputOption::VALUE_OPTIONAL, 'The database password', self::DEFAULT_DB_PASSWD),
                new InputOption('exclude-database', null, InputOption::VALUE_NONE, 'Whether this should not touch database\'s schema'),
            ])
            ->setName('test:prepare')
            ->setDescription('Prepare the Propel test suite by building fixtures');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = static::CODE_SUCCESS;
        foreach ($this->fixtures as $fixturesDir => $connections) {
            $run = $this->buildFixtures(sprintf('%s/%s', self::FIXTURES_DIR, $fixturesDir), $connections, $input, $output);
            if ($run !== static::CODE_SUCCESS) {
                $result = $run;
            }
        }

        return $result;
    }

    /**
     * @param string $fixturesDir
     * @param string[] $connections
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int Exit code
     */
    protected function buildFixtures($fixturesDir, $connections, InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists($this->root . '/' . $fixturesDir)) {
            $output->writeln(sprintf('<error>Directory "%s" not found.</error>', $fixturesDir));

            return static::CODE_ERROR;
        }

        $output->writeln(sprintf('Building fixtures in <info>%-40s</info> ' . ($input->getOption('exclude-database') ? '(exclude-database)' : ''), $fixturesDir));

        chdir($this->root . '/' . $fixturesDir);

        if (is_file('propel.yaml.dist')) {
            $content = file_get_contents('propel.yaml.dist');

            $content = str_replace('##DATABASE_VENDOR##', $input->getOption('vendor'), $content);
            $content = str_replace('##DATABASE_URL##', $input->getOption('dsn'), $content);
            $content = str_replace('##DATABASE_USER##', $input->getOption('user'), $content);
            $content = str_replace('##DATABASE_PASSWORD##', $input->getOption('password'), $content);

            file_put_contents('propel.yaml', $content);
        } else {
            $output->writeln(sprintf('<comment>No "propel.yaml.dist" file found, skipped.</comment>'));
        }

        if (is_file('propel.yaml')) {
            $in = new ArrayInput([
                'command' => 'config:convert',
                '--output-dir' => './build/conf',
                '--output-file' => sprintf('%s-conf.php', $connections[0]), // the first connection is the main one
            ]);

            $command = $this->getApplication()->find('config:convert');
            $command->run($in, $output);
        }

        if (0 < count((array)$this->getSchemas('.'))) {
            $in = new ArrayInput([
                'command' => 'model:build',
                '--schema-dir' => '.',
                '--output-dir' => 'build/classes/',
                '--platform' => ucfirst($input->getOption('vendor')) . 'Platform',
                '--verbose' => $input->getOption('verbose'),
            ]);

            $command = $this->getApplication()->find('model:build');
            $command->run($in, $output);
        }

        if ($input->getOption('exclude-database')) {
            return static::CODE_SUCCESS;
        }

        if (0 < count($this->getSchemas('.'))) {
            $in = new ArrayInput([
                'command' => 'sql:build',
                '--schema-dir' => '.',
                '--output-dir' => 'build/sql/',
                '--platform' => ucfirst($input->getOption('vendor')) . 'Platform',
                '--verbose' => $input->getOption('verbose'),
            ]);

            $command = $this->getApplication()->find('sql:build');
            $command->run($in, $output);

            $conParams = [];
            foreach ($connections as $con) {
                if (substr($input->getOption('dsn'), 0, 6) === 'sqlite') {
                    $conParams[] = sprintf(
                        '%s=%s',
                        $con,
                        $input->getOption('dsn')
                    );
                } else {
                    $conParams[] = sprintf(
                        '%s=%s;user=%s;password=%s',
                        $con,
                        $input->getOption('dsn'),
                        $input->getOption('user'),
                        $input->getOption('password')
                    );
                }
            }

            $in = new ArrayInput([
                'command' => 'sql:insert',
                '--sql-dir' => 'build/sql/',
                '--connection' => $conParams,
                '--verbose' => $input->getOption('verbose'),
            ]);

            $command = $this->getApplication()->find('sql:insert');
            $command->run($in, $output);
        }

        chdir($this->root);

        return static::CODE_SUCCESS;
    }
}
