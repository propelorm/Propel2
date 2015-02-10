<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

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
     */
    public function testSetNamespace($namespace, $expected)
    {
        $table = new Table();
        $table->setNamespace($namespace);

        $this->assertSame($expected, $table->getNamespace());
    }

    public function provideNamespaces()
    {
        return array(
            array('\Acme', '\Acme'),
            array('Acme', 'Acme'),
            array('Acme\\', 'Acme'),
            array('\Acme\Model', '\Acme\Model'),
            array('Acme\Model', 'Acme\Model'),
            array('Acme\Model\\', 'Acme\Model'),
        );
    }

    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();
        $database = $this->getDatabaseMock('foo');

        $database
            ->expects($this->once())
            ->method('getGeneratorConfig')
            ->will($this->returnValue($config))
        ;

        $table = new Table();
        $table->setDatabase($database);

        $this->assertSame($config, $table->getGeneratorConfig());
    }

    public function testGetBuildProperty()
    {
        $table = new Table();
        $this->assertEmpty($table->getBuildProperty('propel.foo.bar'));

        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getBuildProperty')
            ->with('propel.foo.bar')
            ->will($this->returnValue('baz'))
        ;

        $table->setDatabase($database);
        $this->assertSame('baz', $table->getBuildProperty('propel.foo.bar'));
    }

    public function testApplyBehaviors()
    {
        $behavior = $this->getBehaviorMock('foo');
        $behavior
            ->expects($this->once())
            ->method('isTableModified')
            ->will($this->returnValue(false))
        ;

        $behavior
            ->expects($this->once())
            ->method('getTableModifier')
            ->will($this->returnValue($behavior))
        ;

        $behavior
            ->expects($this->once())
            ->method('modifyTable')
        ;

        $behavior
            ->expects($this->once())
            ->method('setTableModified')
            ->with($this->equalTo(true))
        ;

        $table = new Table();
        $table->addBehavior($behavior);
        $table->applyBehaviors();
    }

    public function testGetAdditionalBuilders()
    {
        $additionalBehaviors = array(
            $this->getBehaviorMock('foo'),
            $this->getBehaviorMock('bar'),
            $this->getBehaviorMock('baz'),
        );

        $behavior = $this->getBehaviorMock('mix', array(
            'additional_builders' => $additionalBehaviors,
        ));

        $table = new Table();
        $table->addBehavior($behavior);

        $this->assertCount(3, $table->getAdditionalBuilders());
        $this->assertTrue($table->hasAdditionalBuilders());
    }

    public function testHasNoAdditionalBuilders()
    {
        $table = new Table();
        $table->addBehavior($this->getBehaviorMock('foo'));

        $this->assertCount(0, $table->getAdditionalBuilders());
        $this->assertFalse($table->hasAdditionalBuilders());
    }

    public function testGetColumnList()
    {
        $columns = array(
            $this->getColumnMock('foo'),
            $this->getColumnMock('bar'),
            $this->getColumnMock('baz'),
        );

        $table = new Table();

        $this->assertSame('foo|bar|baz', $table->getColumnList($columns, '|'));
    }

    public function testGetNameWithoutPlatform()
    {
        $table = new Table('books');

        $this->assertSame('books', $table->getName());
    }

    /**
     * @dataProvider provideSchemaNames
     *
     */
    public function testGetNameWithPlatform($supportsSchemas, $schemaName, $expectedName)
    {
        $database = $this->getDatabaseMock($schemaName, array(
            'platform' => $this->getPlatformMock($supportsSchemas),
        ));

        $database
            ->expects($supportsSchemas ? $this->once() : $this->never())
            ->method('getSchemaDelimiter')
            ->will($this->returnValue('.'))
        ;

        $table = new Table('books');
        $table->setSchema($schemaName);
        $table->setDatabase($database);

        $this->assertSame($expectedName, $table->getName());
    }

    public function provideSchemaNames()
    {
        return array(
            array(false, 'bookstore', 'books'),
            array(false, null, 'books'),
            array(true, 'bookstore', 'bookstore.books'),
        );
    }

    public function testSetDefaultPhpName()
    {
        $table = new Table('created_at');

        $this->assertSame('CreatedAt', $table->getPhpName());
        $this->assertSame('createdAt', $table->getCamelCaseName());
    }

    public function testSetCustomPhpName()
    {
        $table = new Table('created_at');
        $table->setPhpName('CreatedAt');

        $this->assertSame('CreatedAt', $table->getPhpName());
        $this->assertSame('createdAt', $table->getCamelCaseName());
    }

    public function testSetDescription()
    {
        $table = new Table();

        $this->assertFalse($table->hasDescription());

        $table->setDescription('Some description');
        $this->assertTrue($table->hasDescription());
        $this->assertSame('Some description', $table->getDescription());
    }

    public function testSetInvalidDefaultStringFormat()
    {
        $this->setExpectedException('Propel\Generator\Exception\InvalidArgumentException');

        $table = new Table();
        $table->setDefaultStringFormat('FOO');
    }

    public function testGetDefaultStringFormatFromDatabase()
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getDefaultStringFormat')
            ->will($this->returnValue('XML'))
        ;

        $table = new Table();
        $table->setDatabase($database);

        $this->assertSame('XML', $table->getDefaultStringFormat());
    }

    /**
     * @dataProvider provideStringFormats
     *
     */
    public function testGetDefaultStringFormat($format)
    {
        $table = new Table();
        $table->setDefaultStringFormat($format);

        $this->assertSame($format, $table->getDefaultStringFormat());
    }

    public function provideStringFormats()
    {
        return array(
            array('XML'),
            array('YAML'),
            array('JSON'),
            array('CSV'),
        );
    }

    public function testAddSameColumnTwice()
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at', array('phpName' => 'CreatedAt'));

        $this->setExpectedException('Propel\Generator\Exception\EngineException');

        $table->addColumn($column);
        $table->addColumn($column);
    }

    public function testGetChildrenNames()
    {
        $column = $this->getColumnMock('created_at', array('inheritance' => true));

        $column
            ->expects($this->any())
            ->method('isEnumeratedClasses')
            ->will($this->returnValue(true))
        ;

        $children[] = $this->getMock('Propel\Generator\Model\Inheritance');
        $children[] = $this->getMock('Propel\Generator\Model\Inheritance');

        $column
            ->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue($children))
        ;

        $table = new Table('books');
        $table->addColumn($column);

        $names = $table->getChildrenNames();

        $this->assertCount(2, $names);
        $this->assertSame('Propel\Generator\Model\Inheritance', get_parent_class ($names[0]));
        $this->assertSame('Propel\Generator\Model\Inheritance', get_parent_class ($names[1]));
    }

    public function testCantGetChildrenNames()
    {
        $column = $this->getColumnMock('created_at', array('inheritance' => true));

        $column
            ->expects($this->any())
            ->method('isEnumeratedClasses')
            ->will($this->returnValue(false))
        ;

        $table = new Table('books');
        $table->addColumn($column);

        $this->assertNull($table->getChildrenNames());
    }

    public function testAddInheritanceColumn()
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at', array('inheritance' => true));

        $this->assertInstanceOf('Propel\Generator\Model\Column', $table->addColumn($column));
        $this->assertInstanceOf('Propel\Generator\Model\Column', $table->getChildrenColumn());
        $this->assertTrue($table->hasColumn($column, true));
        $this->assertTrue($table->hasColumn($column, false));
        $this->assertCount(1, $table->getColumns());
        $this->assertSame(1, $table->getNumColumns());
        $this->assertTrue($table->requiresTransactionInPostgres());
    }

    public function testHasBehaviors()
    {
        $behavior1 = $this->getBehaviorMock('Foo');
        $behavior2 = $this->getBehaviorMock('Bar');
        $behavior3 = $this->getBehaviorMock('Baz');

        $table = new Table();
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

    public function testCantRemoveColumnWhichIsNotInTable()
    {
        $this->setExpectedException('Propel\Generator\Exception\EngineException');

        $column1 = $this->getColumnMock('title');

        $table = new Table('books');
        $table->removeColumn($column1);
    }

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

    public function testGetNumLazyLoadColumns()
    {
        $column1 = $this->getColumnMock('created_at');
        $column2 = $this->getColumnMock('updated_at', array('lazy' => true));

        $column3 = $this->getColumnMock('deleted_at', array('lazy' => true));

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertSame(2, $table->getNumLazyLoadColumns());
    }

    public function testHasEnumColumns()
    {
        $column1 = $this->getColumnMock('created_at');
        $column2 = $this->getColumnMock('updated_at');

        $column1
            ->expects($this->any())
            ->method('isEnumType')
            ->will($this->returnValue(false))
        ;

        $column2
            ->expects($this->any())
            ->method('isEnumType')
            ->will($this->returnValue(true))
        ;

        $table = new Table('books');

        $table->addColumn($column1);
        $this->assertFalse($table->hasEnumColumns());

        $table->addColumn($column2);
        $this->assertTrue($table->hasEnumColumns());
    }

    public function testCantGetColumn()
    {
        $table = new Table('books');

        $this->assertFalse($table->hasColumn('FOO', true));
        $this->assertNull($table->getColumn('FOO'));
        $this->assertNull($table->getColumnByPhpName('Foo'));
    }

    public function testSetAbstract()
    {
        $table = new Table();
        $this->assertFalse($table->isAbstract());

        $table->setAbstract(true);
        $this->assertTrue($table->isAbstract());
    }

    public function testSetInterface()
    {
        $table = new Table();
        $table->setInterface('ActiveRecordInterface');

        $this->assertSame('ActiveRecordInterface', $table->getInterface());
    }

    public function testAddIndex()
    {
        $table = new Table();
        $index = new Index();
        $index->addColumn(['name' => 'bla']);
        $table->addIndex($index);

        $this->assertCount(1, $table->getIndices());
    }

    /**
     * @expectedException \Propel\Generator\Exception\InvalidArgumentException
     */
    public function testAddEmptyIndex()
    {
        $table = new Table();
        $table->addIndex(new Index());

        $this->assertCount(1, $table->getIndices());
    }

    public function testAddArrayIndex()
    {
        $table = new Table();
        $table->addIndex(array('name' => 'author_idx', 'columns' => [['name' => 'bla']]));

        $this->assertCount(1, $table->getIndices());
    }

    public function testIsIndex()
    {
        $table = new Table();
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

    public function testAddUniqueIndex()
    {
        $table = new Table();
        $table->addUnique($this->getUniqueIndexMock('author_unq'));

        $this->assertCount(1, $table->getUnices());
    }

    public function testAddArrayUnique()
    {
        $table = new Table();
        $table->addUnique(array('name' => 'author_unq'));

        $this->assertCount(1, $table->getUnices());
    }

    public function testGetCompositePrimaryKey()
    {
        $column1 = $this->getColumnMock('book_id', array('primary' => true));
        $column2 = $this->getColumnMock('author_id', array('primary' => true));
        $column3 = $this->getColumnMock('rank');

        $table = new Table();
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

    public function testGetSinglePrimaryKey()
    {
        $column1 = $this->getColumnMock('id', array('primary' => true));
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table();
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

    public function testGetNoPrimaryKey()
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table();
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

    public function testGetAutoIncrementPrimaryKey()
    {
        $column1 = $this->getColumnMock('id', array(
            'primary' => true,
            'auto_increment' => true
        ));

        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table();
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(1, $table->getPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertTrue($table->hasAutoIncrementPrimaryKey());
        $this->assertSame($column1, $table->getAutoIncrementPrimaryKey());
    }

    public function testAddIdMethodParameter()
    {
        $parameter = $this
            ->getMockBuilder('Propel\Generator\Model\IdMethodParameter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $parameter
            ->expects($this->once())
            ->method('setTable')
        ;

        $table = new Table();
        $table->addIdMethodParameter($parameter);

        $this->assertCount(1, $table->getIdMethodParameters());
    }

    public function testAddArrayIdMethodParameter()
    {
        $table = new Table();
        $table->addIdMethodParameter(array('name' => 'foo', 'value' => 'bar'));

        $this->assertCount(1, $table->getIdMethodParameters());
    }

    public function testAddReferrerForeignKey()
    {
        $table = new Table('books');
        $table->addReferrer($this->getForeignKeyMock());

        $this->assertCount(1, $table->getReferrers());
    }

    public function testAddForeignKey()
    {
        $fk = $this->getForeignKeyMock('fk_author_id', array(
            'foreign_table_name' => 'authors',
        ));

        $table = new Table('books');

        $this->assertInstanceOf('Propel\Generator\Model\ForeignKey', $table->addForeignKey($fk));
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertTrue($table->hasForeignKeys());
        $this->assertContains('authors', $table->getForeignTableNames());
    }

    public function testAddArrayForeignKey()
    {
        $table = new Table('books');
        $table->setDatabase($this->getDatabaseMock('bookstore'));

        $fk = $table->addForeignKey(array(
            'name'         => 'fk_author_id',
            'phpName'      => 'Author',
            'refPhpName'   => 'Books',
            'onDelete'     => 'CASCADE',
            'foreignTable' => 'authors',
        ));

        $this->assertInstanceOf('Propel\Generator\Model\ForeignKey', $fk);
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertTrue($table->hasForeignKeys());

        $this->assertContains('authors', $table->getForeignTableNames());
    }

    public function testGetForeignKeysReferencingTable()
    {
        $fk1 = $this->getForeignKeyMock('fk1', array('foreign_table_name' => 'authors'));
        $fk2 = $this->getForeignKeyMock('fk2', array('foreign_table_name' => 'categories'));
        $fk3 = $this->getForeignKeyMock('fk3', array('foreign_table_name' => 'authors'));

        $table = new Table();
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);
        $table->addForeignKey($fk3);

        $this->assertCount(2, $table->getForeignKeysReferencingTable('authors'));
    }

    public function testGetForeignKeysReferencingTableMoreThenOnce()
    {
        $fk1 = $this->getForeignKeyMock('fk1', array('foreign_table_name' => 'authors'));
        $fk2 = $this->getForeignKeyMock('fk2', array('foreign_table_name' => 'categories'));
        $fk3 = $this->getForeignKeyMock('fk1', array('foreign_table_name' => 'authors'));

        $table = new Table();
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);

        $this->setExpectedException('Propel\Generator\Exception\EngineException');
        $table->addForeignKey($fk3);
        $this->fail('Expected to throw an EngineException due to duplicate foreign key.');
    }

    public function testGetColumnForeignKeys()
    {
        $fk1 = $this->getForeignKeyMock('fk1', array(
            'local_columns' => array('foo', 'author_id', 'bar')
        ));

        $fk2 = $this->getForeignKeyMock('fk2', array(
            'local_columns' => array('foo', 'bar')
        ));

        $table = new Table();
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);

        $this->assertCount(1, $table->getColumnForeignKeys('author_id'));
        $this->assertContains($fk1, $table->getColumnForeignKeys('author_id'));
    }

    public function testSetBaseClasses()
    {
        $table = new Table();
        $table->setBaseClass('BaseObject');

        $this->assertSame('BaseObject', $table->getBaseClass());
    }

    public function testGetBaseClassesFromDatabase()
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getBaseClass')
            ->will($this->returnValue('BaseObject'))
        ;

        $table = new Table();
        $table->setDatabase($database);

        $this->assertSame('BaseObject', $table->getBaseClass());
    }

    public function testGetBaseClassesWithAlias()
    {
        $table = new Table('books');
        $table->setAlias('Book');

        $this->assertSame('Book', $table->getBaseClass());
    }

    public function testSetAlias()
    {
        $table = new Table('books');

        $this->assertFalse($table->isAlias());

        $table->setAlias('Book');
        $this->assertTrue($table->isAlias());
        $this->assertSame('Book', $table->getAlias());
    }

    public function testTablePrefix()
    {
        $database = new Database();
        $database->loadMapping(array(
            'name'                   => 'bookstore',
            'defaultIdMethod'        => 'native',
            'defaultPhpNamingMethod' => 'underscore',
            'tablePrefix'            => 'acme_',
            'defaultStringFormat'    => 'XML',
        ));

        $table = new Table();
        $database->addTable($table);
        $table->loadMapping(array(
           'name' => 'books'
        ));
        $this->assertEquals('Books', $table->getPhpName());
        $this->assertEquals('acme_books', $table->getCommonName());
    }

    public function testSetContainsForeignPK()
    {
        $table = new Table();

        $table->setContainsForeignPK(true);
        $this->assertTrue($table->getContainsForeignPK());
    }

    public function testSetCrossReference()
    {
        $table = new Table('books');

        $this->assertFalse($table->getIsCrossRef());
        $this->assertFalse($table->isCrossRef());

        $table->setIsCrossRef(true);
        $this->assertTrue($table->getIsCrossRef());
        $this->assertTrue($table->isCrossRef());
    }

    public function testSetSkipSql()
    {
        $table = new Table('books');
        $table->setSkipSql(true);

        $this->assertTrue($table->isSkipSql());
    }

    public function testSetForReferenceOnly()
    {
        $table = new Table('books');
        $table->setForReferenceOnly(true);

        $this->assertTrue($table->isForReferenceOnly());
    }

    /**
     * Returns a dummy Column object.
     *
     * @param  string $name    The column name
     * @param  array  $options An array of options
     * @return Column
     */
    protected function getColumnMock($name, array $options = array())
    {
        $defaults = array(
            'primary' => false,
            'auto_increment' => false,
            'inheritance' => false,
            'lazy' => false,
            'phpName' => str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))),
            'pg_transaction' => true,
        );

        // Overwrite default options with custom options
        $options = array_merge($defaults, $options);

        $column = parent::getColumnMock($name, $options);

        $column
            ->expects($this->any())
            ->method('setTable')
        ;

        $column
            ->expects($this->any())
            ->method('setPosition')
        ;

        $column
            ->expects($this->any())
            ->method('isPrimaryKey')
            ->will($this->returnValue($options['primary']))
        ;

        $column
            ->expects($this->any())
            ->method('isAutoIncrement')
            ->will($this->returnValue($options['auto_increment']))
        ;

        $column
            ->expects($this->any())
            ->method('isInheritance')
            ->will($this->returnValue($options['inheritance']))
        ;

        $column
            ->expects($this->any())
            ->method('isLazyLoad')
            ->will($this->returnValue($options['lazy']))
        ;

        $column
            ->expects($this->any())
            ->method('getPhpName')
            ->will($this->returnValue($options['phpName']))
        ;

        $column
            ->expects($this->any())
            ->method('requiresTransactionInPostgres')
            ->will($this->returnValue($options['pg_transaction']))
        ;

        return $column;
    }
}
