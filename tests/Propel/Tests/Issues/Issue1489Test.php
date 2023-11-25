<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnComparator;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Reverse\MysqlSchemaParser;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel/issues/1489.
 *
 * @group mysql
 * @group database
 */
class Issue1489Test extends TestCase
{
    protected function buildReversedColumn(string $type){
      $database = new Database('le_database', new MysqlPlatform());
      $table = new Table('le_table');
      $table->setDatabase($database);
      $parser = new MysqlSchemaParser();
      $columnRow = [
        'Field' => 'le_column', 
        'Type' => $type,
        'Null' => 'YES', 
        'Key' => '',
        'Default' => null,
        'Extra' => ''
      ];
      return $parser->getColumnFromRow($columnRow, $table);
    }

    protected function buildSchemaColumn(string $type){
      $schema = '
      <database name="le_database" defaultIdMethod="native">
          <table name="le_table">
            <column name="le_column" type="CLOB" sqlType="'. strtoupper($type).'"/>
          </table>
      </database>
      ';

      $quickBuilder = new QuickBuilder();
      $quickBuilder->setSchema($schema);
      $quickBuilder->setIdentifierQuoting(true);
      $quickBuilder->setPlatform(new MysqlPlatform());

      $database = $quickBuilder->getDatabase();
      return $database->getTable('le_table')->getColumn('le_column');
    }

    public function sizedTypes(){
      return [
        ['tinytext'],
        ['mediumtext'],
        ['longtext'],
        ['tinyblob'],
        ['mediumblob'],
        ['longblob'],
      ];
    }

    /**
     * @dataProvider sizedTypes
     */
    public function testCompare(string $type){
      $schemaColumn = $this->buildSchemaColumn($type);
      $reversedColumn = $this->buildReversedColumn($type);
      $diff = ColumnComparator::compareColumns($schemaColumn, $reversedColumn);
      $this->assertEquals([], $diff);
    }
}
