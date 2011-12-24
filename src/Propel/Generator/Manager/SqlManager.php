<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Exception\InvalidArgumentException;

use \PDO;
use \PDOException;

/**
 * Service class for managing SQL.
 *
 * @author     William Durand <william.durand1@gmail.com>
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
            throw new InvalidArgumentException(sprintf('Unkown datasource "%s"', $datasource));
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
            foreach ($this->getDataModels() as $package => $dataModel) {
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
            if (file_exists($file) && $ddl == file_get_contents($file)) {
                // Unchanged
            } else {
                file_put_contents($file, $ddl);
            }

            $sqlDbMapContent .= sprintf("%s=%s\n", $filename, $datasource);
        }

        file_put_contents($this->getSqlDbMapFilename(), $sqlDbMapContent);
    }

    /**
     * @param string $datasource    A datasource name.
     */
    public function insertSql($datasource = null)
    {
        $statementsToInsert = array();
        foreach ($this->getProperties($this->getSqlDbMapFilename()) as $sqlFile => $database) {
            if (null !== $datasource && $database !== $datasource) {
                // skip
                break;
            }

            if (!isset($statementsToInsert[$database])) {
                $statementsToInsert[$database] = array();
            }
            if (null === $database || (null !== $database && $database === $datasource)) {
                $filename = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $sqlFile;

                if (file_exists($filename)) {
                    foreach (SqlParser::parseFile($filename) as $sql) {
                        $statementsToInsert[$database][] = $sql;
                    }
                }
            }
        }

        foreach ($statementsToInsert as $database => $sqls) {
            if (!$this->hasConnection($database)) {
                continue;
            }

            $pdo = $this->getPdoConnection($database);
            $pdo->beginTransaction();

            try {
                foreach ($sqls as $sql) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollback();
                throw $e;
            }
        }

        return true;
    }

    /**
     * Gets a PDO connection for a given datasource.
     *
     * @return PDO
     */
    protected function getPdoConnection($datasource)
    {
        $buildConnection = $this->getConnection($datasource);
        $dsn = str_replace("@DB@", $datasource, $buildConnection['dsn']);

        // Set user + password to null if they are empty strings or missing
        $username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
        $password = isset($buildConnection['password']) && $buildConnection['password'] ? $buildConnection['password'] : null;

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
