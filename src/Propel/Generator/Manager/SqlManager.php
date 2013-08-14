<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;

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
     * @var array
     */
    protected $databases = null;

    /**
     * Set the database connection settings
     *
     * @param array $connections
     */
    public function setConnections($connections)
    {
        $this->connections = $connections;
    }

    /**
     * Get the database connection settings
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    public function hasConnection($connection)
    {
        return isset($this->connections[$connection]);
    }

    public function getConnection($datasource)
    {
        if (!$this->hasConnection($datasource)) {
            throw new InvalidArgumentException(sprintf('Unknown datasource "%s"', $datasource));
        }

        return $this->connections[$datasource];
    }

    /**
     * @return array
     */
    public function getDatabases()
    {
        if (null === $this->databases) {
            $databases = array();
            foreach ($this->getDataModels() as $dataModel) {
                foreach ($dataModel->getDatabases() as $database) {
                    if (!isset($databases[$database->getName()])) {
                        $databases[$database->getName()] = $database;
                    } else {
                        $tables = $database->getTables();
                        // Merge tables from different schema.xml to the same database
                        foreach ($tables as $table) {
                            if (!$databases[$database->getName()]->hasTable($table->getName(), true)) {
                                $databases[$database->getName()]->addTable($table);
                            }
                        }
                    }
                }
            }
            $this->databases = $databases;
        }

        return $this->databases;
    }

    /**
     * @return string
     */
    public function getSqlDbMapFilename()
    {
        return $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . 'sqldb.map';
    }

    /**
     * Build SQL files.
     */
    public function buildSql()
    {
        $sqlDbMapContent = "# Sqlfile -> Database map\n";
        foreach ($this->getDatabases() as $datasource => $database) {
            $platform = $database->getPlatform();
            $filename = $database->getName() . '.sql';

            if ($this->getGeneratorConfig()->getBuildProperty('disableIdentifierQuoting')) {
                $platform->setIdentifierQuoting(false);
            }

            $ddl = $platform->getAddTablesDDL($database);

            $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $filename;
            // Check if the file changed
            if (!file_exists($file) || $ddl !== file_get_contents($file)) {
                file_put_contents($file, $ddl);
            }

            $sqlDbMapContent .= sprintf("%s=%s\n", $filename, $datasource);
        }

        file_put_contents($this->getSqlDbMapFilename(), $sqlDbMapContent);
    }

    /**
     * @param string $datasource A datasource name.
     */
    public function insertSql($datasource = null)
    {
        $statementsToInsert = array();
        foreach ($this->getProperties($this->getSqlDbMapFilename()) as $sqlFile => $database) {
            if (null !== $datasource && $database !== $datasource) {
                // skip
                $this->log(sprintf('Skipping %s.', $sqlFile));
                break;
            }

            if (!isset($statementsToInsert[$database])) {
                $statementsToInsert[$database] = array();
            }

            if (null === $datasource || (null !== $database && $database === $datasource)) {
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
                $this->log(sprintf("No connection available for %s database", $database));
                continue;
            }

            $con = $this->getConnectionInstance($database);
            $con->beginTransaction();

            try {
                foreach ($sqls as $sql) {
                    try {
                        $stmt = $con->prepare($sql);
                        $stmt->execute();
                    } catch (\Exception $e) {
                        $message = sprintf('SQL insert failed: %s', $sql);
                        throw new \Exception($message, 0, $e);
                    }
                }

                $con->commit();
            } catch (\PDOException $e) {
                $con->rollback();
                throw $e;
            }

            $this->log(sprintf('%d queries executed for %s database.', count($sqls), $database));
        }

        return true;
    }

    /**
     * Returns a ConnectionInterface instance for a given datasource.
     *
     * @return ConnectionInterface
     */
    protected function getConnectionInstance($datasource)
    {
        $buildConnection = $this->getConnection($datasource);

        $dsn = str_replace("@DB@", $datasource, $buildConnection['dsn']);

        // Set user + password to null if they are empty strings or missing
        $username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
        $password = isset($buildConnection['password']) && $buildConnection['password'] ? $buildConnection['password'] : null;

        $con = ConnectionFactory::create(array('dsn' => $dsn, 'user' => $username, 'password' => $password), AdapterFactory::create($buildConnection['adapter']));

        return $con;
    }
}
