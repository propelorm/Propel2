<?php

namespace Propel\Tests\Helpers;

use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Reverse\MysqlSchemaParser;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

class PlatformDatabaseBuildTimeBase extends TestCase
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
     * @var PDO
     */
    public $con;

    protected function setUp()
    {
        include(__DIR__ . '/../../../Fixtures/reverse/mysql/build/conf/reverse-bookstore-conf.php');

        $this->con = Propel::getConnection('reverse-bookstore');

        $this->parser   = new MysqlSchemaParser($this->con);
        $this->platform = new MysqlPlatform();

        $this->parser->setGeneratorConfig(new QuickGeneratorConfig());
        $this->parser->setPlatform($this->platform);
        parent::setUp();
    }

    public function readDatabase()
    {
        $this->database = new Database();
        $this->database->setPlatform($this->platform);
        $this->parser->parse($this->database);
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
            $stmt = $this->con->prepare($statement);
            $stmt->execute();
        }
    }
}
