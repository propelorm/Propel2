<?php

namespace Propel\Tests\Generator\Migration;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Util\QuickBuilder;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

class MigrationTestCase extends TestCaseFixturesDatabase
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
        parent::setUp();
        if (!$this->con) {
            require_once __DIR__ . '/../../../../Fixtures/migration/build/conf/migration-conf.php';
            $this->con = Propel::getConnection('migration');

            $adapterClass = Propel::getServiceContainer()->getAdapterClass('migration');
            $this->database = new Database();

            $schemaParserClass = sprintf('\\%s\\%sSchemaParser', 'Propel\\Generator\\Reverse', ucfirst($adapterClass));
            $platformClass = sprintf('\\%s\\%sPlatform', 'Propel\\Generator\\Platform', ucfirst($adapterClass));

            $this->parser = new $schemaParserClass($this->con);
            $this->platform = new $platformClass();
            $this->platform->setIdentifierQuoting(true);
            $generatorConfig = new QuickGeneratorConfig();
            $this->platform->setGeneratorConfig($generatorConfig);

            $this->parser->setGeneratorConfig(new QuickGeneratorConfig());
            $this->parser->setPlatform($this->platform);
            $this->database->setPlatform($this->platform);
        }
    }

    /**
     * @param string $xml
     *
     * @return Database|boolean
     */
    public function applyXml($xml, $changeRequired = false)
    {
        $this->readDatabase();

        $builder = new QuickBuilder();
        $builder->setIdentifierQuoting(true);
        $builder->setPlatform($this->database->getPlatform());
        $builder->setSchema($xml);

        $database = $builder->getDatabase();
        $database->setSchema('migration');
        $database->setPlatform($this->database->getPlatform());

        $diff = DatabaseComparator::computeDiff($this->database, $database);

        if (false === $diff) {
            if ($changeRequired) {
                throw new BuildException(sprintf("No changes in schema to current database: \nSchema database:\n%s\n\nCurrent Database:\n%s",
                    $database,
                    $this->database
                ));
            }

            return false;
        }
        $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);

        $this->con->beginTransaction();
        if (!$sql) {
            throw new BuildException(
                sprintf('Ooops. There is a diff between current database and schema xml but no SQL has been generated. Change: %s',
                $diff
            ));
        }

        $statements = SqlParser::parseString($sql);
        foreach ($statements as $statement) {
            try {
                $stmt = $this->con->prepare($statement);
                $stmt->execute();
            } catch (\Exception $e) {
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
        try {
            $this->applyXmlAndTest($originXml);
        } catch (BuildException $e) {
            throw new BuildException('There was a exception in applying the first(origin) schema', 0, $e);
        }

        try {
            $this->applyXmlAndTest($targetXml, true);
        } catch (BuildException $e) {
            throw new BuildException('There was a exception in applying the second(target) schema', 0, $e);
       }
    }

    /**
     * @param string $xml
     * @param bool   $changeRequired
     */
    public function applyXmlAndTest($xml, $changeRequired = false)
    {
        $database = $this->applyXml($xml, $changeRequired);
        if ($database) {
            $this->compareCurrentDatabase($database);
        }
    }

    /**
     * Compares the current database with $database.
     *
     * @param Database $database
     * @throws BuildException if a difference has been found between $database and the real database
     */
    public function compareCurrentDatabase(Database $database)
    {
        $this->readDatabase();
        $diff = DatabaseComparator::computeDiff($this->database, $database);
        if (false !== $diff) {
            $sql = $this->database->getPlatform()->getModifyDatabaseDDL($diff);
            throw new BuildException(sprintf(
                    "There are unexpected diffs (real to model): \n%s\n-----%s-----\nCurrent Database: \n%s\nTo XML Database: \n%s\n",
                    $diff,
                    $sql,
                    $this->database,
                    $database)
            );
        }
        $this->assertFalse($diff, 'no changes.');
    }
}
