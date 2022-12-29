<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command\Executor;

use Exception;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Service class for executing rollback.
 */
class RollbackExecutor
{
    /**
     * @var string
     */
    protected const COMMAND_OPTION_FAKE = 'fake';

    /**
     * @var string
     */
    protected const COMMAND_OPTION_FORCE = 'force';

    /**
     * @var string
     */
    protected const COMMAND_OPTION_VERBOSE = 'verbose';

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected InputInterface $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var \Propel\Generator\Manager\MigrationManager
     */
    protected MigrationManager $migrationManager;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Propel\Generator\Manager\MigrationManager $migrationManager
     */
    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        MigrationManager $migrationManager
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->migrationManager = $migrationManager;
    }

    /**
     * @param list<int> $previousTimestamps
     *
     * @return bool
     */
    public function executeRollbackToPreviousVersion(array &$previousTimestamps): bool
    {
        $nextMigrationTimestamp = array_pop($previousTimestamps);
        if (!$nextMigrationTimestamp) {
            $this->output->writeln('No migration were ever executed on this database - nothing to reverse.');

            return false;
        }

        $this->output->writeln(sprintf(
            'Executing migration %s down',
            $this->migrationManager->getMigrationClassName($nextMigrationTimestamp),
        ));

        $nbPreviousTimestamps = count($previousTimestamps);
        $previousTimestamp = 0;
        if ($nbPreviousTimestamps) {
            $previousTimestamp = $previousTimestamps[array_key_last($previousTimestamps)];
        }

        $migration = $this->migrationManager->getMigrationObject($nextMigrationTimestamp);
        if (!$this->input->getOption(static::COMMAND_OPTION_FAKE) && $migration->preDown($this->migrationManager) === false) {
            if (!$this->input->getOption(static::COMMAND_OPTION_FORCE)) {
                $this->output->writeln('<error>preDown() returned false. Aborting migration.</error>');

                return false;
            }

            $this->output->writeln('<error>preDown() returned false. Continue migration.</error>');
        }

        foreach ($migration->getDownSQL() as $datasource => $sql) {
            $this->executeRollbackForDatasource($datasource, $sql);

            $this->migrationManager->removeMigrationTimestamp($datasource, $nextMigrationTimestamp);

            if ($this->input->getOption(static::COMMAND_OPTION_VERBOSE)) {
                $this->output->writeln(sprintf(
                    'Downgraded migration date to %d for datasource "%s"',
                    $previousTimestamp,
                    $datasource,
                ));
            }
        }

        if (!$this->input->getOption(static::COMMAND_OPTION_FAKE)) {
            $migration->postDown($this->migrationManager);
        }

        if ($nbPreviousTimestamps) {
            $this->output->writeln(sprintf('Reverse migration complete. %d more migrations available for reverse.', $nbPreviousTimestamps));
        } else {
            $this->output->writeln('Reverse migration complete. No more migration available for reverse');
        }

        return true;
    }

    /**
     * @param string $datasource
     * @param string $sql
     *
     * @throws \Propel\Runtime\Exception\RuntimeException
     *
     * @return void
     */
    protected function executeRollbackForDatasource(string $datasource, string $sql): void
    {
        $connection = $this->migrationManager->getConnection($datasource);

        if ($this->input->getOption(static::COMMAND_OPTION_VERBOSE)) {
            $this->output->writeln(sprintf(
                'Connecting to database "%s" using DSN "%s"',
                $datasource,
                $connection['dsn'],
            ));
        }

        $conn = $this->migrationManager->getAdapterConnection($datasource);
        $res = 0;
        $statements = SqlParser::parseString($sql);

        if ($this->input->getOption(static::COMMAND_OPTION_FAKE)) {
            return;
        }

        foreach ($statements as $statement) {
            try {
                if ($this->input->getOption(static::COMMAND_OPTION_VERBOSE)) {
                    $this->output->writeln(sprintf('Executing statement `%s`', $statement));
                }

                $conn->exec($statement);
                $res++;
            } catch (Exception $e) {
                if ($this->input->getOption(static::COMMAND_OPTION_FORCE)) {
                    //continue, but print error message
                    $this->output->writeln(
                        sprintf('<error>Failed to execute SQL `%s`. Continue migration.</error>', $statement),
                    );
                } else {
                    throw new RuntimeException(
                        sprintf('<error>Failed to execute SQL `%s`. Aborting migration.</error>', $statement),
                        0,
                        $e,
                    );
                }
            }
        }

        $this->output->writeln(sprintf(
            '%d of %d SQL statements executed successfully on datasource "%s"',
            $res,
            count($statements),
            $datasource,
        ));
    }
}
