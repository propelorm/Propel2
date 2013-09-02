<?php

namespace Propel\Tests\Generator\Migration;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

class MigrationTestCase extends TestCase
{

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $con;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var \Propel\Generator\Reverse\AbstractSchemaParser
     */
    protected $parser;

    /**
     * @var \Propel\Generator\Platform\PlatformInterface
     */
    protected $platform;

    public function setUp()
    {
        if (!$this->con) {
            require_once __DIR__ . '/../../../../Fixtures/migration/build/conf/migration-conf.php';
            $this->con = Propel::getConnection('migration');

            $adapterClass = Propel::getServiceContainer()->getAdapterClass('migration');
            $this->database = new Database();

            $schemaParserClass = sprintf('\\%s\\%sSchemaParser', 'Propel\\Generator\\Reverse', ucfirst($adapterClass));
            $platformClass = sprintf('\\%s\\%sPlatform', 'Propel\\Generator\\Platform', ucfirst($adapterClass));

            $this->parser = new $schemaParserClass($this->con);
            $this->platform = new $platformClass();
            $generatorConfig = new QuickGeneratorConfig();
            $generatorConfig->setBuildProperty('mysqlTableType', 'InnoDB');
            $this->platform->setGeneratorConfig($generatorConfig);

            $this->parser->setGeneratorConfig(new QuickGeneratorConfig());
            $this->parser->setPlatform($this->platform);
        }
    }

    /**
     * @param string $xml
     *
     * @return Database
     */
    public function applyXml($xml)
    {
        $this->readDatabase();

        $builder = new QuickBuilder();
        $builder->setPlatform($this->database->getPlatform());
        $builder->setSchema($xml);

        $database = $builder->getDatabase();
        $database->setSchema('migration');
        $database->setPlatform($this->database->getPlatform());

        $diff = DatabaseComparator::computeDiff($this->database, $database);

        if (false === $diff) {
            return null;
        }
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $this->con->beginTransaction();
        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            try {
                $stmt = $this->con->prepare($statement);
                $stmt->execute();
            } catch (\Exception $e) {
                $this->con->rollBack();
                throw new BuildException(sprintf("Can not execute SQL: \n%s\nFrom database: \n%s\n\nTo database: \n%s\n",
                    $statement,
                    $this->database,
                    $database
                ), null, $e);
            }
        }
        $this->con->commit();

        return $database;
    }

    public function readDatabase()
    {
        $this->database = new Database();
        $this->database->setSchema('migration');
        $this->database->setPlatform($this->platform);
        $this->parser->parse($this->database);
    }

    /**
     * Migrates the schema of originXml to the database, checks for no diff and then
     * migrates targetXml and checks again for no diff.
     *
     * @param string $originXml
     * @param string $targetXml
     */
    public function migrateAndTest($originXml, $targetXml)
    {
        $this->applyXmlAndTest($originXml);
        $this->applyXmlAndTest($targetXml);
    }

    /**
     * @param string $xml
     */
    public function applyXmlAndTest($xml)
    {
        $database = $this->applyXml($xml);
        if ($database) {
            $this->compareCurrentDatabase($database);
        }
    }

    /**
     * Compares the current database with $database.
     *
     * @param Database $database
     */
    public function compareCurrentDatabase(Database $database)
    {
        $this->readDatabase();
        $diff = DatabaseComparator::computeDiff($this->database, $database);
        if (false !== $diff) {
            $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);
            $this->fail(sprintf(
                    "There are unexpected diffs: \n%s\n`%s`\nCurrent Database: \n%s\nTo XML Database: \n%s\n",
                    $diff,
                    $sql,
                    $this->database,
                    $database)
            );
        }
        $this->assertFalse($diff, 'no changes.');
    }
}
