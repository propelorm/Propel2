<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Relation;

/**
 * Unit test suite for the Relation model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class RelationTest extends ModelTestCase
{
    public function testCreateNewRelation()
    {
        $fk = new Relation('book_author');

        $this->assertSame('book_author', $fk->getName());
        $this->assertFalse($fk->hasOnUpdate());
        $this->assertFalse($fk->hasOnDelete());
        $this->assertFalse($fk->isComposite());
        $this->assertFalse($fk->isSkipSql());
    }

    public function testRelationIsForeignPrimaryKey()
    {
        $database     = $this->getDatabaseMock('bookstore');
        $platform     = $this->getPlatformMock();
        $foreignEntity = $this->getEntityMock('authors');

        $localEntity   = $this->getEntityMock('books', array(
            'platform' => $platform,
            'database' => $database
        ));

        $idField     = $this->getFieldMock('id');
        $authorIdField = $this->getFieldMock('author_id');

        $database
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $foreignEntity
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue(array($idField)))
        ;

        $foreignEntity
            ->expects($this->any())
            ->method('getField')
            ->with($this->equalTo('id'))
            ->will($this->returnValue($idField))
        ;

        $localEntity
            ->expects($this->any())
            ->method('getField')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($authorIdField))
        ;

        $fk = new Relation();
        $fk->setEntity($localEntity);
        $fk->setForeignEntityName('authors');
        $fk->addReference('author_id', 'id');

        $fkMapping = $fk->getFieldObjectsMapping();

        $this->assertTrue($fk->isForeignPrimaryKey());
        $this->assertCount(1, $fk->getForeignFieldObjects());
        $this->assertSame($authorIdField, $fkMapping[0]['local']);
        $this->assertSame($idField, $fkMapping[0]['foreign']);
        $this->assertSame($idField, $fk->getForeignField(0));
    }

    public function testRelationDoesNotUseRequiredFields()
    {
        $column = $this->getFieldMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(false))
        ;

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getField')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isLocalFieldsRequired());
    }

    public function testRelationUsesRequiredFields()
    {
        $column = $this->getFieldMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(true))
        ;

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getField')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalFieldsRequired());
    }

    public function testCantGetInverseRelation()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(false);
        $foreignEntity = $this->getEntityMock('authors');

        $localEntity = $this->getEntityMock('books', array(
            'platform' => $platform,
            'database' => $database
        ));

        $database
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $inversedFk = new Relation();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setEntity($localEntity);

        $foreignEntity
            ->expects($this->any())
            ->method('getRelations')
            ->will($this->returnValue(array()))
        ;

        $fk = new Relation();
        $fk->setEntity($localEntity);
        $fk->addReference('author_id', 'id');
        $fk->setForeignEntityName('authors');

        $this->assertSame('authors', $fk->getForeignEntityName());
        $this->assertNull($fk->getInverseFK());
        $this->assertFalse($fk->isMatchedByInverseFK());
    }

    public function testGetInverseRelation()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(true);
        $foreignEntity = $this->getEntityMock('authors');

        $localEntity = $this->getEntityMock('books', array(
            'platform' => $platform,
            'database' => $database
        ));

        $database
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $inversedFk = new Relation();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setEntity($localEntity);

        $foreignEntity
            ->expects($this->any())
            ->method('getRelations')
            ->will($this->returnValue(array($inversedFk)))
        ;

        $fk = new Relation();
        $fk->setEntity($localEntity);
        $fk->addReference('author_id', 'id');
        $fk->setForeignEntityName('authors');

        $this->assertSame('authors', $fk->getForeignEntityName());
        $this->assertInstanceOf('Propel\Generator\Model\Entity', $fk->getForeignEntity());
        $this->assertSame($inversedFk, $fk->getInverseFK());
        $this->assertTrue($fk->isMatchedByInverseFK());
    }

    public function testGetLocalField()
    {
        $column = $this->getFieldMock('id');

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->any())
            ->method('getField')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('author_id', 'id');

        $this->assertCount(1, $fk->getLocalFieldObjects());
        $this->assertInstanceOf('Propel\Generator\Model\Field', $fk->getLocalField(0));
    }

    public function testRelationIsNotLocalPrimaryKey()
    {
        $pks = array($this->getFieldMock('id'));

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('book_id', 'id');

        $this->assertFalse($fk->isLocalPrimaryKey());
    }

    public function testRelationIsLocalPrimaryKey()
    {
        $pks = array(
            $this->getFieldMock('book_id'),
            $this->getFieldMock('author_id'),
        );

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $fk = new Relation();
        $fk->setEntity($table);
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalPrimaryKey());
    }

    public function testGetOtherRelations()
    {
        $fk = new Relation();

        $fks[] = new Relation();
        $fks[] = $fk;
        $fks[] = new Relation();

        $table = $this->getEntityMock('books');
        $table
            ->expects($this->once())
            ->method('getRelations')
            ->will($this->returnValue($fks))
        ;

        $fk->setEntity($table);

        $this->assertCount(2, $fk->getOtherFks());
    }

    public function testClearReferences()
    {
        $fk = new Relation();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');
        $fk->clearReferences();

        $this->assertCount(0, $fk->getLocalFields());
        $this->assertCount(0, $fk->getForeignFields());
    }

    public function testAddMultipleReferences()
    {
        $fk = new Relation();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isComposite());
        $this->assertCount(2, $fk->getLocalFields());
        $this->assertCount(2, $fk->getForeignFields());

        $this->assertSame('book_id', $fk->getLocalFieldName(0));
        $this->assertSame('id', $fk->getForeignFieldName(0));
        $this->assertSame('id', $fk->getMappedForeignField('book_id'));

        $this->assertSame('author_id', $fk->getLocalFieldName(1));
        $this->assertSame('id', $fk->getForeignFieldName(1));
        $this->assertSame('id', $fk->getMappedForeignField('author_id'));
    }

    public function testAddSingleStringReference()
    {
        $fk = new Relation();
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalFields());
        $this->assertCount(1, $fk->getForeignFields());

        $this->assertSame('author_id', $fk->getMappedLocalField('id'));
    }

    public function testAddSingleArrayReference()
    {
        $reference = array('local' => 'author_id', 'foreign' => 'id');

        $fk = new Relation();
        $fk->addReference($reference);

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalFields());
        $this->assertCount(1, $fk->getForeignFields());

        $this->assertSame($reference['local'], $fk->getMappedLocalField($reference['foreign']));
    }

    public function testAddSingleFieldReference()
    {
        $fk = new Relation();
        $fk->addReference(
            $this->getFieldMock('author_id'),
            $this->getFieldMock('id')
        );

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalFields());
        $this->assertCount(1, $fk->getForeignFields());

        $this->assertSame('author_id', $fk->getMappedLocalField('id'));
    }

    public function testSetEntity()
    {
        $table = $this->getEntityMock('book');
        $table
            ->expects($this->once())
            ->method('getSchema')
            ->will($this->returnValue('books'))
        ;

        $fk = new Relation();
        $fk->setEntity($table);

        $this->assertInstanceOf('Propel\Generator\Model\Entity', $fk->getEntity());
        $this->assertSame('books', $fk->getSchemaName());
        $this->assertSame('book', $fk->getEntityName());
    }

    public function testSetDefaultJoin()
    {
        $fk = new Relation();
        $fk->setDefaultJoin('INNER');

        $this->assertSame('INNER', $fk->getDefaultJoin());
    }

    public function testSetNames()
    {
        $fk = new Relation();
        $fk->setName('book_author');
        $fk->setField('author');
        $fk->setRefField('books');

        $this->assertSame('book_author', $fk->getName());
        $this->assertSame('author', $fk->getField());
        $this->assertSame('books', $fk->getRefField());
    }

    public function testSkipSql()
    {
        $fk = new Relation();
        $fk->setSkipSql(true);

        $this->assertTrue($fk->isSkipSql());
    }

    public function testGetOnActionBehaviors()
    {
        $fk = new Relation();
        $fk->setOnUpdate('SETNULL');
        $fk->setOnDelete('CASCADE');

        $this->assertSame('SET NULL', $fk->getOnUpdate());
        $this->assertTrue($fk->hasOnUpdate());

        $this->assertSame('CASCADE', $fk->getOnDelete());
        $this->assertTrue($fk->hasOnDelete());
    }

    /**
     * @dataProvider provideOnActionBehaviors
     *
     */
    public function testNormalizeRelation($behavior, $normalized)
    {
        $fk = new Relation();

        $this->assertSame($normalized, $fk->normalizeFKey($behavior));
    }

    public function provideOnActionBehaviors()
    {
        return array(
            array(null, ''),
            array('none', ''),
            array('NONE', ''),
            array('setnull', 'SET NULL'),
            array('SETNULL', 'SET NULL'),
            array('cascade', 'CASCADE'),
            array('CASCADE', 'CASCADE'),
        );
    }
}
