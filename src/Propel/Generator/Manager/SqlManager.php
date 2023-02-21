<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Manager;

use Exception;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionInterface;
use RuntimeException;

/**
 * Service class for managing SQL.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class SqlManager extends AbstractManager
{
    /**
     * @var array
     */
    protected $connections;

    /**
     * @var bool
     */
    protected $overwriteSqlMap = false;

    /**
     * Set the database connection settings
     *
     * @param array $connections
     *
     * @return void
     */
    public function setConnections(array $connections): void
    {
        $this->connections = $connections;
    }

    /**
     * Get the database connection settings
     *
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @param string $connection
     *
     * @return bool
     */
    public function hasConnection(string $connection): bool
    {
        return isset($this->connections[$connection]);
    }

    /**
     * @return bool
     */
    public function isOverwriteSqlMap(): bool
    {
        return $this->overwriteSqlMap;
    }

    /**
     * @param bool $overwriteSqlMap
     *
     * @return void
     */
    public function setOverwriteSqlMap(bool $overwriteSqlMap): void
    {
        $this->overwriteSqlMap = $overwriteSqlMap;
    }

    /**
     * @param string $datasource
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return array
     */
    public function getConnection(string $datasource): array
    {
        if (!$this->hasConnection($datasource)) {
            throw new InvalidArgumentException(sprintf('Unknown datasource "%s"', $datasource));
        }

        return $this->connections[$datasource];
    }

    /**
     * @return string
     */
    public function getSqlDbMapFilename(): string
    {
        return $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . 'sqldb.map';
    }

    /**
     * Build SQL files.
     *
     * @return void
     */
    public function buildSql(): void
    {
        $sqlDbMapContent = "# Sqlfile -> Database map\n";
        foreach ($this->getDatabases() as $datasource => $database) {
            /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
            $platform = $database->getPlatform();
            $filename = $database->getName() . '.sql';

            $ddl = $platform->getAddTablesDDL($database);

            $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $filename;
            // Check if the file changed
            if (!file_exists($file) || $ddl !== file_get_contents($file)) {
                file_put_contents($file, $ddl);
            }

            $sqlDbMapContent .= sprintf("%s=%s\n", $filename, $datasource);
        }

        if ($this->isOverwriteSqlMap() || !$this->existSqlMap()) {
            file_put_contents($this->getSqlDbMapFilename(), $sqlDbMapContent);
        }
    }

    /**
     * Checks if the sqldb.map exists.
     *
     * @return bool
     */
    public function existSqlMap(): bool
    {
        return file_exists($this->getSqlDbMapFilename());
    }

    /**
     * @param string|null $datasource A datasource name.
     *
     * @return bool
     */
    public function insertSql(?string $datasource = null): bool
    {
        $statementsToInsert = [];
        foreach ($this->getProperties($this->getSqlDbMapFilename()) as $sqlFile => $database) {
            if ($datasource !== null && $database !== $datasource) {
                // skip
                $this->log(sprintf('Skipping %s.', $sqlFile));

                break;
            }

            if (!isset($statementsToInsert[$database])) {
                $statementsToInsert[$database] = [];
            }

            if ($datasource === null || ($database !== null && $database === $datasource)) {
                $filename = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $sqlFile;

                if (file_exists($filename)) {
                    foreach (SqlParser::parseFile($filename) as $sql) {
                        $statementsToInsert[$database][] = $sql;
                    }
                } else {
                    $this->log(sprintf("File %s doesn't exist", $filename));
                }
            }
        }

        foreach ($statementsToInsert as $database => $sqls) {
            if (!$this->hasConnection($database)) {
                $this->log(sprintf('No connection available for %s database', $database));

                continue;
            }

            $con = $this->getConnectionInstance($database);
            $con->transaction(function () use ($con, $sqls): void {
                foreach ($sqls as $sql) {
                    try {
                        $stmt = $con->prepare($sql);

                        if ($stmt === false) {
                            throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
                        }

                        $stmt->execute();
                    } catch (Exception $e) {
                        $message = sprintf('SQL insert failed: %s', $sql);

                        throw new Exception($message, 0, $e);
                    }
                }
            });

            $this->log(sprintf('%d queries executed for %s database.', count($sqls), $database));
        }

        return true;
    }

    /**
     * Returns a ConnectionInterface instance for a given datasource.
     *
     * @param string $datasource
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    protected function getConnectionInstance(string $datasource): ConnectionInterface
    {
        $buildConnection = $this->getConnection($datasource);

        $dsn = str_replace('@DB@', $datasource, $buildConnection['dsn']);

        // Set user + password to null if they are empty strings or missing
        $username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
        $password = isset($buildConnection['password']) && $buildConnection['password'] ? $buildConnection['password'] : null;

        $con = ConnectionFactory::create(['dsn' => $dsn, 'user' => $username, 'password' => $password], AdapterFactory::create($buildConnection['adapter']));

        return $con;
    }
}
