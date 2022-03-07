<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Builder\Om\TableMapBuilder;
use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Table;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Behavior\Map\Table1TableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Test class for TableMapBuilder.
 *
 * @author François Zaninotto
 *
 * @group database
 */
class TableMapBuilderTest extends BookstoreTestBase
{
    /**
     * @var \Propel\Runtime\Map\DatabaseMap
     */
    protected $databaseMap;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseMap = Propel::getServiceContainer()->getDatabaseMap('bookstore');
    }

    /**
     * @return void
     */
    public function testColumnDefaultValue()
    {
        $table = $this->databaseMap->getTableByPhpName('\Propel\Tests\Bookstore\BookstoreEmployeeAccount');
        $this->assertNull($table->getColumn('login')->getDefaultValue(), 'null default values are correctly mapped');
        $this->assertEquals(
            '\'@\'\'34"',
            $table->getColumn('password')->getDefaultValue(),
            'string default values are correctly escaped and mapped'
        );
        $this->assertTrue(
            $table->getColumn('enabled')->getDefaultValue(),
            'boolean default values are correctly mapped'
        );
        $this->assertFalse(
            $table->getColumn('not_enabled')->getDefaultValue(),
            'boolean default values are correctly mapped'
        );
        $this->assertEquals(
            'CURRENT_TIMESTAMP',
            $table->getColumn('created')->getDefaultValue(),
            'expression default values are correctly mapped'
        );
        $this->assertNull(
            $table->getColumn('role_id')->getDefaultValue(),
            'explicit null default values are correctly mapped'
        );
    }

    /**
     * @return void
     */
    public function testRelationCount()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(
            13,
            count($bookTable->getRelations()),
            'The map builder creates relations for both incoming and outgoing keys'
        );
    }

    /**
     * @return void
     */
    public function testSimpleRelationName()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertTrue(
            $bookTable->hasRelation('Publisher'),
            'The map builder creates relations based on the foreign table name, camelized'
        );
        $this->assertTrue(
            $bookTable->hasRelation('BookListRel'),
            'The map builder creates relations based on the foreign table phpName, if provided'
        );
    }

    /**
     * @return void
     */
    public function testAliasRelationName()
    {
        $bookEmpTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployee');
        $this->assertTrue(
            $bookEmpTable->hasRelation('Supervisor'),
            'The map builder creates relations based on the foreign key phpName'
        );
        $this->assertTrue(
            $bookEmpTable->hasRelation('Subordinate'),
            'The map builder creates relations based on the foreign key refPhpName'
        );
    }

    /**
     * @return void
     */
    public function testDuplicateRelationName()
    {
        $essayTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Essay');
        $this->assertTrue(
            $essayTable->hasRelation('FirstAuthor'),
            'The map builder creates relations based on the foreign table name and the foreign key'
        );
        $this->assertTrue(
            $essayTable->hasRelation('SecondAuthor'),
            'The map builder creates relations based on the foreign table name and the foreign key'
        );
    }

    /**
     * @return void
     */
    public function testRelationDirectionManyToOne()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(
            RelationMap::MANY_TO_ONE,
            $bookTable->getRelation('Publisher')->getType(),
            'The map builder creates MANY_TO_ONE relations for every foreign key'
        );
        $this->assertEquals(
            RelationMap::MANY_TO_ONE,
            $bookTable->getRelation('Author')->getType(),
            'The map builder creates MANY_TO_ONE relations for every foreign key'
        );
    }

    /**
     * @return void
     */
    public function testRelationDirectionOneToMany()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(
            RelationMap::ONE_TO_MANY,
            $bookTable->getRelation('Review')->getType(),
            'The map builder creates ONE_TO_MANY relations for every incoming foreign key'
        );
        $this->assertEquals(
            RelationMap::ONE_TO_MANY,
            $bookTable->getRelation('Media')->getType(),
            'The map builder creates ONE_TO_MANY relations for every incoming foreign key'
        );
        $this->assertEquals(
            RelationMap::ONE_TO_MANY,
            $bookTable->getRelation('BookListRel')->getType(),
            'The map builder creates ONE_TO_MANY relations for every incoming foreign key'
        );
        $this->assertEquals(
            RelationMap::ONE_TO_MANY,
            $bookTable->getRelation('BookOpinion')->getType(),
            'The map builder creates ONE_TO_MANY relations for every incoming foreign key'
        );
        $this->assertEquals(
            RelationMap::ONE_TO_MANY,
            $bookTable->getRelation('ReaderFavorite')->getType(),
            'The map builder creates ONE_TO_MANY relations for every incoming foreign key'
        );
        $this->assertEquals(
            RelationMap::ONE_TO_MANY,
            $bookTable->getRelation('BookstoreContest')->getType(),
            'The map builder creates ONE_TO_MANY relations for every incoming foreign key'
        );
    }

    /**
     * @return void
     */
    public function testRelationDirectionOneToOne()
    {
        $bookEmpTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployee');
        $this->assertEquals(
            RelationMap::ONE_TO_ONE,
            $bookEmpTable->getRelation('BookstoreEmployeeAccount')->getType(),
            'The map builder creates ONE_TO_ONE relations for every incoming foreign key to a primary key'
        );
    }

    /**
     * @return void
     */
    public function testRelationDirectionManyToMAny()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(
            RelationMap::MANY_TO_MANY,
            $bookTable->getRelation('BookClubList')->getType(),
            'The map builder creates MANY_TO_MANY relations for every cross key'
        );
    }

    /**
     * @return void
     */
    public function testRelationsColumns()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $expectedMapping = ['book.publisher_id' => 'publisher.id'];
        $this->assertEquals(
            $expectedMapping,
            $bookTable->getRelation('Publisher')->getColumnMappings(),
            'The map builder adds columns in the correct order for foreign keys'
        );
        $expectedMapping = ['review.book_id' => 'book.id'];
        $this->assertEquals(
            $expectedMapping,
            $bookTable->getRelation('Review')->getColumnMappings(),
            'The map builder adds columns in the correct order for incoming foreign keys'
        );
        $publisherTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Publisher');
        $expectedMapping = ['book.publisher_id' => 'publisher.id'];
        $this->assertEquals(
            $expectedMapping,
            $publisherTable->getRelation('Book')->getColumnMappings(),
            'The map builder adds local columns where the foreign key lies'
        );
        $rfTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\ReaderFavorite');
        $expectedMapping = [
            'reader_favorite.book_id' => 'book_opinion.book_id',
            'reader_favorite.reader_id' => 'book_opinion.reader_id',
        ];
        $this->assertEquals(
            $expectedMapping,
            $rfTable->getRelation('BookOpinion')->getColumnMappings(),
            'The map builder adds all columns for composite foreign keys'
        );
        $expectedMapping = [];
        $this->assertEquals(
            $expectedMapping,
            $bookTable->getRelation('BookClubList')->getColumnMappings(),
            'The map builder provides no column mapping for many-to-many relationships'
        );
    }

    /**
     * @return void
     */
    public function testRelationOnDelete()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(
            'SET NULL',
            $bookTable->getRelation('Publisher')->getOnDelete(),
            'The map builder adds columns with the correct onDelete'
        );
    }

    /**
     * @return void
     */
    public function testRelationOnUpdate()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertNull(
            $bookTable->getRelation('Publisher')->getOnUpdate(),
            'The map builder adds columns with onDelete null by default'
        );
        $this->assertEquals(
            'CASCADE',
            $bookTable->getRelation('Author')->getOnUpdate(),
            'The map builder adds columns with the correct onUpdate'
        );
    }

    /**
     * @return void
     */
    public function testBehaviors()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertEquals(
            $bookTable->getBehaviors(),
            [],
            'getBehaviors() returns an empty array when no behaviors are registered'
        );

        //this init tableMap for class 'Propel\Tests\Bookstore\Behavior\Table1'
        Propel::getServiceContainer()->getDatabaseMap(Table1TableMap::DATABASE_NAME)->getTableByPhpName(
            'Propel\Tests\Bookstore\Behavior\Table1'
        );

        $tmap = Propel::getServiceContainer()->getDatabaseMap(Table1TableMap::DATABASE_NAME)->getTable(
            Table1TableMap::TABLE_NAME
        );
        $expectedBehaviorParams = [
            'timestampable' => [
                'create_column' => 'created_on',
                'update_column' => 'updated_on',
                'disable_created_at' => 'false',
                'disable_updated_at' => 'false',
            ],
        ];
        $this->assertEquals(
            $tmap->getBehaviors(),
            $expectedBehaviorParams,
            'The map builder creates a getBehaviors() method to retrieve behaviors parameters when behaviors are registered'
        );
    }

    /**
     * @return void
     */
    public function testSingleTableInheritance()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertFalse(
            $bookTable->isSingleTableInheritance(),
            'isSingleTabkeInheritance() returns false by default'
        );

        $empTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookstoreEmployee');
        $this->assertTrue(
            $empTable->isSingleTableInheritance(),
            'isSingleTabkeInheritance() returns true for tables using single table inheritance'
        );
    }

    /**
     * @return void
     */
    public function testPrimaryString()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertTrue($bookTable->hasPrimaryStringColumn(), 'The map builder adds primaryString columns.');
        $this->assertEquals(
            $bookTable->getColumn('TITLE'),
            $bookTable->getPrimaryStringColumn(),
            'The map builder maps the correct column as primaryString.'
        );
    }

    /**
     * @return void
     */
    public function testIsCrossRef()
    {
        $bookTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\Book');
        $this->assertFalse($bookTable->isCrossRef(), 'The map builder add isCrossRef information "false"');
        $BookListRelTable = $this->databaseMap->getTableByPhpName('Propel\Tests\Bookstore\BookListRel');
        $this->assertTrue($BookListRelTable->isCrossRef(), 'The map builder add isCrossRef information "true"');
    }

    /**
     * @return void
     */
    public function testNormalizedColumnMapHasOnlyUniqueKeys()
    {
        $databaseXml = '
<database>
    <table name="email">
        <column name="id" type="integer"/>
        <column name="email_address" type="varchar"/>
    </table>
</database>
';
        $reader = new SchemaReader();
        $schema = $reader->parseString($databaseXml);
        $table = $schema->getDatabase()->getTable('email');

        $tableMapBuilder = new class ($table) extends TableMapBuilder {
            public function getNormalizedColumnNameMapDefinition(): string
            {
                $script = '';
                $this->addNormalizedColumnNameMap($script);

                return $script;
            }
        };
        $tableMapBuilder->setGeneratorConfig(new QuickGeneratorConfig());
        $normalizedColumnMapDefinition = $tableMapBuilder->getNormalizedColumnNameMapDefinition();

        // extract inner part of the array
        $this->assertEquals(1, preg_match('/= \[\n(.*)\n\s*\]/ms', $normalizedColumnMapDefinition, $matches));

        // split, then check for number of lines -- so that we are sure nothing vanishes
        $list = explode(PHP_EOL, $matches[1]);
        $this->assertCount(14, $list);

        // check for uniqueness -> unique list has to be the same size
        $this->assertCount(14, array_unique($list));
    }

    /**
     * @return array
     */
    public function stringifyDataProvider(): array
    {
        return [
            [1, 'int should stay int'],
            [3.14, 'float should stay float'],
            [null, 'null should stay null'],
            [true, 'bool should stay bool'],
            ['literal', 'string should stay string'],
            ['', 'empty string should stay string'],
            [' ', 'space should stay string'],
            ["\n", 'new line should stay string'],
            ['\'quoted literal\'', 'quotes should be escaped'],
            [[1, 2, 3], 'array should stay array'],
            [['nr1' => 1, 'nr2' => 2], 'array indexes should remain'],
            [[1, 3.14, null, true, 'literal'], 'array types should not change'],
            [[null, 'nested' => [1, true], [2.71, 'arr' => ['any']]], 'nested arrays should work too'],
        ];
    }

    /**
     * @dataProvider stringifyDataProvider
     *
     * @param bool|int|float|string|array|null $scalarData
     * @param string $message
     *
     * @return void
     */
    public function testStringify($scalarData, string $message): void
    {
        $builder = new class (new Table('any')) extends TableMapBuilder{
            public function doStringify($value): string
            {
                return $this->stringify($value);
            }
        };
        $stringifiedData = $builder->doStringify($scalarData);
        eval("\$restoredData = $stringifiedData;");

        $this->assertSame($scalarData, $restoredData, $message);
    }

    /**
     * @return void
     */
    public function testGetOMClassDefaultInstantiable()
    {
        $databaseXml = <<<XML
<database namespace="ExampleNamespace\Greens" package="Greens">
    <table name="green_thing">
        <column name="id" type="integer"/>
        <column
            name="type"
            type="enum"
            required="true"
            default="default"
            valueSet="default,grass"
            inheritance="single"
        >
            <inheritance key="default" class="GreenThing"/>
            <inheritance key="grass" class="Grass" extends="GreenThing" />
        </column>
    </table>
</database>
XML;
        $builder = new QuickBuilder();
        $builder->setSchema($databaseXml);
        $builder->build();

        $this->assertTrue(\class_exists('\\ExampleNamespace\\Greens\\Map\\GreenThingTableMap'));
        $this->assertTrue(\class_exists('\\ExampleNamespace\\Greens\\Grass'));

        $unexpectedClassName = \ExampleNamespace\Greens\Map\GreenThingTableMap::getOMClass(
            array(
                2, // random 'ID' value
                'othervalue', // enable the 'default' case in getOMClass
            ),
            0, // somehow the offset is calculated within the getOMClass function (?)
            false
        );
        $this->assertInstanceOf('\\ExampleNamespace\\Greens\\GreenThing', new $unexpectedClassName());


        $grassClass = \ExampleNamespace\Greens\Map\GreenThingTableMap::getOMClass(
            array(
                2, // random 'ID' value
                // enable the 'grass' case
                \ExampleNamespace\Greens\Map\GreenThingTableMap::COL_TYPE_GRASS,
            ),
            0, // somehow the offset is calculated within the getOMClass function (?)
            false
        );
        $this->assertInstanceOf('\\ExampleNamespace\\Greens\\Grass', new $grassClass());

        $greenClass = \ExampleNamespace\Greens\Map\GreenThingTableMap::getOMClass(
            array(
                2, // random 'ID' value
                // enable the 'default' case
                \ExampleNamespace\Greens\Map\GreenThingTableMap::COL_TYPE_DEFAULT,
            ),
            0, // somehow the offset is calculated within the getOMClass function (?)
            false
        );
        $this->assertInstanceOf('\\ExampleNamespace\\Greens\\GreenThing', new $greenClass());
    }
}
