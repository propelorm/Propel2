<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\AggregateMultipleColumns;

use Exception;
use InvalidArgumentException;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Tests\Bookstore\Behavior\AggregateMultipleScore;
use Propel\Tests\Bookstore\Behavior\AggregateMultipleScoreGroup;
use Propel\Tests\Bookstore\Behavior\AggregateMultipleScoreGroupQuery;
use Propel\Tests\Bookstore\Behavior\AggregateMultipleScoreQuery;
use Propel\Tests\Bookstore\Behavior\Map\AggregateMultipleScoreGroupTableMap;
use Propel\Tests\Bookstore\Behavior\Map\AggregateMultipleScoreTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for AggregateColumnBehavior class.
 *
 * Uses the tables defined in tests/Fixtures/bookstore/behavior-aggregate-multiple-schema.xml
 *
 * @author Moritz Ringler
 *
 * @group database
 */
class AggregateMultipleColumnsBehaviorTest extends BookstoreTestBase
{
    /**
     * Behavior ID as set in schema. Needed to test if behavior was set on table and foreign table.
     *
     * @var string
     */
    private const AGGREGATION_NAME_IN_SCHEMA = 'score_group_aggregates';

    /**
     * List of column names defined in behavior.
     *
     * @var array
     */
    private const AGGREGATION_COLUMN_NAMES = ['TotalScore', 'NumberOfScores', 'AvgScore', 'MinScore', 'MaxScore', 'FirstScoreAt', 'LastScoreAt'];

    /**
     * Second behavior ID as set in schema. Secondary schema is used to detect naming collisions.
     *
     * @var string
     */
    private const SECONDARY_AGGREGATION_NAME_IN_SCHEMA = 'another_score_group_aggregates';

    /**
     * List of column names defined in behavior.
     *
     * @var array
     */
    private const SECONDARY_AGGREGATION_COLUMN_NAMES = ['TotalBigScore', 'NumberOfBigScores'];

    /**
     * @return void
     */
    public function testExceptionOnNoColumnConfiguration()
    {
        $behaviorSchemaDefinition = <<<EOF
<behavior name="aggregate_multiple_columns">
  <parameter name="foreign_table" value="leForeignTable"/>
  <parameter-list name="columns">
  </parameter-list>
</behavior>
EOF;
        $this->assertExceptionMessageDuringSchemaParsing(
            $behaviorSchemaDefinition,
            InvalidArgumentException::class,
            'At least one column is required in the \'aggregate_multiple_columns\' behavior definition in the \'table1\' table definition'
        );
    }

    /**
     * @return void
     */
    public function testExceptionOnMissingColumnNameInConfiguration()
    {
        $behaviorSchemaDefinition = <<<EOF
<behavior name="aggregate_multiple_columns">
  <parameter name="foreign_table" value="leForeignTable"/>
  <parameter-list name="columns">
    <parameter-list-item>
      <parameter name="expression" value="leExpression"/>
    </parameter-list-item>
  </parameter-list>
</behavior>
EOF;
        $this->assertExceptionMessageDuringSchemaParsing(
            $behaviorSchemaDefinition,
            InvalidArgumentException::class,
            'Parameter \'name\' is missing on a column in the \'aggregate_multiple_columns\' behavior definition in the \'table1\' table definition'
        );
    }

    /**
     * @return void
     */
    public function testExceptionOnMissingColumnExpressionInConfiguration()
    {
        $behaviorSchemaDefinition = <<<EOF
<behavior name="aggregate_multiple_columns">
  <parameter name="foreign_table" value="leForeignTable"/>
  <parameter-list name="columns">
    <parameter-list-item>
      <parameter name="column_name" value="leColumnName"/>
    </parameter-list-item>
  </parameter-list>
</behavior>
EOF;
        $this->assertExceptionMessageDuringSchemaParsing(
            $behaviorSchemaDefinition,
            InvalidArgumentException::class,
            'Parameter \'expression\' is missing on column leColumnName in the \'aggregate_multiple_columns\' behavior definition in the \'table1\' table definition'
        );
    }

    /**
     * @return void
     */
    public function testExceptionOnMissingForeignTableInConfiguration()
    {
        $behaviorSchemaDefinition = <<<EOF
<behavior name="aggregate_multiple_columns">
  <parameter-list name="columns">
    <parameter-list-item>
      <parameter name="column_name" value="leColumn"/>
      <parameter name="expression" value="leExpression"/>
    </parameter-list-item>
  </parameter-list>
</behavior>
EOF;
        $this->assertExceptionMessageDuringSchemaParsing(
            $behaviorSchemaDefinition,
            InvalidArgumentException::class,
            'You must define a \'foreign_table\' parameter in the \'aggregate_multiple_columns\' behavior definition in the \'table1\' table definition'
        );
    }

    /**
     * @param string $behaviorSchemaDefinition
     *
     * @return array behaviors
     */
    private function buildBehaviorFromSchema(string $behaviorSchemaDefinition): array
    {
        $schema = <<<EOF
<database name="test1">
  <table name="table1">
    $behaviorSchemaDefinition
  </table>
</database>
EOF;
        $schemaReader = new SchemaReader();
        $appData = $schemaReader->parseString($schema);
        $table = $appData->getDatabase('test1')->getTable('table1');

        return $table->getBehaviors();
    }

    /**
     * @param string $behaviorSchemaDefinition
     * @param string $exceptionClass
     * @param string $expectedMessage
     *
     * @throws \Exception
     *
     * @return void
     */
    private function assertExceptionMessageDuringSchemaParsing(string $behaviorSchemaDefinition, $exceptionClass, string $expectedMessage)
    {
        $this->expectException($exceptionClass);

        try {
            $this->buildBehaviorFromSchema($behaviorSchemaDefinition);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->assertEquals($expectedMessage, $msg);

            throw $e;
        }
    }

    /**
     * Test that the behavior was added to the table with all necessary columns.
     *
     * @return void
     */
    public function testTableConfiguration()
    {
        $scoreGroupTable = AggregateMultipleScoreGroupTableMap::getTableMap();

        $behaviors = $scoreGroupTable->getBehaviors();
        $this->assertArrayHasKey(self::AGGREGATION_NAME_IN_SCHEMA, $behaviors);

        $expectedNumberOfColumns = 1 + count(self::AGGREGATION_COLUMN_NAMES) + count(self::SECONDARY_AGGREGATION_COLUMN_NAMES);
        $this->assertCount($expectedNumberOfColumns, $scoreGroupTable->getColumns(), 'AggregateMultipleColumns did not add expected number of columns');

        $class = 'Propel\Tests\Bookstore\Behavior\AggregateMultipleScoreGroup';
        foreach (self::AGGREGATION_COLUMN_NAMES as $columnName) {
            $columnGetterName = 'get' . $columnName;
            $this->assertTrue(method_exists($class, $columnGetterName));
        }
        $this->assertTrue(method_exists($class, 'computeAggregatedColumnsFromAggregateMultipleScore'));
        $this->assertTrue(method_exists($class, 'computeAggregatedColumnsFromAggregateMultipleScore1'));
    }

    /**
     * Check that the foreign table was given the correct behavior.
     *
     * Note that the behavior itself is the regular AggregateColumnRelationBehavior, which is already tested and does not need
     * to be tested again.
     *
     * @return void
     */
    public function testForeignTableConfiguration()
    {
        $scoreTable = AggregateMultipleScoreTableMap::getTableMap();

        $behaviors = $scoreTable->getBehaviors();
        $foreignTableSuffix = 'aggregate_multiple_columns_relation_';

        $behaviorKey = $foreignTableSuffix . self::AGGREGATION_NAME_IN_SCHEMA;
        $this->assertArrayHasKey($behaviorKey, $behaviors);

        $secondaryBehaviorKey = $foreignTableSuffix . self::SECONDARY_AGGREGATION_NAME_IN_SCHEMA;
        $this->assertArrayHasKey($secondaryBehaviorKey, $behaviors);
    }

    /**
     * Test that the compute function returns values for all aggregation columns.
     *
     * @return void
     */
    public function testComputeReturnsResultForAllColumns()
    {
        $this->clearTables();
        $group = $this->insertScoreGroup();
        $computedResult = $group->computeAggregatedColumnsFromAggregateMultipleScore($this->con);

        $this->assertIsArray($computedResult, 'compute function did not return array');
        $this->assertCount(count(self::AGGREGATION_COLUMN_NAMES), $computedResult, 'Number of computed values does not match number of aggregate columns');
    }

    /**
     * Test that the aggregation values are set correctly after changing foreign table.
     *
     * @return void
     */
    public function testAggregateUpdate()
    {
        $this->clearTables();
        $group = $this->insertScoreGroup();
        $this->assertAggregates($group, null, null, null, null, null, null, 'creating empty group');

        $score1 = $this->insertScore($group, 10, '2021-02-25');
        $this->assertAggregates($group, 1, 10, 10, 10, '2021-02-25', '2021-02-25', 'inserting first score');

        $score2 = $this->insertScore($group, 20, '2021-02-24');
        $this->assertAggregates($group, 2, 30, 10, 20, '2021-02-24', '2021-02-25', 'inserting second score');

        $score2->setScore(30)->setScoredAt('2021-02-26')->save($this->con);
        $this->assertAggregates($group, 2, 40, 10, 30, '2021-02-25', '2021-02-26', 'updating second score');

        $score1->delete($this->con);
        $this->assertAggregates($group, 1, 30, 30, 30, '2021-02-26', '2021-02-26', 'deleting first score');

        $score2->delete($this->con);
        $this->assertAggregates($group, null, null, null, null, null, null, 'deleting second score');
    }

    /**
     * @return void
     */
    private function clearTables(): void
    {
        AggregateMultipleScoreQuery::create()->deleteAll($this->con);
        AggregateMultipleScoreGroupQuery::create()->deleteAll($this->con);
    }

    /**
     * Convenience method to create database object
     *
     * @return \Propel\Tests\Bookstore\Behavior\AggregateMultipleScoreGroup
     */
    private function insertScoreGroup(): AggregateMultipleScoreGroup
    {
        $group = new AggregateMultipleScoreGroup();
        $group->save($this->con);

        return $group;
    }

    /**
     * Convenience method to create database object
     *
     * @param \Propel\Tests\Bookstore\Behavior\AggregateMultipleScoreGroup $group
     * @param int $scoreValue
     * @param string|\Propel\Runtime\Validator\Constraints\Date|null $date
     *
     * @return \Propel\Tests\Bookstore\Behavior\AggregateMultipleScore
     */
    private function insertScore(AggregateMultipleScoreGroup $group, int $scoreValue, $date = null): AggregateMultipleScore
    {
        $score = new AggregateMultipleScore();
        $score->setAggregateMultipleScoreGroup($group)->setScore($scoreValue)->setScoredAt($date)->save($this->con);

        return $score;
    }

    /**
     * @return void
     */
    private function assertAggregates(
        AggregateMultipleScoreGroup $group,
        ?int $numberOfScores,
        ?int $scoreSum,
        ?int $minScore,
        ?int $maxScore,
        ?string $firstScoreAt,
        ?string $lastScoreAt,
        ?string $testDescription
    ) {
        $this->assertEquals($numberOfScores, $group->getNumberOfScores(), 'Number of scores did not match after ' . $testDescription);
        $this->assertEquals($scoreSum, $group->getTotalScore(), 'Score sum did not match after ' . $testDescription);
        $this->assertEquals($minScore, $group->getMinScore(), 'Min score did not match after ' . $testDescription);
        $this->assertEquals($maxScore, $group->getMaxScore(), 'Max score did not match after ' . $testDescription);
        $this->assertEquals($firstScoreAt, $group->getFirstScoreAt('Y-m-d'), 'Max date did not match after ' . $testDescription);
        $this->assertEquals($lastScoreAt, $group->getLastScoreAt('Y-m-d'), 'Min date did not match after ' . $testDescription);
    }
}
