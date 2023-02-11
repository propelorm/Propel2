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
        $parserClass = $this->getSchemaParserClass();
        $parser = new $parserClass($this->con);
        $parser->setGeneratorConfig(new QuickGeneratorConfig());

        $database = new Database();
        $database->setPlatform(new DefaultPlatform());

        $parser->parse($database);

        $this->parser = $parser;
        $this->parsedDatabase = $database;
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

        if ($this->parser === null) {
            $this->init();
        }
    }
}
