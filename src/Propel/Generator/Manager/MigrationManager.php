<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Manager;

use Exception;
use PDO;
use PDOException;
use Propel\Common\Util\PathTrait;
use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Service class for preparing and executing migrations
 *
 * @author François Zaninotto
 */
class MigrationManager extends AbstractManager
{
    use PathTrait;

    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @var array<\Propel\Runtime\Connection\ConnectionInterface>
     */
    protected $adapterConnections = [];

    /**
     * @var string
     */
    protected $migrationTable;

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
     * @param string $datasource
     *
     * @throws \Propel\Generator\Exception\InvalidArgumentException
     *
     * @return array
     */
    public function getConnection(string $datasource): array
    {
        if (!isset($this->connections[$datasource])) {
            throw new InvalidArgumentException(sprintf('Unknown datasource "%s"', $datasource));
        }

        return $this->connections[$datasource];
    }

    /**
     * @param string $datasource
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getAdapterConnection(string $datasource): ConnectionInterface
    {
        if (!isset($this->adapterConnections[$datasource])) {
            $buildConnection = $this->getConnection($datasource);
            $conn = ConnectionFactory::create($buildConnection, AdapterFactory::create($buildConnection['adapter']));
            $this->adapterConnections[$datasource] = $conn;
        }

        return $this->adapterConnections[$datasource];
    }

    /**
     * @param string $datasource
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform(string $datasource): PlatformInterface
    {
        $params = $this->getConnection($datasource);
        $adapter = $params['adapter'];

        $class = '\\Propel\\Generator\\Platform\\' . ucfirst($adapter) . 'Platform';

        return new $class();
    }

    /**
     * Set the migration table name
     *
     * @param string $migrationTable
     *
     * @return void
     */
    public function setMigrationTable(string $migrationTable): void
    {
        $this->migrationTable = $migrationTable;
    }

    /**
     * get the migration table name
     *
     * @return string
     */
    public function getMigrationTable(): string
    {
        return $this->migrationTable;
    }

    /**
     * @throws \Exception
     *
     * @return list<int>
     */
    public function getAllDatabaseVersions(): array
    {
        $connections = $this->getConnections();
        if (!$connections) {
            throw new Exception('You must define database connection settings in a buildtime-conf.xml file to use migrations');
        }

        /** @var array<int> $migrationTimestamps */
        $migrationTimestamps = [];
        foreach ($connections as $name => $params) {
            $conn = $this->getAdapterConnection($name);
            $platform = $this->getGeneratorConfig()->getConfiguredPlatform($conn);
            if (!$platform->supportsMigrations()) {
                continue;
            }

            $sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());

            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute();

                while ($migrationTimestamp = $stmt->fetchColumn()) {
                    /** @phpstan-var int $migrationTimestamp */
                    $migrationTimestamps[] = $migrationTimestamp;
                }
            } catch (PDOException $e) {
                $this->createMigrationTable($name);
                $migrationTimestamps = [];
            }
        }

        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    /**
     * @param string $datasource
     *
     * @return bool
     */
    public function migrationTableExists(string $datasource): bool
    {
        $conn = $this->getAdapterConnection($datasource);
        $sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @param string $datasource
     *
     * @throws \Exception
     *
     * @return void
     */
    public function createMigrationTable(string $datasource): void
    {
        /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
        $platform = $this->getPlatform($datasource);
        // modelize the table
        $database = new Database($datasource);
        $database->setPlatform($platform);

        $table = new Table($this->getMigrationTable());
        $database->addTable($table);

        $column = new Column('version');
        $column->getDomain()->copy($platform->getDomainForType('INTEGER'));
        $column->setDefaultValue('0');

        $table->addColumn($column);
        // insert the table into the database
        $statements = $platform->getAddTableDDL($table);
        $conn = $this->getAdapterConnection($datasource);
        $res = SqlParser::executeString($statements, $conn);

        if (!$res) {
            throw new Exception(sprintf('Unable to create migration table in datasource "%s"', $datasource));
        }
    }

    /**
     * @param string $datasource
     * @param int $timestamp
     *
     * @return void
     */
    public function removeMigrationTimestamp(string $datasource, int $timestamp): void
    {
        $platform = $this->getPlatform($datasource);
        $conn = $this->getAdapterConnection($datasource);
        $conn->transaction(function () use ($conn, $platform, $timestamp): void {
            $sql = sprintf(
                'DELETE FROM %s WHERE %s = ?',
                $this->getMigrationTable(),
                $platform->doQuoting('version'),
            );
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $timestamp, PDO::PARAM_INT);
            $stmt->execute();
        });
    }

    /**
     * @param string $datasource
     * @param int $timestamp
     *
     * @return void
     */
    public function updateLatestMigrationTimestamp(string $datasource, int $timestamp): void
    {
        $platform = $this->getPlatform($datasource);
        $conn = $this->getAdapterConnection($datasource);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (?)',
            $this->getMigrationTable(),
            $platform->doQuoting('version'),
        );
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $timestamp, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @return array<int>
     */
    public function getMigrationTimestamps(): array
    {
        $path = $this->getWorkingDirectory();
        $migrationTimestamps = [];

        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (preg_match('/^PropelMigration_(\d+).*\.php$/', $file, $matches)) {
                    $migrationTimestamps[] = (int)$matches[1];
                }
            }
        }

        return $migrationTimestamps;
    }

    /**
     * @return array<int>
     */
    public function getValidMigrationTimestamps(): array
    {
        $migrationTimestamps = array_diff($this->getMigrationTimestamps(), $this->getAllDatabaseVersions());
        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    /**
     * @return bool
     */
    public function hasPendingMigrations(): bool
    {
        return $this->getValidMigrationTimestamps() !== [];
    }

    /**
     * @return array<int>
     */
    public function getAlreadyExecutedMigrationTimestamps(): array
    {
        $migrationTimestamps = array_intersect($this->getMigrationTimestamps(), $this->getAllDatabaseVersions());
        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    /**
     * @return int|null
     */
    public function getFirstUpMigrationTimestamp(): ?int
    {
        $validTimestamps = $this->getValidMigrationTimestamps();

        return array_shift($validTimestamps);
    }

    /**
     * @return int|null
     */
    public function getFirstDownMigrationTimestamp(): ?int
    {
        return $this->getOldestDatabaseVersion();
    }

    /**
     * @param int $timestamp
     * @param string $suffix
     *
     * @return string
     */
    public function getMigrationClassName(int $timestamp, string $suffix = ''): string
    {
        $className = sprintf('PropelMigration_%d', $timestamp);
        if ($suffix === '') {
            $suffix = $this->findMigrationClassNameSuffix($timestamp);
        }
        if ($suffix !== '') {
            $className .= '_' . $suffix;
        }

        return $className;
    }

    /**
     * @param int $timestamp
     *
     * @return string
     */
    public function findMigrationClassNameSuffix(int $timestamp): string
    {
        $suffix = '';
        $path = $this->getWorkingDirectory();
        if ($path && is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (preg_match('/^PropelMigration_' . $timestamp . '(_)?(.*)\.php$/', $file, $matches)) {
                    $suffix = (string)$matches[2];
                }
            }
        }

        return $suffix;
    }

    /**
     * @param int $timestamp
     *
     * @return object
     */
    public function getMigrationObject(int $timestamp): object
    {
        $className = $this->getMigrationClassName($timestamp);
        $filename = sprintf(
            '%s/%s.php',
            $this->getWorkingDirectory(),
            $className,
        );
        require_once $filename;

        return new $className();
    }

    /**
     * @param array<string> $migrationsUp
     * @param array<string> $migrationsDown
     * @param int $timestamp
     * @param string $comment
     * @param string $suffix
     *
     * @return string
     */
    public function getMigrationClassBody(array $migrationsUp, array $migrationsDown, int $timestamp, string $comment = '', string $suffix = ''): string
    {
        $connectionToVariableName = self::buildConnectionToVariableNameMap($migrationsUp, $migrationsDown);

        $vars = [
            'timestamp' => $timestamp,
            'commentString' => addcslashes($comment, ','),
            'suffix' => $suffix,
            'timeInWords' => date('Y-m-d H:i:s', $timestamp),
            'migrationAuthor' => ($author = $this->getUser()) ? 'by ' . $author : '',
            'migrationClassName' => $this->getMigrationClassName($timestamp, $suffix),
            'migrationsUp' => $migrationsUp,
            'migrationsDown' => $migrationsDown,
            'connectionToVariableName' => $connectionToVariableName,
        ];

        $templatePath = $this->getTemplatePath(__DIR__);

        $template = new PropelTemplate();
        $filePath = $templatePath . 'migration_template.php';
        $template->setTemplateFile($filePath);

        return $template->render($vars);
    }

    /**
     *  * Builds an array mapping connection names to a string that can be used as a php variable name.
     *
     * @param array $migrationsUp
     * @param array $migrationsDown
     *
     * @return array<string, string>
     */
    protected static function buildConnectionToVariableNameMap(array $migrationsUp, array $migrationsDown): array
    {
        $connectionToVariableName = [];
        foreach ([$migrationsUp, $migrationsDown] as $migrations) {
            $connectionNames = array_keys($migrations);
            foreach ($connectionNames as $index => $connectionName) {
                if (array_key_exists($connectionName, $connectionToVariableName)) {
                    continue;
                }
                $alphNums = preg_replace('/\W/', '', $connectionName);
                if (strlen($alphNums) === 0) {
                    $alphNums = $index;
                }
                $variableName = '$connection_' . $alphNums;
                while (in_array($variableName, $connectionToVariableName, true)) {
                    $variableName .= 'I';
                }
                $connectionToVariableName[$connectionName] = $variableName;
            }
        }

        return $connectionToVariableName;
    }

    /**
     * @param int $timestamp
     * @param string $suffix
     *
     * @return string
     */
    public function getMigrationFileName(int $timestamp, string $suffix = ''): string
    {
        return sprintf('%s.php', $this->getMigrationClassName($timestamp, $suffix));
    }

    /**
     * @return string
     */
    public static function getUser(): string
    {
        if (function_exists('posix_getuid')) {
            $currentUser = posix_getpwuid(posix_getuid());
            if (isset($currentUser['name'])) {
                return $currentUser['name'];
            }
        }

        return '';
    }

    /**
     * @return int|null
     */
    public function getOldestDatabaseVersion(): ?int
    {
        $versions = $this->getAllDatabaseVersions();
        if (!$versions) {
            return null;
        }

        return array_pop($versions);
    }
}
