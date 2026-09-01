<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Reverse;

use PDO;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Database;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Generator\Reverse\MysqlSchemaParser;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Tests for Mysql database schema parser.
 *
 * @author William Durand
 *
 * @group database
 * @group mysql
 */
class MysqlSchemaParserTest extends AbstractSchemaParserTest
{
    /**
     * @return string
     */
    protected function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * @return string
     */
    protected function getSchemaParserClass(): string
    {
        return MysqlSchemaParser::class;
    }

    /**
     * @return void
     */
    public function testParseImportsAllTables(): void
    {
        $query = <<< EOT
SELECT table_name
FROM INFORMATION_SCHEMA.TABLES
WHERE table_schema=DATABASE()
AND table_name NOT LIKE 'propel_migration'
EOT;
        $expectedTableNames = $this->con->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);

        $importedTables = $this->parsedDatabase->getTables();
        $importedTableNames = array_map(function ($table) {
            return $table->getName();
        }, $importedTables);

        $this->assertEqualsCanonicalizing($expectedTableNames, $importedTableNames);
    }

    /**
     * @return void
     */
    public function testParseImportsBookTable(): void
    {
        $parsedBookTable = $this->parsedDatabase->getTable('book');
        $sourceBookTable = BookTableMap::getTableMap();

        $columns = $sourceBookTable->getColumns();
        $expectedNumberOfColumns = count($columns);
        $this->assertCount($expectedNumberOfColumns, $parsedBookTable->getColumns());
    }

    /**
     * @return void
     */
    public function testDescriptionsAreImported(): void
    {
        $bookTable = $this->parsedDatabase->getTable('book');
        $this->assertEquals('Book Table', $bookTable->getDescription());
        $this->assertEquals('Book Title', $bookTable->getColumn('title')->getDescription());
    }

    /**
     * @return void
     */
    public function testOnUpdateIsImported(): void
    {
        $onUpdateTable = $this->parsedDatabase->getTable('bookstore_employee_account');
        $updatedAtColumn = $onUpdateTable->getColumn('updated');
        $this->assertEquals(ColumnDefaultValue::TYPE_EXPR, $updatedAtColumn->getDefaultValue()->getType());
        $this->assertEquals('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $updatedAtColumn->getDefaultValue()->getValue());
    }

    /**
     * @return void
     */
    public function testTextDefaultValues(): void
    {
        $serverVersion = $this->con->getAttribute(PDO::ATTR_SERVER_VERSION);
        $isMariaDb = stripos($serverVersion, 'mariadb') !== false;

        if ($isMariaDb) {
            if (version_compare(preg_replace('/^.*?(\d+\.\d+\.\d+).*$/', '$1', $serverVersion), '10.2.1', '<')) {
                $this->markTestSkipped('TEXT columns with DEFAULT values require MariaDB 10.2.1+');
            }
        } else {
            if (version_compare($serverVersion, '8.0.13', '<')) {
                $this->markTestSkipped('TEXT columns with DEFAULT values require MySQL 8.0.13+');
            }
        }

        $this->con->exec('DROP TABLE IF EXISTS test_text_defaults');
        $this->con->exec("CREATE TABLE test_text_defaults (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            content_text TEXT DEFAULT ('hello text'),
            content_text_empty TEXT DEFAULT (''),
            content_text_not_null TEXT NOT NULL,
            content_text_none TEXT
        )");

        try {
            $parser = new MysqlSchemaParser($this->con);
            $parser->setGeneratorConfig(new QuickGeneratorConfig());

            $database = new Database();
            $database->setPlatform(new DefaultPlatform());
            $parser->parse($database);

            $table = $database->getTable('test_text_defaults');
            $this->assertNotNull($table, 'Table test_text_defaults should be parsed');

            // TEXT with default value
            $contentText = $table->getColumn('content_text');
            $this->assertNotNull($contentText, 'Column content_text should exist');
            $this->assertNotNull($contentText->getDefaultValue(), 'TEXT column default value should be preserved');
            $this->assertEquals(ColumnDefaultValue::TYPE_VALUE, $contentText->getDefaultValue()->getType());
            $this->assertEquals('hello text', $contentText->getDefaultValue()->getValue());

            // TEXT with empty string default
            $contentTextEmpty = $table->getColumn('content_text_empty');
            $this->assertNotNull($contentTextEmpty, 'Column content_text_empty should exist');
            $this->assertNotNull($contentTextEmpty->getDefaultValue(), 'TEXT column with empty default should be preserved');
            $this->assertEquals(ColumnDefaultValue::TYPE_VALUE, $contentTextEmpty->getDefaultValue()->getType());
            $this->assertEquals('', $contentTextEmpty->getDefaultValue()->getValue());

            // TEXT NOT NULL without default
            $contentTextNotNull = $table->getColumn('content_text_not_null');
            $this->assertNotNull($contentTextNotNull, 'Column content_text_not_null should exist');
            $this->assertNull($contentTextNotNull->getDefaultValue(), 'TEXT NOT NULL column without default should have no default');

            // TEXT without default (nullable, implicit DEFAULT NULL)
            $contentTextNone = $table->getColumn('content_text_none');
            $this->assertNotNull($contentTextNone, 'Column content_text_none should exist');
            $this->assertNull($contentTextNone->getDefaultValue(), 'TEXT column without explicit default should have no default');
        } finally {
            $this->con->exec('DROP TABLE IF EXISTS test_text_defaults');
        }
    }
}
