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
use RuntimeException;

/**
 * Service class for preparing and executing migrations
 *
 * @author François Zaninotto
 */
class MigrationManager extends AbstractManager
{
    use PathTrait;

    /**
     * @var string
     */
    protected const COL_VERSION = 'version';

    /**
     * @var string
     */
    protected const COL_EXECUTION_DATETIME = 'execution_datetime';

    /**
     * @var string
     */
    protected const EXECUTION_DATETIME_FORMAT = 'Y-m-d H:i:s';

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
        $connection = $this->getConnection($datasource);
        $adapter = ucfirst($connection['adapter']);
        $class = '\\Propel\\Generator\\Platform\\' . $adapter . 'Platform';

        /** @var \Propel\Generator\Platform\PlatformInterface $platform */
        $platform = new $class();

        return $platform;
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

        $migrationData = [];
        foreach ($connections as $name => $params) {
            try {
                $migrationData += $this->getMigrationData($name);
            } catch (PDOException $e) {
                $this->createMigrationTable($name);
                $migrationData = [];
            }
        }

        usort($migrationData, function (array $a, array $b) {
            if ($a[static::COL_EXECUTION_DATETIME] === $b[static::COL_EXECUTION_DATETIME]) {
                return $a[static::COL_VERSION] <=> $b[static::COL_VERSION];
            }

            return $a[static::COL_EXECUTION_DATETIME] <=> $b[static::COL_EXECUTION_DATETIME];
        });

        return array_map(function (array $migration) {
            return (int)$migration[static::COL_VERSION];
        }, $migrationData);
    }

    /**
     * @param string $datasource
     *
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function migrationTableExists(string $datasource): bool
    {
        $conn = $this->getAdapterConnection($datasource);
        $sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
        try {
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
            }

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

        $table->addColumn($this->createVersionColumn($platform));
        $table->addColumn($this->createExecutionDatetimeColumn($platform));

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

            if ($stmt === false) {
                throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
            }

            $stmt->bindParam(1, $timestamp, PDO::PARAM_INT);
            $stmt->execute();
        });
    }

    /**
     * @param string $datasource
     * @param int $timestamp
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function updateLatestMigrationTimestamp(string $datasource, int $timestamp): void
    {
        $platform = $this->getPlatform($datasource);
        $conn = $this->getAdapterConnection($datasource);

        $this->modifyMigrationTableIfOutdated($datasource);

        $sql = sprintf(
            'INSERT INTO %s (%s, %s) VALUES (?, ?)',
            $this->getMigrationTable(),
            $platform->doQuoting(static::COL_VERSION),
            $platform->doQuoting(static::COL_EXECUTION_DATETIME),
        );

        $executionDatetime = date(static::EXECUTION_DATETIME_FORMAT);

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
        }

        $stmt->bindParam(1, $timestamp, PDO::PARAM_INT);
        $stmt->bindParam(2, $executionDatetime);
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

            if ($files) {
                foreach ($files as $file) {
                    if (preg_match('/^PropelMigration_(\d+).*\.php$/', $file, $matches)) {
                        $migrationTimestamps[] = (int)$matches[1];
                    }
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
     * - Gets non executed migrations.
     * - If `version` is provided, filters out values after the given version in the result.
     *
     * @param int|null $version
     *
     * @return list<int>
     */
    public function getNonExecutedMigrationTimestampsByVersion(?int $version = null): array
    {
        $migrationTimestamps = $this->getValidMigrationTimestamps();

        if ($version === null) {
            return $migrationTimestamps;
        }

        $versionIndex = array_search($version, $migrationTimestamps, true);
        if ($versionIndex === false) {
            return $migrationTimestamps;
        }

        return array_slice($migrationTimestamps, 0, (int)$versionIndex + 1);
    }

    /**
     * @return bool
     */
    public function hasPendingMigrations(): bool
    {
        return $this->getValidMigrationTimestamps() !== [];
    }

    /**
     * @return list<int>
     */
    public function getAlreadyExecutedMigrationTimestamps(): array
    {
        $allDatabaseVersions = $this->getAllDatabaseVersions();
        $migrationTimestamps = array_intersect($this->getMigrationTimestamps(), $allDatabaseVersions);

        $sortOrder = array_flip($allDatabaseVersions);
        usort($migrationTimestamps, function (int $a, int $b) use ($sortOrder) {
            return $sortOrder[$a] <=> $sortOrder[$b];
        });

        return $migrationTimestamps;
    }

    /**
     * - Gets already executed migration timestamps.
     * - If `version` is provided, filters out values before the given version in the result.
     *
     * @param int|null $version
     *
     * @return list<int>
     */
    public function getAlreadyExecutedMigrationTimestampsByVersion(?int $version = null): array
    {
        $migrationTimestamps = $this->getAlreadyExecutedMigrationTimestamps();

        if ($version === null) {
            return $migrationTimestamps;
        }

        $versionIndex = array_search($version, $migrationTimestamps, true);
        if ($versionIndex === false) {
            return $migrationTimestamps;
        }

        return array_slice($migrationTimestamps, $versionIndex + 1);
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
            $files = scandir($path) ?: [];
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

    /**
     * @param string $datasource
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function modifyMigrationTableIfOutdated(string $datasource): void
    {
        $connection = $this->getAdapterConnection($datasource);

        if ($this->columnExists($connection, static::COL_EXECUTION_DATETIME)) {
            return;
        }

        $table = new Table($this->getMigrationTable());

        $platform = $this->getPlatform($datasource);
        $column = $this->createExecutionDatetimeColumn($platform);
        $column->setTable($table);

        /** @phpstan-var \Propel\Generator\Platform\DefaultPlatform $platform */
        $sql = $platform->getAddColumnDDL($column);
        $stmt = $connection->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
        }

        $stmt->execute();
    }

    /**
     * @param int $version
     *
     * @return bool
     */
    public function isDatabaseVersionApplied(int $version): bool
    {
        return in_array($version, $this->getAlreadyExecutedMigrationTimestamps());
    }

    /**
     * @param string $connectionName
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function getMigrationData(string $connectionName): array
    {
        $connection = $this->getAdapterConnection($connectionName);
        $platform = $this->getGeneratorConfig()->getConfiguredPlatform($connection);
        if (!$platform->supportsMigrations()) {
            return [];
        }

        $this->modifyMigrationTableIfOutdated($connectionName);

        $sql = sprintf(
            'SELECT %s, %s FROM %s',
            static::COL_VERSION,
            static::COL_EXECUTION_DATETIME,
            $this->getMigrationTable(),
        );

        $stmt = $connection->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return \Propel\Generator\Model\Column
     */
    protected function createVersionColumn(PlatformInterface $platform): Column
    {
        $column = new Column(static::COL_VERSION);
        $column->getDomain()->copy($platform->getDomainForType('INTEGER'));
        $column->setDefaultValue('0');

        return $column;
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return \Propel\Generator\Model\Column
     */
    protected function createExecutionDatetimeColumn(PlatformInterface $platform): Column
    {
        $column = new Column(static::COL_EXECUTION_DATETIME);
        $column->getDomain()->copy($platform->getDomainForType('DATETIME'));

        return $column;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $connection
     * @param string $columnName
     *
     * @throws \RuntimeException
     *
     * @return bool
     */
    protected function columnExists(ConnectionInterface $connection, string $columnName): bool
    {
        try {
            $sql = sprintf('SELECT %s FROM %s', $columnName, $this->getMigrationTable());
            $stmt = $connection->prepare($sql);

            if ($stmt === false) {
                throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
            }

            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
