<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Tests for package handling.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class FieldTest extends ModelTestCase
{
    public function testCreateNewField()
    {
        $field = new Field('title');

        $this->assertSame('title', $field->getName());
        $this->assertEmpty($field->getAutoIncrementString());
        $this->assertSame('COL_TITLE', $field->getConstantName());
        $this->assertSame('public', $field->getMutatorVisibility());
        $this->assertSame('public', $field->getAccessorVisibility());
        $this->assertFalse($field->getSize());
        $this->assertFalse($field->hasPlatform());
        $this->assertFalse($field->hasReferrers());
        $this->assertFalse($field->isAutoIncrement());
        $this->assertFalse($field->isEnumeratedClasses());
        $this->assertFalse($field->isLazyLoad());
        $this->assertFalse($field->isNamePlural());
        $this->assertFalse($field->isNestedSetLeftKey());
        $this->assertFalse($field->isNestedSetRightKey());
        $this->assertFalse($field->isNotNull());
        $this->assertFalse($field->isNodeKey());
        $this->assertFalse($field->isPrimaryKey());
        $this->assertFalse($field->isPrimaryString());
        $this->assertFalse($field->isTreeScopeKey());
        $this->assertFalse($field->isUnique());
        $this->assertFalse($field->requiresTransactionInPostgres());
    }

    public function testSetupObjectWithoutPlatformTypeAndDomain()
    {
        $database = $this->getDatabaseMock('bookstore');

        $entity = $this->getEntityMock('books', array('database' => $database));

        $field = new Field();
        $field->setEntity($entity);
        $field->loadMapping(array('name' => 'title'));

        $this->assertSame('title', $field->getName());
        $this->assertSame('VARCHAR', $field->getDomain()->getType());
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

        $entity = $this->getEntityMock('books', array(
            'database' => $database,
            'platform' => $platform,
        ));

        $domain = $this->getDomainMock('VARCHAR');
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('VARCHAR'))
        ;

        $field = new Field();
        $field->setEntity($entity);
        $field->setDomain($domain);
        $field->loadMapping(array('name' => 'title'));

        $this->assertSame('title', $field->getName());
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

        $entity = $this->getEntityMock('books', array(
            'database' => $database,
            'platform' => $platform,
        ));

        $field = new Field();
        $field->setEntity($entity);
        $field->setDomain($this->getDomainMock('VARCHAR'));
        $field->loadMapping(array(
            'type'        => 'date',
            'name'        => 'created_at',
            'defaultExpr' => 'NOW()',
        ));

        $this->assertSame('createdAt', $field->getName());
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

        $entity = $this->getEntityMock('books', array('database' => $database));

        $field = new Field();
        $field->setEntity($entity);
        $field->setDomain($this->getDomainMock('BOOLEAN'));
        $field->loadMapping(array(
            'domain'             => 'BOOLEAN',
            'name'               => 'isPublished',
            'sqlName'            => 'is_published',
            'phpType'            => 'boolean',
            'entityMapName'      => 'IS_PUBLISHED',
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

        $this->assertSame('isPublished', $field->getName());
        $this->assertSame('is_published', $field->getSqlName());
        $this->assertSame('boolean', $field->getPhpType());
        $this->assertSame('IS_PUBLISHED', $field->getEntityMapName());
        $this->assertSame('public', $field->getAccessorVisibility());
        $this->assertSame('public', $field->getMutatorVisibility());
        $this->assertFalse($field->isPrimaryString());
        $this->assertFalse($field->isPrimaryKey());
        $this->assertFalse($field->isNodeKey());
        $this->assertFalse($field->isNestedSetLeftKey());
        $this->assertFalse($field->isNestedSetRightKey());
        $this->assertFalse($field->isTreeScopeKey());
        $this->assertTrue($field->isLazyLoad());
        $this->assertCount(3, $field->getValueSet());
    }

    public function testSetPosition()
    {
        $field = new Field();
        $field->setPosition(2);

        $this->assertSame(2, $field->getPosition());
    }

    public function testGetNullDefaultValueString()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue(null))
        ;

        $field = new Field();
        $field->setDomain($domain);

        $this->assertSame('null', $field->getDefaultValueString());
    }

    /**
     * @dataProvider provideDefaultValues
     */
    public function testGetDefaultValueString($mappingType, $value, $expected)
    {
        $defaultValue = $this
            ->getMockBuilder('Propel\Generator\Model\FieldDefaultValue')
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setDefaultValue('foo');          // Test with a scalar
        $field->setDefaultValue($defaultValue);  // Test with an object

        $this->assertSame($expected, $field->getDefaultValueString());
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
        $field = new Field();

        $inheritance = $this
            ->getMockBuilder('Propel\Generator\Model\Inheritance')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $inheritance
            ->expects($this->any())
            ->method('setField')
            ->with($this->equalTo($field))
        ;

        $field->addInheritance($inheritance);

        $this->assertTrue($field->isEnumeratedClasses());
        $this->assertCount(1, $field->getChildren());

        $field->clearInheritanceList();
        $this->assertCount(0, $field->getChildren());
    }

    public function testAddArrayInheritance()
    {
        $field = new Field();

        $field->addInheritance(array(
            'key' => 'baz',
            'extends' => 'BaseObject',
            'class' => 'Foo\Bar',
            'package' => 'Foo',
        ));

        $field->addInheritance(array(
            'key' => 'foo',
            'extends' => 'BaseObject',
            'class' => 'Acme\Foo',
            'package' => 'Acme',
        ));

        $this->assertCount(2, $field->getChildren());
    }

    public function testClearForeignKeys()
    {
        $fks = array(
            $this->getMock('Propel\Generator\Model\Relation'),
            $this->getMock('Propel\Generator\Model\Relation'),
        );

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->any())
            ->method('getFieldRelations')
            ->with('authorId')
            ->will($this->returnValue($fks))
        ;

        $field = new Field('author_id');
        $field->setEntity($entity);
        $field->addReferrer($fks[0]);
        $field->addReferrer($fks[1]);

        $this->assertTrue($field->isRelation());
        $this->assertTrue($field->hasMultipleFK());
        $this->assertTrue($field->hasReferrers());
        $this->assertTrue($field->hasReferrer($fks[0]));
        $this->assertCount(2, $field->getReferrers());

        // Clone the current field
        $clone = clone $field;

        $field->clearReferrers();
        $this->assertCount(0, $field->getReferrers());
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

        $field = new Field();
        $field->setEntity($this->getEntityMock('books', array(
            'platform' => $platform
        )));
        $field->setDomain($domain);
        $field->setDomainForType('BOOLEAN');

        $this->assertTrue($field->isDefaultSqlType($platform));
    }

    public function testIsDefaultSqlType()
    {
        $field = new Field();

        $this->assertTrue($field->isDefaultSqlType());
    }

    public function testGetNotNullString()
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getNullString')
            ->will($this->returnValue('NOT NULL'))
        ;

        $entity = $this->getEntityMock('books', array('platform' => $platform));

        $field = new Field();
        $field->setEntity($entity);
        $field->setNotNull(true);

        $this->assertSame('NOT NULL', $field->getNotNullString());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame($pdoType, $field->getPDOType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType('ENUM');
        $field->setValueSet(array('FOO', 'BAR'));

        $this->assertSame('int', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isEnumType());
        $this->assertContains('FOO', $field->getValueSet());
        $this->assertContains('BAR', $field->getValueSet());
    }

    public function testSetStringValueSet()
    {
        $field = new Field();
        $field->setValueSet(' FOO , BAR , BAZ');

        $this->assertContains('FOO', $field->getValueSet());
        $this->assertContains('BAR', $field->getValueSet());
        $this->assertContains('BAZ', $field->getValueSet());
    }

    public function testPhpObjectType()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('OBJECT'))
        ;

        $field = new Field();
        $field->setDomain($domain);
        $field->setType('OBJECT');

        $this->assertFalse($field->isPhpPrimitiveType());
        $this->assertTrue($field->isPhpObjectType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame('string', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isTemporalType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame($phpType, $field->getPhpType());
        $this->assertSame($isPhpPrimitiveType, $field->isPhpPrimitiveType());
        $this->assertTrue($field->isLobType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame('boolean', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isBooleanType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame($phpType, $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertSame($isPrimitiveNumericType, $field->isPhpPrimitiveNumericType());
        $this->assertTrue($field->isNumericType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setType($mappingType);

        $this->assertSame('string', $field->getPhpType());
        $this->assertTrue($field->isPhpPrimitiveType());
        $this->assertTrue($field->isTextType());
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

        $field = new Field();
        $field->setDomain($domain);

        $this->assertSame('(10,2)', $field->getSizeDefinition());
    }

    public function testGetConstantName()
    {
        $entity = $this->getEntityMock('Article');

        $field = new Field('created_at');
        $field->setEntity($entity);
        $field->setEntityMapName('created_at');

        $this->assertSame('created_at', $field->getEntityMapName());
        $this->assertSame('COL_CREATED_AT', $field->getConstantName());
        $this->assertSame('ArticleEntityMap::COL_CREATED_AT', $field->getFQConstantName());
    }

    public function testSetDefaultMutatorAndAccessorMethodsVisibility()
    {
        $field = new Field();
        $field->setAccessorVisibility('foo');
        $field->setMutatorVisibility('bar');

        $this->assertSame('public', $field->getAccessorVisibility());
        $this->assertSame('public', $field->getMutatorVisibility());
    }

    public function testSetMutatorAndAccessorMethodsVisibility()
    {
        $field = new Field();
        $field->setAccessorVisibility('private');
        $field->setMutatorVisibility('private');

        $this->assertSame('private', $field->getAccessorVisibility());
        $this->assertSame('private', $field->getMutatorVisibility());
    }

    public function testGetPhpDefaultValue()
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getPhpDefaultValue')
            ->will($this->returnValue(true))
        ;

        $field = new Field();
        $field->setDomain($domain);

        $this->assertTrue($field->getPhpDefaultValue());
    }

    public function testGetAutoIncrementStringThrowsEngineException()
    {
        $this->setExpectedException('Propel\Generator\Exception\EngineException');

        $entity = $this->getEntityMock('books');
        $entity
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('none'))
        ;

        $field = new Field();
        $field->setEntity($entity);
        $field->setAutoIncrement(true);
        $field->getAutoIncrementString();
    }

    public function testGetNativeAutoIncrementString()
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getAutoIncrement')
            ->will($this->returnValue('AUTO_INCREMENT'))
        ;

        $entity = $this->getEntityMock('books', array('platform' => $platform));
        $entity
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('native'))
        ;

        $field = new Field();
        $field->setAutoIncrement(true);
        $field->setEntity($entity);

        $this->assertEquals('AUTO_INCREMENT', $field->getAutoIncrementString());
    }

    public function testGetFullyQualifiedName()
    {
        $field = new Field('title');
        $field->setEntity($this->getEntityMock('books'));

        $this->assertSame('books.TITLE', $field->getFullyQualifiedName());
    }

    public function testHasPlatform()
    {
        $entity = $this->getEntityMock('books', array(
            'platform' => $this->getPlatformMock(),
        ));

        $field = new Field();
        $field->setEntity($entity);

        $this->assertTrue($field->hasPlatform());
        $this->assertInstanceOf('Propel\Generator\Platform\PlatformInterface', $field->getPlatform());
    }

    public function testIsPhpArrayType()
    {
        $field = new Field();
        $this->assertFalse($field->isPhpArrayType());

        $field->setType(PropelTypes::PHP_ARRAY);
        $this->assertTrue($field->isPhpArrayType());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setSize(50);

        $this->assertSame(50, $field->getSize());
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

        $field = new Field();
        $field->setDomain($domain);
        $field->setScale(2);

        $this->assertSame(2, $field->getScale());
    }

    public function testGetDefaultDomain()
    {
        $field = new Field();

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $field->getDomain());
    }

    public function testGetSingularName()
    {
        $field = new Field('titles');

        $this->assertSame('title', $field->getSingularName());
        $this->assertTrue($field->isNamePlural());
    }

    public function testSetEntity()
    {
        $field = new Field();
        $field->setEntity($this->getEntityMock('books'));

        $this->assertInstanceOf('Propel\Generator\Model\Entity', $field->getEntity());
        $this->assertSame('books', $field->getEntityName());
    }

    public function testSetDomain()
    {
        $field = new Field();
        $field->setDomain($this->getDomainMock());

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $field->getDomain());
    }

    public function testSetDescription()
    {
        $field = new Field();
        $field->setDescription('Some description');

        $this->assertSame('Some description', $field->getDescription());
    }

    public function testSetNestedSetLeftKey()
    {
        $field = new Field();
        $field->setNestedSetLeftKey(true);
        $field->setNodeKeySep(',');
        $field->setNodeKey(true);

        $this->assertTrue($field->isNestedSetLeftKey());
        $this->assertTrue($field->isNodeKey());
        $this->assertSame(',', $field->getNodeKeySep());
    }

    public function testSetNestedSetRightKey()
    {
        $field = new Field();
        $field->setNestedSetRightKey(true);

        $this->assertTrue($field->isNestedSetRightKey());
    }

    public function testSetTreeScopeKey()
    {
        $field = new Field();
        $field->setTreeScopeKey(true);

        $this->assertTrue($field->isTreeScopeKey());
    }

    public function testSetAutoIncrement()
    {
        $field = new Field();
        $field->setAutoIncrement(true);

        $this->assertTrue($field->isAutoIncrement());
    }

    public function testSetPrimaryString()
    {
        $field = new Field();
        $field->setPrimaryString(true);

        $this->assertTrue($field->isPrimaryString());
    }

    public function testSetNotNull()
    {
        $field = new Field();
        $field->setNotNull(true);

        $this->assertTrue($field->isNotNull());
    }

    public function testSingularName()
    {
        $field = new Field();
        $field->setName('Aliases');

        $this->assertEquals($field->getName(), 'aliases');
        $this->assertEquals($field->getSingularName(), 'aliase');

        $field = new Field();
        $field->setName('Aliases');
        $field->setSingularName('Alias');

        $this->assertEquals($field->getName(), 'aliases');
        $this->assertEquals($field->getSingularName(), 'alias');
    }
}
