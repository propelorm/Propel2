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
     * @param int $currentVersion
     * @param int|null $previousVersion
     *
     * @return bool
     */
    public function executeRollbackToPreviousVersion(int $currentVersion, ?int $previousVersion = null): bool
    {
        $this->output->writeln(sprintf(
            'Executing migration %s down',
            $this->migrationManager->getMigrationClassName($currentVersion),
        ));

        $migration = $this->migrationManager->getMigrationObject($currentVersion);

        $canBeRollback = $this->isFake() || $migration->preDown($this->migrationManager) !== false;
        if (!$canBeRollback && !$this->isForce()) {
            $this->output->writeln('<error>preDown() returned false. Aborting migration.</error>');

            return false;
        }

        if (!$canBeRollback) {
            $this->output->writeln('<error>preDown() returned false. Continue migration.</error>');
        }

        foreach ($migration->getDownSQL() as $datasource => $sql) {
            $this->executeRollbackForDatasource($datasource, $sql);

            $this->migrationManager->removeMigrationTimestamp($datasource, $currentVersion);

            if ($this->isVerbose()) {
                $this->output->writeln(sprintf(
                    'Downgraded migration date to %d for datasource "%s"',
                    $previousVersion,
                    $datasource,
                ));
            }
        }

        if (!$this->isFake()) {
            $migration->postDown($this->migrationManager);
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

        if ($this->isVerbose()) {
            $this->output->writeln(sprintf(
                'Connecting to database "%s" using DSN "%s"',
                $datasource,
                $connection['dsn'],
            ));
        }

        $conn = $this->migrationManager->getAdapterConnection($datasource);
        $res = 0;
        $statements = SqlParser::parseString($sql);

        if ($this->isFake()) {
            return;
        }

        foreach ($statements as $statement) {
            try {
                if ($this->isVerbose()) {
                    $this->output->writeln(sprintf('Executing statement `%s`', $statement));
                }

                $conn->exec($statement);
                $res++;
            } catch (Exception $e) {
                if ($this->isForce()) {
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

    /**
     * @return bool
     */
    protected function isFake(): bool
    {
        return (bool)$this->input->getOption(static::COMMAND_OPTION_FAKE);
    }

    /**
     * @return bool
     */
    protected function isForce(): bool
    {
        return (bool)$this->input->getOption(static::COMMAND_OPTION_FORCE);
    }

    /**
     * @return bool
     */
    protected function isVerbose(): bool
    {
        return (bool)$this->input->getOption(static::COMMAND_OPTION_VERBOSE);
    }
}
