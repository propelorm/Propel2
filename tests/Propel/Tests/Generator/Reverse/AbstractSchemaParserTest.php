<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Reverse;

use PDO;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Database;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Abstract base class for database schema parser tests.
 *
 * @author Moritz Ringler
 *
 * @abstract
 *
 * @group database
 */
abstract class AbstractSchemaParserTest extends BookstoreTestBase
{
    /*
     * HACK: tests were written using instance properties for parser and 
     * parsedDatabase, leading to re-initialization on every test.
     * Using static properties instead fixes the issue, but I don't want
     * to update every test, so the static objects will be copied.
     */ 


    /**
     * @var \Propel\Generator\Model\Database
     */
    protected static $parserDatabaseInstance;

    /**
     * @var \Propel\Generator\Reverse\SchemaParserInterface
     */
    protected $parser;

    /**
     * @var \Propel\Generator\Model\Database
     */
    protected $parsedDatabase;

    /**
     * @abstract
     *
     * @return string
     */
    abstract protected function getSchemaParserClass(): string;

    /**
     * @abstract
     *
     * @return string
     */
    abstract protected function getDriverName(): string;

    /**
     * @return void
     */
    protected function init(): void
    {
        $database = new Database();
        $database->setPlatform(new DefaultPlatform());

        $this->parser->parse($database);

        static::$parserDatabaseInstance = $database;
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $expectedDriverName = $this->getDriverName();
        $currentDriverName = $this->con->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($currentDriverName !== $expectedDriverName) {
            $this->markTestSkipped("This test is designed for $expectedDriverName and cannot be run with $currentDriverName");
        }

        $parserClass = $this->getSchemaParserClass();
        $this->parser = new $parserClass($this->con);
        $this->parser->setGeneratorConfig(new QuickGeneratorConfig());

        if (!static::$parserDatabaseInstance) {
            $this->init();
        }

        $this->parsedDatabase = static::$parserDatabaseInstance;
    }
}
