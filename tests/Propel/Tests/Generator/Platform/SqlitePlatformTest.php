<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\FieldDefaultValue;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;

/**
 *
 */
class SqlitePlatformTest extends PlatformTestProvider
{
    /**
     * Get the Platform object for this class
     *
     * @return Platform
     */
    protected function getPlatform()
    {
        return new SqlitePlatform();
    }

    public function testQuoteConnected()
    {
        $p = $this->getPlatform();
        $p->setConnection(ConnectionFactory::create(array('dsn' => 'sqlite::memory:'), AdapterFactory::create('sqlite')));

        $unquoted = "Naughty ' string";
        $quoted = $p->quote($unquoted);

        $expected = "'Naughty '' string'";
        $this->assertEquals($expected, $quoted);
    }

    public function testGetSequenceNameDefault()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_SEQ';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($entity));
    }

    public function testGetSequenceNameCustom()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(IdMethod::NATIVE);
        $idMethodParameter = new IdMethodParameter();
        $idMethodParameter->setValue('foo_sequence');
        $entity->addIdMethodParameter($idMethodParameter);
        $entity->setIdMethod(IdMethod::NATIVE);
        $expected = 'foo_sequence';
        $this->assertEquals($expected, $this->getPlatform()->getSequenceName($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesDDL
     */
    public function testGetAddEntitiesDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- book
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [book];

CREATE TABLE [book]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [title] VARCHAR(255) NOT NULL,
    [author_id] INTEGER,
    UNIQUE ([id]),
    FOREIGN KEY ([author_id]) REFERENCES [author] ([id])
);

CREATE INDEX [book_i_639136] ON [book] ([title]);

-----------------------------------------------------------------------
-- author
-----------------------------------------------------------------------

DROP TABLE IF EXISTS [author];

CREATE TABLE [author]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [first_name] VARCHAR(100),
    [last_name] VARCHAR(100),
    UNIQUE ([id])
);

EOF;
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntitiesSkipSQLDDL
     */
    public function testGetAddEntitiesSkipSQLDDL($schema)
    {
        $database = $this->getDatabaseFromSchema($schema);
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddEntitiesDDL($database));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLSimplePK
     */
    public function testGetAddEntityDDLSimplePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
-- This is foo table
CREATE TABLE [foo]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [bar] VARCHAR(255) NOT NULL
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLNonIntegerPK
     */
    public function testGetAddEntityDDLNonIntegerPK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
-- This is foo table
CREATE TABLE [foo]
(
    [foo] VARCHAR(255) NOT NULL,
    [bar] VARCHAR(255) NOT NULL,
    PRIMARY KEY ([foo])
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLCompositePK
     */
    public function testGetAddEntityDDLCompositePK($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE [foo]
(
    [foo] INTEGER NOT NULL,
    [bar] INTEGER NOT NULL,
    [baz] VARCHAR(255) NOT NULL,
    PRIMARY KEY ([foo],[bar])
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetAddEntityDDLUniqueIndex
     */
    public function testGetAddEntityDDLUniqueIndex($schema)
    {
        $entity = $this->getEntityFromSchema($schema);
        $expected = "
CREATE TABLE [foo]
(
    [id] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [bar] INTEGER,
    UNIQUE ([bar])
);
";
        $this->assertEquals($expected, $this->getPlatform()->getAddEntityDDL($entity));
    }

    public function testGetDropEntityDDL()
    {
        $entity = new Entity('foo');
        $expected = "
DROP TABLE IF EXISTS [foo];
";
        $this->assertEquals($expected, $this->getPlatform()->getDropEntityDDL($entity));
    }

    public function testGetFieldDDL()
    {
        $c = new Field('foo');
        $c->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c->getDomain()->replaceScale(2);
        $c->getDomain()->replaceSize(3);
        $c->setNotNull(true);
        $c->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $expected = '[foo] DOUBLE(3,2) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($c));
    }

    public function testGetFieldDDLCustomSqlType()
    {
        $field = new Field('foo');
        $field->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $field->getDomain()->replaceScale(2);
        $field->getDomain()->replaceSize(3);
        $field->setNotNull(true);
        $field->getDomain()->setDefaultValue(new FieldDefaultValue(123, FieldDefaultValue::TYPE_VALUE));
        $field->getDomain()->replaceSqlType('DECIMAL(5,6)');
        $expected = '[foo] DECIMAL(5,6) DEFAULT 123 NOT NULL';
        $this->assertEquals($expected, $this->getPlatform()->getFieldDDL($field));
    }

    public function testGetPrimaryKeyDDLCompositeKeyWithAutoIncrement()
    {
        $entity = new Entity('foo');
        $entity->setIdMethod(IdMethod::NATIVE);

        $field1 = new Field('bar');
        $field1->setPrimaryKey(true);
        $entity->addField($field1);

        $field2 = new Field('baz');
        $field2->setPrimaryKey(true);
        $field2->setAutoIncrement(true);
        $entity->addField($field2);

        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    public function testGetPrimaryKeyDDLCompositeKey()
    {
        $entity = new Entity('foo');
        $field1 = new Field('bar1');
        $field1->setPrimaryKey(true);
        $entity->addField($field1);
        $field2 = new Field('bar2');
        $field2->setPrimaryKey(true);
        $entity->addField($field2);
        $expected = 'PRIMARY KEY ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetDropPrimaryKeyDDL($entity)
    {
        // not supported by SQLite
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestPrimaryKeyDDL
     */
    public function testGetAddPrimaryKeyDDL($entity)
    {
        // not supported by SQLite
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getAddPrimaryKeyDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndicesDDL
     */
    public function testAddIndicesDDL($entity)
    {
        $expected = '
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);

CREATE INDEX [foo_index] ON [foo] ([bar1]);
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndicesDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testAddIndexDDL($index)
    {
        $expected = '
CREATE INDEX [babar] ON [foo] ([bar1],[bar2]);
';
        $this->assertEquals($expected, $this->getPlatform()->getAddIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testDropIndexDDL($index)
    {
        $expected = '
DROP INDEX [babar];
';
        $this->assertEquals($expected, $this->getPlatform()->getDropIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetIndexDDL
     */
    public function testGetIndexDDL($index)
    {
        $expected = 'INDEX [babar] ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getIndexDDL($index));
    }

    /**
     * @dataProvider providerForTestGetUniqueDDL
     */
    public function testGetUniqueDDL($index)
    {
        $expected = 'UNIQUE ([bar1],[bar2])';
        $this->assertEquals($expected, $this->getPlatform()->getUniqueDDL($index));
    }

    /**
     * @dataProvider providerForTestGetRelationsDDL
     */
    public function testGetAddRelationsDDL($entity)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationsDDL($entity));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetAddRelationDDL($fk)
    {
        $expected = "";
        $this->assertEquals($expected, $this->getPlatform()->getAddRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetDropRelationDDL($fk)
    {
        $expected = '';
        $this->assertEquals($expected, $this->getPlatform()->getDropRelationDDL($fk));
    }

    /**
     * @dataProvider providerForTestGetRelationDDL
     */
    public function testGetRelationDDL($fk)
    {
        $expected = 'FOREIGN KEY ([bar_id]) REFERENCES [bar] ([id])
    ON DELETE CASCADE';
        $this->assertEquals($expected, $this->getPlatform()->getRelationDDL($fk));
    }

    public function testGetCommentBlockDDL()
    {
        $expected = "
-----------------------------------------------------------------------
-- foo bar
-----------------------------------------------------------------------
";
        $this->assertEquals($expected, $this->getPlatform()->getCommentBlockDDL('foo bar'));
    }
}
