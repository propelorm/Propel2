<?php

namespace Propel\Tests\Helpers;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Reverse\MysqlSchemaParser;
use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Configuration;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;
use Propel\Tests\TestCaseFixtures;

class PlatformDatabaseBuildTimeBase extends TestCaseFixtures
{

    /**
     * @var Database
     */
    public $database;

    /**
     * @var MysqlSchemaParser
     */
    public $parser;

    /**
     * @var MysqlPlatform
     */
    public $platform;

    /**
     * @var string
     */
    protected $databaseName = 'reverse-bookstore';

    protected function setUp()
    {
        $config = sprintf('%s-conf.php', $this->databaseName);
        $path = 'reverse-bookstore' === $this->databaseName ? 'reverse/mysql' : $this->databaseName;
        $this->configuration = include(__DIR__ . '/../../../Fixtures/' . $path . '/build/conf/' . $config);

        $this->parser = $this->getParser($this->getConnection());
        $this->platform = $this->getPlatform();

        $this->parser->setGeneratorConfig(new QuickGeneratorConfig());
        $this->parser->setPlatform($this->platform);
        parent::setUp();
    }

    /**
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    protected function getConnection()
    {
        return $this->configuration->getConnectionManager($this->databaseName)->getWriteConnection();
    }

    public function readDatabase()
    {
        $this->database = new Database();
        $this->database->setIdentifierQuoting(true);
        $this->database->setPlatform($this->platform);
        $this->parser->parse($this->database);
    }

    /**
     * Builds all classes and migrates the database.
     *
     * @param string $schema xml schema
     */
    public function buildAndMigrate($schema)
    {
        $builder = new QuickBuilder();
        $platform = $this->getPlatform();

        $builder->setPlatform($platform);
        $builder->setParser($this->getParser($this->getConnection()));
        $builder->getParser()->setPlatform($platform);
        $builder->setSchema($schema);

        $builder->buildClasses(null, true);
        $builder->registerEntities($this->configuration);

        $builder->updateDB($this->getConnection());
    }

    /**
     * Migrates the database.
     *
     * @param string $schema xml schema
     */
    public function migrate($schema)
    {
        $builder = new QuickBuilder();
        $platform  = $this->getPlatform();

        $builder->setPlatform($platform);
        $builder->setParser($this->getParser($this->getConnection()));
        $builder->getParser()->setPlatform($platform);
        $builder->setSchema($schema);

        $builder->updateDB($this->getConnection());
    }

    /**
     * Detects the differences between current connected database and $pDatabase
     * and updates the schema. This does not DROP tables.
     *
     * @param Database $pDatabase
     */
    public function updateSchema($pDatabase)
    {
        $diff = DatabaseComparator::computeDiff($this->database, $pDatabase);
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            if (strpos($statement, 'DROP') === 0) {
                // drop statements cause errors since the table doesn't exist
                continue;
            }
            $stmt = $this->getConnection()->prepare($statement);
            $stmt->execute();
        }
    }
}
