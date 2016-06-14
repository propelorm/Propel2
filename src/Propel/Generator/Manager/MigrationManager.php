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
    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @var ConnectionInterface[]
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

    public function getConnection($datasource)
    {
        if (!isset($this->connections[$datasource])) {
            throw new InvalidArgumentException(sprintf('Unknown datasource "%s"', $datasource));
        }

        return $this->connections[$datasource];
    }

    /**
     * @param $datasource
     * @return ConnectionInterface
     */
    public function getAdapterConnection($datasource)
    {
        if (!isset($this->adapterConnections[$datasource])) {
            $buildConnection = $this->getConnection($datasource);
            $conn = ConnectionFactory::create($buildConnection, AdapterFactory::create($buildConnection['adapter']));
            $this->adapterConnections[$datasource] = $conn;
        }

        return $this->adapterConnections[$datasource];
    }

    /**
     * @param  string            $datasource
     * @return PlatformInterface
     */
    public function getPlatform($datasource)
    {
        $params  = $this->getConnection($datasource);
        $adapter = $params['adapter'];

        $class = '\\Propel\\Generator\\Platform\\' . ucfirst($adapter) . 'Platform';

        return new $class();
    }

    /**
     * Set the migration table name
     *
     * @param string $migrationTable
     */
    public function setMigrationTable($migrationTable)
    {
        $this->migrationTable = $migrationTable;
    }

    /**
     * get the migration table name
     *
     * @return string
     */
    public function getMigrationTable()
    {
        return $this->migrationTable;
    }

    public function getAllDatabaseVersions()
    {
        $connections = $this->getConnections();
        if (!$connections) {
            throw new \Exception('You must define database connection settings in a buildtime-conf.xml file to use migrations');
        }

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
                    $migrationTimestamps[] = $migrationTimestamp;
                }
            } catch (\PDOException $e) {
                $this->createMigrationTable($name);
                $migrationTimestamps = [];
            }
        }

        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    public function migrationTableExists($datasource)
    {
        $conn = $this->getAdapterConnection($datasource);
        $sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function createMigrationTable($datasource)
    {
        $platform = $this->getPlatform($datasource);
        // modelize the table
        $database = new Database($datasource);
        $database->setPlatform($platform);

        $table = new Table($this->getMigrationTable());
        $database->addTable($table);

        $column = new Column('version');
        $column->getDomain()->copy($platform->getDomainForType('INTEGER'));
        $column->setDefaultValue(0);

        $table->addColumn($column);
        // insert the table into the database
        $statements = $platform->getAddTableDDL($table);
        $conn = $this->getAdapterConnection($datasource);
        $res = SqlParser::executeString($statements, $conn);

        if (!$res) {
            throw new \Exception(sprintf('Unable to create migration table in datasource "%s"', $datasource));
        }
    }

    public function removeMigrationTimestamp($datasource, $timestamp)
    {
        $platform = $this->getPlatform($datasource);
        $conn = $this->getAdapterConnection($datasource);
        $conn->transaction(function () use ($conn, $platform, $timestamp) {
            $sql = sprintf('DELETE FROM %s WHERE %s = ?',
                $this->getMigrationTable(),
                $platform->doQuoting('version')
            );
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $timestamp, \PDO::PARAM_INT);
            $stmt->execute();
        });
    }

    public function updateLatestMigrationTimestamp($datasource, $timestamp)
    {
        $platform = $this->getPlatform($datasource);
        $conn = $this->getAdapterConnection($datasource);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (?)',
            $this->getMigrationTable(),
            $platform->doQuoting('version')
        );
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $timestamp, \PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getMigrationTimestamps()
    {
        $path = $this->getWorkingDirectory();
        $migrationTimestamps = [];

        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (preg_match('/^PropelMigration_(\d+).*\.php$/', $file, $matches)) {
                    $migrationTimestamps[] = (integer) $matches[1];
                }
            }
        }

        return $migrationTimestamps;
    }

    public function getValidMigrationTimestamps()
    {
        $migrationTimestamps = array_diff($this->getMigrationTimestamps(), $this->getAllDatabaseVersions());
        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    public function hasPendingMigrations()
    {
        return [] !== $this->getValidMigrationTimestamps();
    }

    public function getAlreadyExecutedMigrationTimestamps()
    {
        $migrationTimestamps = array_intersect($this->getMigrationTimestamps(), $this->getAllDatabaseVersions());
        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    public function getFirstUpMigrationTimestamp()
    {
        $validTimestamps = $this->getValidMigrationTimestamps();

        return array_shift($validTimestamps);
    }

    public function getFirstDownMigrationTimestamp()
    {
        return $this->getOldestDatabaseVersion();
    }

    public function getMigrationClassName($timestamp, $suffix = "")
    {
        $className = sprintf('PropelMigration_%d', $timestamp);
        if ($suffix === "") {
            $suffix = $this->findMigrationClassNameSuffix($timestamp);
        }
        if ($suffix !== "") {
            $className .= '_' . $suffix;
        }
        return $className;
    }

    public function findMigrationClassNameSuffix($timestamp) {
        $suffix = "";
        $path = $this->getWorkingDirectory();
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (preg_match('/^PropelMigration_'.$timestamp.'(_)?(.*)\.php$/', $file, $matches)) {
                    $suffix = (string) $matches[2];
                }
            }
        }
        return $suffix;
    }

    public function getMigrationObject($timestamp)
    {
        $className = $this->getMigrationClassName($timestamp);
        require_once sprintf('%s/%s.php',
            $this->getWorkingDirectory(),
            $className
        );

        return new $className();
    }

    public function getMigrationClassBody($migrationsUp, $migrationsDown, $timestamp, $comment = "", $suffix = "")
    {
        $timeInWords = date('Y-m-d H:i:s', $timestamp);
        $migrationAuthor = ($author = $this->getUser()) ? 'by ' . $author : '';
        $migrationClassName = $this->getMigrationClassName($timestamp, $suffix);
        $migrationUpString = var_export($migrationsUp, true);
        $migrationDownString = var_export($migrationsDown, true);
        $commentString = var_export($comment, true);
        $migrationClassBody = <<<EOP
<?php

use Propel\Generator\Manager\MigrationManager;

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version $timestamp.
 * Generated on $timeInWords $migrationAuthor
 */
class $migrationClassName
{
    public \$comment = $commentString;

    public function preUp(MigrationManager \$manager)
    {
        // add the pre-migration code here
    }

    public function postUp(MigrationManager \$manager)
    {
        // add the post-migration code here
    }

    public function preDown(MigrationManager \$manager)
    {
        // add the pre-migration code here
    }

    public function postDown(MigrationManager \$manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return $migrationUpString;
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return $migrationDownString;
    }

}
EOP;

        return $migrationClassBody;
    }

    public function getMigrationFileName($timestamp, $suffix = "")
    {
        return sprintf('%s.php', $this->getMigrationClassName($timestamp, $suffix));
    }

    public static function getUser()
    {
        if (function_exists('posix_getuid')) {
            $currentUser = posix_getpwuid(posix_getuid());
            if (isset($currentUser['name'])) {
                return $currentUser['name'];
            }
        }

        return '';
    }

    public function getOldestDatabaseVersion()
    {
        $versions = $this->getAllDatabaseVersions();

        return array_pop($versions);
    }
}
