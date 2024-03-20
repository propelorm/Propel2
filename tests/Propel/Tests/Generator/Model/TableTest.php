<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;

/**
 * Unit test suite for Table model class.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class TableTest extends ModelTestCase
{
    /**
     * @return void
     */
    public function testCreateNewTable()
    {
        $table = new Table('books');

        $this->assertSame('books', $table->getCommonName());
        $this->assertFalse($table->isAllowPkInsert());
        $this->assertFalse($table->isCrossRef());
        $this->assertFalse($table->isReloadOnInsert());
        $this->assertFalse($table->isReloadOnUpdate());
        $this->assertFalse($table->isSkipSql());
        $this->assertFalse($table->isReadOnly());
        $this->assertSame(0, $table->getNumLazyLoadColumns());
        $this->assertNull($table->getChildrenNames());
        $this->assertFalse($table->hasForeignKeys());
    }

    /**
     * @dataProvider provideNamespaces
     *
     * @return void
     */
    public function testSetNamespace($namespace, $expected)
    {
        $table = new Table('');
        $table->setNamespace($namespace);

        $this->assertSame($expected, $table->getNamespace());
    }

    public function provideNamespaces()
    {
        return [
            ['\Acme', '\Acme'],
            ['Acme', 'Acme'],
            ['Acme\\', 'Acme'],
            ['\Acme\Model', '\Acme\Model'],
            ['Acme\Model', 'Acme\Model'],
            ['Acme\Model\\', 'Acme\Model'],
        ];
    }

    /**
     * @return void
     */
    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();
        $database = $this->getDatabaseMock('foo');

        $database
            ->expects($this->once())
            ->method('getGeneratorConfig')
            ->will($this->returnValue($config));

        $table = new Table('');
        $table->setDatabase($database);

        $this->assertSame($config, $table->getGeneratorConfig());
    }

    /**
     * @return void
     */
    public function testGetBuildProperty()
    {
        $table = new Table('');
        $this->assertEmpty($table->getBuildProperty('propel.foo.bar'));

        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getBuildProperty')
            ->with('propel.foo.bar')
            ->will($this->returnValue('baz'));

        $table->setDatabase($database);
        $this->assertSame('baz', $table->getBuildProperty('propel.foo.bar'));
    }

    /**
     * @return void
     */
    public function testApplyBehaviors()
    {
        $behavior = $this->getBehaviorMock('foo');
        $behavior
            ->expects($this->once())
            ->method('isTableModified')
            ->will($this->returnValue(false));

        $behavior
            ->expects($this->once())
            ->method('getTableModifier')
            ->will($this->returnValue($behavior));

        $behavior
            ->expects($this->once())
            ->method('modifyTable');

        $behavior
            ->expects($this->once())
            ->method('setTableModified')
            ->with($this->equalTo(true));

        $table = new Table('');
        $table->addBehavior($behavior);
        $table->applyBehaviors();
    }

    /**
     * @return void
     */
    public function testGetAdditionalBuilders()
    {
        $additionalBehaviors = [
            $this->getBehaviorMock('foo'),
            $this->getBehaviorMock('bar'),
            $this->getBehaviorMock('baz'),
        ];

        $behavior = $this->getBehaviorMock('mix', [
            'additional_builders' => $additionalBehaviors,
        ]);

        $table = new Table('');
        $table->addBehavior($behavior);

        $this->assertCount(3, $table->getAdditionalBuilders());
        $this->assertTrue($table->hasAdditionalBuilders());
    }

    /**
     * @return void
     */
    public function testHasNoAdditionalBuilders()
    {
        $table = new Table('');
        $table->addBehavior($this->getBehaviorMock('foo'));

        $this->assertCount(0, $table->getAdditionalBuilders());
        $this->assertFalse($table->hasAdditionalBuilders());
    }

    /**
     * @return void
     */
    public function testGetColumnList()
    {
        $columns = [
            $this->getColumnMock('foo'),
            $this->getColumnMock('bar'),
            $this->getColumnMock('baz'),
        ];

        $table = new Table('');

        $this->assertSame('foo|bar|baz', $table->getColumnList($columns, '|'));
    }

    /**
     * @return void
     */
    public function testGetNameWithoutPlatform()
    {
        $table = new Table('books');

        $this->assertSame('books', $table->getName());
    }

    /**
     * @dataProvider provideSchemaNames
     *
     * @return void
     */
    public function testGetNameWithPlatform($supportsSchemas, $schemaName, $expectedName)
    {
        $database = $this->getDatabaseMock($schemaName, [
            'platform' => $this->getPlatformMock($supportsSchemas),
        ]);

        $database
            ->expects($supportsSchemas ? $this->once() : $this->never())
            ->method('getSchemaDelimiter')
            ->will($this->returnValue('.'));

        $table = new Table('books');
        $table->setSchema($schemaName);
        $table->setDatabase($database);

        $this->assertSame($expectedName, $table->getName());
    }

    public function provideSchemaNames()
    {
        return [
            [false, 'bookstore', 'books'],
            [false, null, 'books'],
            [true, 'bookstore', 'bookstore.books'],
        ];
    }

    /**
     * @return void
     */
    public function testSetDefaultPhpName()
    {
        $table = new Table('created_at');

        $this->assertSame('CreatedAt', $table->getPhpName());
        $this->assertSame('createdAt', $table->getCamelCaseName());
    }

    /**
     * @return void
     */
    public function testSetCustomPhpName()
    {
        $table = new Table('created_at');
        $table->setPhpName('CreatedAt');

        $this->assertSame('CreatedAt', $table->getPhpName());
        $this->assertSame('createdAt', $table->getCamelCaseName());
    }

    /**
     * @return void
     */
    public function testSetDescription()
    {
        $table = new Table('');

        $this->assertFalse($table->hasDescription());

        $table->setDescription('Some description');
        $this->assertTrue($table->hasDescription());
        $this->assertSame('Some description', $table->getDescription());
    }

    /**
     * @return void
     */
    public function testSetInvalidDefaultStringFormat()
    {
        $this->expectException(InvalidArgumentException::class);

        $table = new Table('');
        $table->setDefaultStringFormat('FOO');
    }

    /**
     * @return void
     */
    public function testGetDefaultStringFormatFromDatabase()
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getDefaultStringFormat')
            ->will($this->returnValue('XML'));

        $table = new Table('');
        $table->setDatabase($database);

        $this->assertSame('XML', $table->getDefaultStringFormat());
    }

    /**
     * @dataProvider provideStringFormats
     *
     * @return void
     */
    public function testGetDefaultStringFormat($format)
    {
        $table = new Table('');
        $table->setDefaultStringFormat($format);

        $this->assertSame($format, $table->getDefaultStringFormat());
    }

    public function provideStringFormats()
    {
        return [
            ['XML'],
            ['YAML'],
            ['JSON'],
            ['CSV'],
        ];
    }

    /**
     * @return void
     */
    public function testAddSameColumnTwice()
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at', ['phpName' => 'CreatedAt']);

        $this->expectException(EngineException::class);

        $table->addColumn($column);
        $table->addColumn($column);
    }

    /**
     * @return void
     */
    public function testGetChildrenNames()
    {
        $column = $this->getColumnMock('created_at', ['inheritance' => true]);

        $column
            ->expects($this->any())
            ->method('isEnumeratedClasses')
            ->will($this->returnValue(true));

        $children[] = $this->getMockBuilder('Propel\Generator\Model\Inheritance')->getMock();
        $children[] = $this->getMockBuilder('Propel\Generator\Model\Inheritance')->getMock();

        $column
            ->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue($children));

        $table = new Table('books');
        $table->addColumn($column);

        $names = $table->getChildrenNames();

        $this->assertCount(2, $names);
        $this->assertSame('Propel\Generator\Model\Inheritance', get_parent_class($names[0]));
        $this->assertSame('Propel\Generator\Model\Inheritance', get_parent_class($names[1]));
    }

    /**
     * @return void
     */
    public function testCantGetChildrenNames()
    {
        $column = $this->getColumnMock('created_at', ['inheritance' => true]);

        $column
            ->expects($this->any())
            ->method('isEnumeratedClasses')
            ->will($this->returnValue(false));

        $table = new Table('books');
        $table->addColumn($column);

        $this->assertNull($table->getChildrenNames());
    }

    /**
     * @return void
     */
    public function testAddInheritanceColumn()
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at', ['inheritance' => true]);

        $this->assertInstanceOf('Propel\Generator\Model\Column', $table->addColumn($column));
        $this->assertInstanceOf('Propel\Generator\Model\Column', $table->getChildrenColumn());
        $this->assertTrue($table->hasColumn($column, true));
        $this->assertTrue($table->hasColumn($column, false));
        $this->assertCount(1, $table->getColumns());
        $this->assertSame(1, $table->getNumColumns());
        $this->assertTrue($table->requiresTransactionInPostgres());
    }

    /**
     * @return void
     */
    public function testHasBehaviors()
    {
        $behavior1 = $this->getBehaviorMock('Foo');
        $behavior2 = $this->getBehaviorMock('Bar');
        $behavior3 = $this->getBehaviorMock('Baz');

        $table = new Table('');
        $table->addBehavior($behavior1);
        $table->addBehavior($behavior2);
        $table->addBehavior($behavior3);

        $this->assertCount(3, $table->getBehaviors());

        $this->assertTrue($table->hasBehavior('Foo'));
        $this->assertTrue($table->hasBehavior('Bar'));
        $this->assertTrue($table->hasBehavior('Baz'));
        $this->assertFalse($table->hasBehavior('Bab'));

        $this->assertSame($behavior1, $table->getBehavior('Foo'));
        $this->assertSame($behavior2, $table->getBehavior('Bar'));
        $this->assertSame($behavior3, $table->getBehavior('Baz'));
    }

    /**
     * @return void
     */
    public function testAddColumn()
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at');

        $this->assertInstanceOf('Propel\Generator\Model\Column', $table->addColumn($column));
        $this->assertNull($table->getChildrenColumn());
        $this->assertTrue($table->requiresTransactionInPostgres());
        $this->assertTrue($table->hasColumn($column));
        $this->assertTrue($table->hasColumn('CREATED_AT', true));
        $this->assertSame($column, $table->getColumnByPhpName('CreatedAt'));
        $this->assertCount(1, $table->getColumns());
        $this->assertSame(1, $table->getNumColumns());
    }

    /**
     * @return void
     */
    public function testCantRemoveColumnWhichIsNotInTable()
    {
        $this->expectException(EngineException::class);

        $column1 = $this->getColumnMock('title');

        $table = new Table('books');
        $table->removeColumn($column1);
    }

    /**
     * @return void
     */
    public function testRemoveColumnByName()
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);
        $table->removeColumn('title');

        $this->assertCount(2, $table->getColumns());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('isbn'));
        $this->assertFalse($table->hasColumn('title'));
    }

    /**
     * @return void
     */
    public function testRemoveColumn()
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);
        $table->removeColumn($column2);

        $this->assertCount(2, $table->getColumns());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('isbn'));
        $this->assertFalse($table->hasColumn('title'));
    }

    /**
     * @return void
     */
    public function testGetNumLazyLoadColumns()
    {
        $column1 = $this->getColumnMock('created_at');
        $column2 = $this->getColumnMock('updated_at', ['lazy' => true]);

        $column3 = $this->getColumnMock('deleted_at', ['lazy' => true]);

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertSame(2, $table->getNumLazyLoadColumns());
    }

    /**
     * @return void
     */
    public function testHasValueSetColumns()
    {
        $column1 = $this->getColumnMock('created_at');
        $column2 = $this->getColumnMock('updated_at');

        $column1
            ->expects($this->any())
            ->method('isValueSetType')
            ->will($this->returnValue(false));

        $column2
            ->expects($this->any())
            ->method('isValueSetType')
            ->will($this->returnValue(true));

        $table = new Table('books');

        $table->addColumn($column1);
        $this->assertFalse($table->hasValueSetColumns());

        $table->addColumn($column2);
        $this->assertTrue($table->hasValueSetColumns());
    }

    /**
     * @return void
     */
    public function testCantGetColumn()
    {
        $table = new Table('books');

        $this->assertFalse($table->hasColumn('FOO', true));
        $this->assertNull($table->getColumn('FOO'));
        $this->assertNull($table->getColumnByPhpName('Foo'));
    }

    /**
     * @return void
     */
    public function testSetAbstract()
    {
        $table = new Table('');
        $this->assertFalse($table->isAbstract());

        $table->setAbstract(true);
        $this->assertTrue($table->isAbstract());
    }

    /**
     * @return void
     */
    public function testSetInterface()
    {
        $table = new Table('');
        $table->setInterface('ActiveRecordInterface');

        $this->assertSame('ActiveRecordInterface', $table->getInterface());
    }

    /**
     * @return void
     */
    public function testAddIndex()
    {
        $table = new Table('');
        $index = new Index();
        $index->addColumn(['name' => 'bla']);
        $table->addIndex($index);

        $this->assertCount(1, $table->getIndices());
    }

    /**
     * @return void
     */
    public function testAddEmptyIndex()
    {
        $this->expectException(InvalidArgumentException::class);

        $table = new Table('');
        $table->addIndex(new Index());

        $this->assertCount(1, $table->getIndices());
    }

    /**
     * @return void
     */
    public function testAddArrayIndex()
    {
        $table = new Table('');
        $table->addIndex(['name' => 'author_idx', 'columns' => [['name' => 'bla']]]);

        $this->assertCount(1, $table->getIndices());
    }

    /**
     * @return void
     */
    public function testIsIndex()
    {
        $table = new Table('');
        $column1 = new Column('category_id');
        $column2 = new Column('type');
        $table->addColumn($column1);
        $table->addColumn($column2);

        $index = new Index('test_index');
        $index->setColumns([$column1, $column2]);
        $table->addIndex($index);

        $this->assertTrue($table->isIndex(['category_id', 'type']));
        $this->assertTrue($table->isIndex(['type', 'category_id']));
        $this->assertFalse($table->isIndex(['category_id', 'type2']));
        $this->assertFalse($table->isIndex(['asd']));
    }

    /**
     * @return void
     */
    public function testAddUniqueIndex()
    {
        $table = new Table('');
        $table->addUnique($this->getUniqueIndexMock('author_unq'));

        $this->assertCount(1, $table->getUnices());
    }

    /**
     * @return void
     */
    public function testAddArrayUnique()
    {
        $table = new Table('');
        $table->addUnique(['name' => 'author_unq']);

        $this->assertCount(1, $table->getUnices());
    }

    /**
     * @return void
     */
    public function testGetCompositePrimaryKey()
    {
        $column1 = $this->getColumnMock('book_id', ['primary' => true]);
        $column2 = $this->getColumnMock('author_id', ['primary' => true]);
        $column3 = $this->getColumnMock('rank');

        $table = new Table('');
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(2, $table->getPrimaryKey());
        $this->assertFalse($table->hasAutoIncrementPrimaryKey());
        $this->assertNull($table->getAutoIncrementPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertTrue($table->hasCompositePrimaryKey());
        $this->assertSame($column1, $table->getFirstPrimaryKeyColumn());
    }

    /**
     * @return void
     */
    public function testGetSinglePrimaryKey()
    {
        $column1 = $this->getColumnMock('id', ['primary' => true]);
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('');
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(1, $table->getPrimaryKey());
        $this->assertFalse($table->hasAutoIncrementPrimaryKey());
        $this->assertNull($table->getAutoIncrementPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertFalse($table->hasCompositePrimaryKey());
        $this->assertSame($column1, $table->getFirstPrimaryKeyColumn());
    }

    /**
     * @return void
     */
    public function testGetNoPrimaryKey()
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('');
        $table->setIdMethod('none');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(0, $table->getPrimaryKey());
        $this->assertFalse($table->hasAutoIncrementPrimaryKey());
        $this->assertNull($table->getAutoIncrementPrimaryKey());
        $this->assertFalse($table->hasPrimaryKey());
        $this->assertFalse($table->hasCompositePrimaryKey());
        $this->assertNull($table->getFirstPrimaryKeyColumn());
    }

    /**
     * @return void
     */
    public function testGetAutoIncrementPrimaryKey()
    {
        $column1 = $this->getColumnMock('id', [
            'primary' => true,
            'auto_increment' => true,
        ]);

        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('');
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(1, $table->getPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertTrue($table->hasAutoIncrementPrimaryKey());
        $this->assertSame($column1, $table->getAutoIncrementPrimaryKey());
    }

    /**
     * @return void
     */
    public function testAddIdMethodParameter()
    {
        $parameter = $this
            ->getMockBuilder('Propel\Generator\Model\IdMethodParameter')
            ->disableOriginalConstructor()
            ->getMock();
        $parameter
            ->expects($this->once())
            ->method('setTable');

        $table = new Table('');
        $table->addIdMethodParameter($parameter);

        $this->assertCount(1, $table->getIdMethodParameters());
    }

    /**
     * @return void
     */
    public function testAddArrayIdMethodParameter()
    {
        $table = new Table('');
        $table->addIdMethodParameter(['name' => 'foo', 'value' => 'bar']);

        $this->assertCount(1, $table->getIdMethodParameters());
    }

    /**
     * @return void
     */
    public function testAddReferrerForeignKey()
    {
        $table = new Table('books');
        $table->addReferrer($this->getForeignKeyMock());

        $this->assertCount(1, $table->getReferrers());
    }

    /**
     * @return void
     */
    public function testAddForeignKey()
    {
        $fk = $this->getForeignKeyMock('fk_author_id', [
            'foreign_table_name' => 'authors',
        ]);

        $table = new Table('books');

        $this->assertInstanceOf('Propel\Generator\Model\ForeignKey', $table->addForeignKey($fk));
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertTrue($table->hasForeignKeys());
        $this->assertContains('authors', $table->getForeignTableNames());
    }

    /**
     * @return void
     */
    public function testAddArrayForeignKey()
    {
        $table = new Table('books');
        $table->setDatabase($this->getDatabaseMock('bookstore'));

        $fk = $table->addForeignKey([
            'name' => 'fk_author_id',
            'phpName' => 'Author',
            'refPhpName' => 'Books',
            'onDelete' => 'CASCADE',
            'foreignTable' => 'authors',
        ]);

        $this->assertInstanceOf('Propel\Generator\Model\ForeignKey', $fk);
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertTrue($table->hasForeignKeys());

        $this->assertContains('authors', $table->getForeignTableNames());
    }

    /**
     * @return void
     */
    public function testGetForeignKeysReferencingTable()
    {
        $fk1 = $this->getForeignKeyMock('fk1', ['foreign_table_name' => 'authors']);
        $fk2 = $this->getForeignKeyMock('fk2', ['foreign_table_name' => 'categories']);
        $fk3 = $this->getForeignKeyMock('fk3', ['foreign_table_name' => 'authors']);

        $table = new Table('');
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);
        $table->addForeignKey($fk3);

        $this->assertCount(2, $table->getForeignKeysReferencingTable('authors'));
    }

    /**
     * @return void
     */
    public function testGetForeignKeysReferencingTableMoreThenOnce()
    {
        $fk1 = $this->getForeignKeyMock('fk1', ['foreign_table_name' => 'authors']);
        $fk2 = $this->getForeignKeyMock('fk2', ['foreign_table_name' => 'categories']);
        $fk3 = $this->getForeignKeyMock('fk1', ['foreign_table_name' => 'authors']);

        $table = new Table('');
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);

        $this->expectException(EngineException::class);
        $table->addForeignKey($fk3);
        $this->fail('Expected to throw an EngineException due to duplicate foreign key.');
    }

    /**
     * @return void
     */
    public function testGetColumnForeignKeys()
    {
        $fk1 = $this->getForeignKeyMock('fk1', [
            'local_columns' => ['foo', 'author_id', 'bar'],
        ]);

        $fk2 = $this->getForeignKeyMock('fk2', [
            'local_columns' => ['foo', 'bar'],
        ]);

        $table = new Table('');
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);

        $this->assertCount(1, $table->getColumnForeignKeys('author_id'));
        $this->assertContains($fk1, $table->getColumnForeignKeys('author_id'));
    }

    /**
     * return array
     */
    public function baseClassDataProvider(): array
    {
        return [
            // [<Class name>, <Expected class name>, <message>]]
            ['\CustomBaseQueryObject', '\CustomBaseQueryObject', 'Setter should set base query class'],
            ['CustomBaseQueryObject', '\CustomBaseQueryObject', 'Setter should set absolute namespace of base query class'],
        ];
    }

    /**
     * @dataProvider baseClassDataProvider
     *
     * @return void
     */
    public function testSetBaseClass(string $className, string $expectedClassName, string $message)
    {
        $table = new Table('');
        $table->setBaseClass($className);

        $this->assertSame($expectedClassName, $table->getBaseClass(), $message);
    }

    /**
     * @dataProvider baseClassDataProvider
     *
     * @return void
     */
    public function testSetBaseQueryClass(string $className, string $expectedClassName, string $message)
    {
        $table = new Table('');
        $table->setBaseQueryClass($className);

        $this->assertSame($expectedClassName, $table->getBaseQueryClass(), $message);
    }

    /**
     * @return void
     */
    public function testGetBaseClassesFromDatabase()
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getBaseClass')
            ->will($this->returnValue('BaseObject'));

        $table = new Table('');
        $table->setDatabase($database);

        $this->assertSame('BaseObject', $table->getBaseClass());
    }

    /**
     * @return void
     */
    public function testGetBaseClassesWithAlias()
    {
        $table = new Table('books');
        $table->setAlias('Book');

        $this->assertSame('Book', $table->getBaseClass());
    }

    /**
     * @return void
     */
    public function testSetAlias()
    {
        $table = new Table('books');

        $this->assertFalse($table->isAlias());

        $table->setAlias('Book');
        $this->assertTrue($table->isAlias());
        $this->assertSame('Book', $table->getAlias());
    }

    /**
     * @return void
     */
    public function testTablePrefix()
    {
        $database = new Database();
        $database->loadMapping([
            'name' => 'bookstore',
            'defaultIdMethod' => 'native',
            'defaultPhpNamingMethod' => 'underscore',
            'tablePrefix' => 'acme_',
            'defaultStringFormat' => 'XML',
        ]);

        $table = new Table('');
        $database->addTable($table);
        $table->loadMapping([
           'name' => 'books',
        ]);
        $this->assertEquals('Books', $table->getPhpName());
        $this->assertEquals('acme_books', $table->getCommonName());
    }

    /**
     * @return void
     */
    public function testSetContainsForeignPK()
    {
        $table = new Table('');

        $table->setContainsForeignPK(true);
        $this->assertTrue($table->getContainsForeignPK());
    }

    /**
     * @return void
     */
    public function testSetCrossReference()
    {
        $table = new Table('books');

        $this->assertFalse($table->getIsCrossRef());
        $this->assertFalse($table->isCrossRef());

        $table->setIsCrossRef(true);
        $this->assertTrue($table->getIsCrossRef());
        $this->assertTrue($table->isCrossRef());
    }

    /**
     * @return void
     */
    public function testSetSkipSql()
    {
        $table = new Table('books');
        $table->setSkipSql(true);

        $this->assertTrue($table->isSkipSql());
    }

    /**
     * @return void
     */
    public function testSetForReferenceOnly()
    {
        $table = new Table('books');
        $table->setForReferenceOnly(true);

        $this->assertTrue($table->isForReferenceOnly());
    }

    /**
     * Returns a dummy Column object.
     *
     * @param string $name The column name
     * @param array $options An array of options
     *
     * @return \Propel\Generator\Model\Column
     */
    protected function getColumnMock($name, array $options = [])
    {
        $defaults = [
            'primary' => false,
            'auto_increment' => false,
            'inheritance' => false,
            'lazy' => false,
            'phpName' => str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))),
            'pg_transaction' => true,
        ];

        // Overwrite default options with custom options
        $options = array_merge($defaults, $options);

        $column = parent::getColumnMock($name, $options);

        $column
            ->expects($this->any())
            ->method('setTable');

        $column
            ->expects($this->any())
            ->method('setPosition');

        $column
            ->expects($this->any())
            ->method('isPrimaryKey')
            ->will($this->returnValue($options['primary']));

        $column
            ->expects($this->any())
            ->method('isAutoIncrement')
            ->will($this->returnValue($options['auto_increment']));

        $column
            ->expects($this->any())
            ->method('isInheritance')
            ->will($this->returnValue($options['inheritance']));

        $column
            ->expects($this->any())
            ->method('isLazyLoad')
            ->will($this->returnValue($options['lazy']));

        $column
            ->expects($this->any())
            ->method('getPhpName')
            ->will($this->returnValue($options['phpName']));

        $column
            ->expects($this->any())
            ->method('requiresTransactionInPostgres')
            ->will($this->returnValue($options['pg_transaction']));

        return $column;
    }
}
