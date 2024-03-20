<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Reverse;

use PDO;
use Propel\Generator\Model\ColumnDefaultValue;
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
}
