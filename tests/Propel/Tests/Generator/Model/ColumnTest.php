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
use Propel\Generator\Model\PropelTypes;

/**
 * Tests for package handling.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class ColumnTest extends ModelTestCase
{
    public function testCreateNewColumn()
    {
        $column = new Column('title');

        $this->assertSame('title', $column->getName());
        $this->assertEmpty($column->getAutoIncrementString());
        $this->assertSame('COL_TITLE', $column->getConstantName());
        $this->assertSame('public', $column->getMutatorVisibility());
        $this->assertSame('public', $column->getAccessorVisibility());
        $this->assertFalse($column->getSize());
        $this->assertFalse($column->hasPlatform());
        $this->assertFalse($column->hasReferrers());
        $this->assertFalse($column->isAutoIncrement());
        $this->assertFalse($column->isEnumeratedClasses());
        $this->assertFalse($column->isLazyLoad());
        $this->assertFalse($column->isNamePlural());
        $this->assertFalse($column->isNestedSetLeftKey());
        $this->assertFalse($column->isNestedSetRightKey());
        $this->assertFalse($column->isNotNull());
        $this->assertFalse($column->isNodeKey());
        $this->assertFalse($column->isPrimaryKey());
        $this->assertFalse($column->isPrimaryString());
        $this->assertFalse($column->isTreeScopeKey());
        $this->assertFalse($column->isUnique());
        $this->assertFalse($column->requiresTransactionInPostgres());
    }

    public function testSetupObjectWithoutPlatformTypeAndDomain()
    {
        $database = $this->getDatabaseMock('bookstore');

        $table = $this->getTableMock('books', array('database' => $database));

        $column = new Column();
        $column->setTable($table);
        $column->loadMapping(array('name' => 'title'));

        $this->assertSame('title', $column->getName());
        $this->assertSame('VARCHAR', $column->getDomain()->getType());
    }

    public function testSetupObjectWithPlatformOnly()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getDomainForType')
            ->with($this->equalTo('VARCHAR'))
            ->will($this->returnValue($this->getDomainMock('VARCHAR')))
        ;
        $platform
            ->expects($this->any())
            ->method('supportsVarcharWithoutSize')
            ->will($this->returnValue(false))
        ;

        $table = $this->getTableMock('books', array(
            'database' => $database,
            'platform' => $platform,
        ));

        $domain = $this->getDomainMock('VARCHAR');
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('VARCHAR'))
        ;

        $column = new Column();
        $column->setTable($table);
        $column->setDomain($domain);
        $column->loadMapping(array('name' => 'title'));

        $this->assertSame('title', $column->getName());
    }

    public function testSetupObjectWithPlatformAndType()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getDomainForType')
            ->with($this->equalTo('DATE'))
            ->will($this->returnValue($this->getDomainMock('DATE')))
        ;

        $table = $this->getTableMock('books', array(
            'database' => $database,
            'platform' => $platform,
        ));

        $column = new Column();
        $column->setTable($table);
        $column->setDomain($this->getDomainMock('VARCHAR'));
        $column->loadMapping(array(
            'type'        => 'date',
            'name'        => 'created_at',
            'defaultExpr' => 'NOW()',
        ));

        $this->assertSame('created_at', $column->getName());
    }

    public function testSetupObjectWithDomain()
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getDomain')
            ->with($this->equalTo('BOOLEAN'))
            ->will($this->returnValue($this->getDomainMock('INTEGER')))
        ;

        $table = $this->getTableMock('books', array('database' => $database));

        $column = new Column();
        $column->setTable($table);
        $column->setDomain($this->getDomainMock('BOOLEAN'));
        $column->loadMapping(array(
            'domain'             => 'BOOLEAN',
            'name'               => 'is_published',
            'phpName'            => 'IsPublished',
            'phpType'            => 'boolean',
            'tableMapName'       => 'IS_PUBLISHED',
            'prefix'             => 'col_',
            'accessorVisibility' => 'public',
            'mutatorVisibility'  => 'public',
            'primaryString'      => 'false',
            'primaryKey'         => 'false',
            'nodeKey'            => 'false',
            'nestedSetLeftKey'   => 'false',
            'nestedSetRightKey'  => 'false',
            'treeScopeKey'       => 'false',
            'required'           => 'false',
            'autoIncrement'      => 'false',
            'lazyLoad'           => 'true',
            'sqlType'            => 'TINYINT',
            'size'               => 1,
            'defaultValue'       => 'true',
            'valueSet'           => 'FOO, BAR, BAZ',
        ));

        $this->assertSame('is_published', $column->getName());
        $this->assertSame('IsPublished', $column->getPhpName());
        $this->assertSame('boolean', $column->getPhpType());
        $this->assertSame('IS_PUBLISHED', $column->getTableMapName());
        $this->assertSame('public', $column->getAccessorVisibility());
        $this->assertSame('public', $column->getMutatorVisibility());
        $this->assertFalse($column->isPrimaryString());
        $this->assertFalse($column->isPrimaryKey());
        $this->assertFalse($column->isNodeKey());
        $this->assertFalse($column->isNestedSetLeftKey());
        $this->assertFalse($column->isNestedSetRightKey());
        $this->assertFalse($column->isTreeScopeKey());
        $this->assertTrue($column->isLazyLoad());
        $this->assertCount(3, $column->getValueSet());
    }

    public function testSetPosition()
    {
        $column = new Column();
        $column->setPosition(2);

        $this->assertSame(2, $column->getPosition());
    }

    public function testGetNullDefaultValueString()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue(null))
        ;

        $column = new Column();
        $column->setDomain($domain);

        $this->assertSame('null', $column->getDefaultValueString());
    }

    /**
     * @dataProvider provideDefaultValues
     */
    public function testGetDefaultValueString($mappingType, $value, $expected)
    {
        $defaultValue = $this
            ->getMockBuilder('Propel\Generator\Model\ColumnDefaultValue')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $defaultValue
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($value))
        ;

        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue($defaultValue))
        ;
        $domain
            ->expects($this->any())
            ->method('setDefaultValue')
        ;
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setDefaultValue('foo');          // Test with a scalar
        $column->setDefaultValue($defaultValue);  // Test with an object

        $this->assertSame($expected, $column->getDefaultValueString());
    }

    public function provideDefaultValues()
    {
        return array(
            array('DOUBLE', 3.14, 3.14),
            array('VARCHAR', 'hello', "'hello'"),
            array('VARCHAR', "john's bike", "'john\\'s bike'"),
            array('BOOLEAN', 1, 'true'),
            array('BOOLEAN', 0, 'false'),
            array('ENUM', 'foo,bar', "'foo,bar'"),
        );
    }

    public function testAddInheritance()
    {
        $column = new Column();

        $inheritance = $this
            ->getMockBuilder('Propel\Generator\Model\Inheritance')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $inheritance
            ->expects($this->any())
            ->method('setColumn')
            ->with($this->equalTo($column))
        ;

        $column->addInheritance($inheritance);

        $this->assertTrue($column->isEnumeratedClasses());
        $this->assertCount(1, $column->getChildren());

        $column->clearInheritanceList();
        $this->assertCount(0, $column->getChildren());
    }

    public function testAddArrayInheritance()
    {
        $column = new Column();

        $column->addInheritance(array(
            'key' => 'baz',
            'extends' => 'BaseObject',
            'class' => 'Foo\Bar',
            'package' => 'Foo',
        ));

        $column->addInheritance(array(
            'key' => 'foo',
            'extends' => 'BaseObject',
            'class' => 'Acme\Foo',
            'package' => 'Acme',
        ));

        $this->assertCount(2, $column->getChildren());
    }

    public function testClearForeignKeys()
    {
        $fks = array(
            $this->getMock('Propel\Generator\Model\ForeignKey'),
            $this->getMock('Propel\Generator\Model\ForeignKey'),
        );

        $table = $this->getTableMock('books');
        $table
            ->expects($this->any())
            ->method('getColumnForeignKeys')
            ->with('author_id')
            ->will($this->returnValue($fks))
        ;

        $column = new Column('author_id');
        $column->setTable($table);
        $column->addReferrer($fks[0]);
        $column->addReferrer($fks[1]);

        $this->assertTrue($column->isForeignKey());
        $this->assertTrue($column->hasMultipleFK());
        $this->assertTrue($column->hasReferrers());
        $this->assertTrue($column->hasReferrer($fks[0]));
        $this->assertCount(2, $column->getReferrers());

        // Clone the current column
        $clone = clone $column;

        $column->clearReferrers();
        $this->assertCount(0, $column->getReferrers());
        $this->assertCount(0, $clone->getReferrers());
    }

    public function testIsDefaultSqlTypeFromDomain()
    {
        $toCopy = $this->getDomainMock();
        $toCopy
            ->expects($this->once())
            ->method('getSqlType')
            ->will($this->returnValue('INTEGER'))
        ;

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->with($this->equalTo('BOOLEAN'))
            ->will($this->returnValue($toCopy))
        ;

        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('copy')
            ->with($this->equalTo($toCopy))
        ;
        $domain
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('BOOLEAN'))
        ;
        $domain
            ->expects($this->any())
            ->method('getSqlType')
            ->will($this->returnValue('INTEGER'))
        ;

        $column = new Column();
        $column->setTable($this->getTableMock('books', array(
            'platform' => $platform
        )));
        $column->setDomain($domain);
        $column->setDomainForType('BOOLEAN');

        $this->assertTrue($column->isDefaultSqlType($platform));
    }

    public function testIsDefaultSqlType()
    {
        $column = new Column();

        $this->assertTrue($column->isDefaultSqlType());
    }

    public function testGetNotNullString()
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getNullString')
            ->will($this->returnValue('NOT NULL'))
        ;

        $table = $this->getTableMock('books', array('platform' => $platform));

        $column = new Column();
        $column->setTable($table);
        $column->setNotNull(true);

        $this->assertSame('NOT NULL', $column->getNotNullString());
    }

    /**
     * @dataProvider providePdoTypes
     *
     */
    public function testGetPdoType($mappingType, $pdoType)
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame($pdoType, $column->getPDOType());
    }

    public function providePdoTypes()
    {
        return array(
            array('CHAR', \PDO::PARAM_STR),
            array('VARCHAR', \PDO::PARAM_STR),
            array('LONGVARCHAR', \PDO::PARAM_STR),
            array('CLOB', \PDO::PARAM_STR),
            array('CLOB_EMU', \PDO::PARAM_STR),
            array('NUMERIC', \PDO::PARAM_INT),
            array('DECIMAL', \PDO::PARAM_STR),
            array('TINYINT', \PDO::PARAM_INT),
            array('SMALLINT', \PDO::PARAM_INT),
            array('INTEGER', \PDO::PARAM_INT),
            array('BIGINT', \PDO::PARAM_INT),
            array('REAL', \PDO::PARAM_STR),
            array('FLOAT', \PDO::PARAM_STR),
            array('DOUBLE', \PDO::PARAM_STR),
            array('BINARY', \PDO::PARAM_STR),
            array('VARBINARY', \PDO::PARAM_LOB),
            array('LONGVARBINARY', \PDO::PARAM_LOB),
            array('BLOB', \PDO::PARAM_LOB),
            array('DATE', \PDO::PARAM_STR),
            array('TIME', \PDO::PARAM_STR),
            array('TIMESTAMP', \PDO::PARAM_STR),
            array('BOOLEAN', \PDO::PARAM_BOOL),
            array('BOOLEAN_EMU', \PDO::PARAM_INT),
            array('OBJECT', \PDO::PARAM_LOB),
            array('ARRAY', \PDO::PARAM_STR),
            array('ENUM', \PDO::PARAM_INT),
            array('BU_DATE', \PDO::PARAM_STR),
            array('BU_TIMESTAMP', \PDO::PARAM_STR),
        );
    }

    public function testEnumType()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('ENUM'))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType('ENUM');
        $column->setValueSet(array('FOO', 'BAR'));

        $this->assertSame('int', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isEnumType());
        $this->assertContains('FOO', $column->getValueSet());
        $this->assertContains('BAR', $column->getValueSet());
    }

    public function testSetStringValueSet()
    {
        $column = new Column();
        $column->setValueSet(' FOO , BAR , BAZ');

        $this->assertContains('FOO', $column->getValueSet());
        $this->assertContains('BAR', $column->getValueSet());
        $this->assertContains('BAZ', $column->getValueSet());
    }

    public function testPhpObjectType()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('OBJECT'))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType('OBJECT');

        $this->assertFalse($column->isPhpPrimitiveType());
        $this->assertTrue($column->isPhpObjectType());
    }

    /**
     * @dataProvider provideMappingTemporalTypes
     */
    public function testTemporalType($mappingType)
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame('string', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isTemporalType());
    }

    public function provideMappingTemporalTypes()
    {
        return array(
            array('DATE'),
            array('TIME'),
            array('TIMESTAMP'),
            array('BU_DATE'),
            array('BU_TIMESTAMP'),
        );
    }

    /**
     * @dataProvider provideMappingLobTypes
     */
    public function testLobType($mappingType, $phpType, $isPhpPrimitiveType)
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame($phpType, $column->getPhpType());
        $this->assertSame($isPhpPrimitiveType, $column->isPhpPrimitiveType());
        $this->assertTrue($column->isLobType());
    }

    public function provideMappingLobTypes()
    {
        return array(
            array('VARBINARY', 'string', true),
            array('LONGVARBINARY', 'string', true),
            array('BLOB', 'resource', false),
        );
    }

    /**
     * @dataProvider provideMappingBooleanTypes
     */
    public function testBooleanType($mappingType)
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame('boolean', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isBooleanType());
    }

    public function provideMappingBooleanTypes()
    {
        return array(
            array('BOOLEAN'),
            array('BOOLEAN_EMU'),
        );
    }

    /**
     * @dataProvider provideMappingNumericTypes
     */
    public function testNumericType($mappingType, $phpType, $isPrimitiveNumericType)
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame($phpType, $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertSame($isPrimitiveNumericType, $column->isPhpPrimitiveNumericType());
        $this->assertTrue($column->isNumericType());
    }

    public function provideMappingNumericTypes()
    {
        return array(
            array('SMALLINT', 'int', true),
            array('TINYINT', 'int', true),
            array('INTEGER', 'int', true),
            array('BIGINT', 'string', false),
            array('FLOAT', 'double', true),
            array('DOUBLE', 'double', true),
            array('NUMERIC', 'string', false),
            array('DECIMAL', 'string', false),
            array('REAL', 'double', true),
        );
    }

    /**
     * @dataProvider provideMappingTextTypes
     */
    public function testTextType($mappingType)
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame('string', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isTextType());
    }

    public function provideMappingTextTypes()
    {
        return array(
            array('CHAR'),
            array('VARCHAR'),
            array('LONGVARCHAR'),
            array('CLOB'),
            array('DATE'),
            array('TIME'),
            array('TIMESTAMP'),
            array('BU_DATE'),
            array('BU_TIMESTAMP'),
        );
    }

    public function testGetSizeDefinition()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getSizeDefinition')
            ->will($this->returnValue('(10,2)'))
        ;

        $column = new Column();
        $column->setDomain($domain);

        $this->assertSame('(10,2)', $column->getSizeDefinition());
    }

    public function testGetConstantName()
    {
        $table = $this->getTableMock('article');
        $table
            ->expects($this->once())
            ->method('getPhpName')
            ->will($this->returnValue('Article'))
        ;

        $column = new Column('created_at');
        $column->setTable($table);
        $column->setTableMapName('created_at');

        $this->assertSame('created_at', $column->getTableMapName());
        $this->assertSame('COL_CREATED_AT', $column->getConstantName());
        $this->assertSame('ArticleTableMap::COL_CREATED_AT', $column->getFQConstantName());
    }

    public function testSetDefaultPhpName()
    {
        $column = new Column('created_at');

        $this->assertSame('CreatedAt', $column->getPhpName());
        $this->assertSame('createdAt', $column->getCamelCaseName());
    }

    public function testSetCustomPhpName()
    {
        $column = new Column('created_at');
        $column->setPhpName('CreatedAt');

        $this->assertSame('CreatedAt', $column->getPhpName());
        $this->assertSame('createdAt', $column->getCamelCaseName());
    }

    public function testSetDefaultMutatorAndAccessorMethodsVisibility()
    {
        $column = new Column();
        $column->setAccessorVisibility('foo');
        $column->setMutatorVisibility('bar');

        $this->assertSame('public', $column->getAccessorVisibility());
        $this->assertSame('public', $column->getMutatorVisibility());
    }

    public function testSetMutatorAndAccessorMethodsVisibility()
    {
        $column = new Column();
        $column->setAccessorVisibility('private');
        $column->setMutatorVisibility('private');

        $this->assertSame('private', $column->getAccessorVisibility());
        $this->assertSame('private', $column->getMutatorVisibility());
    }

    public function testGetPhpDefaultValue()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getPhpDefaultValue')
            ->will($this->returnValue(true))
        ;

        $column = new Column();
        $column->setDomain($domain);

        $this->assertTrue($column->getPhpDefaultValue());
    }

    public function testGetAutoIncrementStringThrowsEngineException()
    {
        $this->setExpectedException('Propel\Generator\Exception\EngineException');

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('none'))
        ;

        $column = new Column();
        $column->setTable($table);
        $column->setAutoIncrement(true);
        $column->getAutoIncrementString();
    }

    public function testGetNativeAutoIncrementString()
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getAutoIncrement')
            ->will($this->returnValue('AUTO_INCREMENT'))
        ;

        $table = $this->getTableMock('books', array('platform' => $platform));
        $table
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('native'))
        ;

        $column = new Column();
        $column->setAutoIncrement(true);
        $column->setTable($table);

        $this->assertEquals('AUTO_INCREMENT', $column->getAutoIncrementString());
    }

    public function testGetFullyQualifiedName()
    {
        $column = new Column('title');
        $column->setTable($this->getTableMock('books'));

        $this->assertSame('books.TITLE', $column->getFullyQualifiedName());
    }

    public function testHasPlatform()
    {
        $table = $this->getTableMock('books', array(
            'platform' => $this->getPlatformMock(),
        ));

        $column = new Column();
        $column->setTable($table);

        $this->assertTrue($column->hasPlatform());
        $this->assertInstanceOf('Propel\Generator\Platform\PlatformInterface', $column->getPlatform());
    }

    public function testIsPhpArrayType()
    {
        $column = new Column();
        $this->assertFalse($column->isPhpArrayType());

        $column->setType(PropelTypes::PHP_ARRAY);
        $this->assertTrue($column->isPhpArrayType());
    }

    public function testSetSize()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setSize')
            ->with($this->equalTo(50))
        ;
        $domain
            ->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(50))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setSize(50);

        $this->assertSame(50, $column->getSize());
    }

    public function testSetScale()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setScale')
            ->with($this->equalTo(2))
        ;
        $domain
            ->expects($this->once())
            ->method('getScale')
            ->will($this->returnValue(2))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setScale(2);

        $this->assertSame(2, $column->getScale());
    }

    public function testGetDefaultDomain()
    {
        $column = new Column();

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $column->getDomain());
    }

    public function testGetSingularName()
    {
        $column = new Column('titles');

        $this->assertSame('title', $column->getSingularName());
        $this->assertTrue($column->isNamePlural());
    }

    public function testSetTable()
    {
        $column = new Column();
        $column->setTable($this->getTableMock('books'));

        $this->assertInstanceOf('Propel\Generator\Model\Table', $column->getTable());
        $this->assertSame('books', $column->getTableName());
    }

    public function testSetDomain()
    {
        $column = new Column();
        $column->setDomain($this->getDomainMock());

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $column->getDomain());
    }

    public function testSetDescription()
    {
        $column = new Column();
        $column->setDescription('Some description');

        $this->assertSame('Some description', $column->getDescription());
    }

    public function testSetNestedSetLeftKey()
    {
        $column = new Column();
        $column->setNestedSetLeftKey(true);
        $column->setNodeKeySep(',');
        $column->setNodeKey(true);

        $this->assertTrue($column->isNestedSetLeftKey());
        $this->assertTrue($column->isNodeKey());
        $this->assertSame(',', $column->getNodeKeySep());
    }

    public function testSetNestedSetRightKey()
    {
        $column = new Column();
        $column->setNestedSetRightKey(true);

        $this->assertTrue($column->isNestedSetRightKey());
    }

    public function testSetTreeScopeKey()
    {
        $column = new Column();
        $column->setTreeScopeKey(true);

        $this->assertTrue($column->isTreeScopeKey());
    }

    public function testSetAutoIncrement()
    {
        $column = new Column();
        $column->setAutoIncrement(true);

        $this->assertTrue($column->isAutoIncrement());
    }

    public function testSetPrimaryString()
    {
        $column = new Column();
        $column->setPrimaryString(true);

        $this->assertTrue($column->isPrimaryString());
    }

    public function testSetNotNull()
    {
        $column = new Column();
        $column->setNotNull(true);

        $this->assertTrue($column->isNotNull());
    }

    public function testPhpSingularName()
    {
        $column = new Column();
        $column->setPhpName('Aliases');

        $this->assertEquals($column->getPhpName(), 'Aliases');
        $this->assertEquals($column->getPhpSingularName(), 'Aliase');

        $column = new Column();
        $column->setPhpName('Aliases');
        $column->setPhpSingularName('Alias');

        $this->assertEquals($column->getPhpName(), 'Aliases');
        $this->assertEquals($column->getPhpSingularName(), 'Alias');
    }
}
