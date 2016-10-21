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
        $relation = new Relation('book_author');

        $this->assertSame('bookAuthor', $relation->getName());
        $this->assertFalse($relation->hasOnUpdate());
        $this->assertFalse($relation->hasOnDelete());
        $this->assertFalse($relation->isComposite());
        $this->assertFalse($relation->isSkipSql());
    }

    public function testRelationIsForeignPrimaryKey()
    {
        $database     = $this->getDatabaseMock('bookstore');
        $platform     = $this->getPlatformMock();
        $foreignEntity = $this->getEntityMock('Authors');

        $localEntity   = $this->getEntityMock('Books', array(
            'platform' => $platform,
            'database' => $database
        ));

        $idField     = $this->getFieldMock('id');
        $authorIdField = $this->getFieldMock('authorId');

        $database
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('Authors'))
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
            ->with($this->equalTo('authorId'))
            ->will($this->returnValue($authorIdField))
        ;

        $relation = new Relation();
        $relation->setEntity($localEntity);
        $relation->setForeignEntityName('Authors');
        $relation->addReference('authorId', 'id');

        $relationMapping = $relation->getFieldObjectsMapping();

        $this->assertTrue($relation->isForeignPrimaryKey());
        $this->assertCount(1, $relation->getForeignFieldObjects());
        $this->assertSame($authorIdField, $relationMapping[0]['local']);
        $this->assertSame($idField, $relationMapping[0]['foreign']);
        $this->assertSame($idField, $relation->getForeignField(0));
    }

    public function testRelationDoesNotUseRequiredFields()
    {
        $field = $this->getFieldMock('authorId');
        $field
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(false))
        ;

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->once())
            ->method('getField')
            ->with($this->equalTo('authorId'))
            ->will($this->returnValue($field))
        ;

        $relation = new Relation();
        $relation->setEntity($entity);
        $relation->addReference('authorId', 'id');

        $this->assertFalse($relation->isLocalFieldsRequired());
    }

    public function testRelationUsesRequiredFields()
    {
        $field = $this->getFieldMock('authorId');
        $field
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(true))
        ;

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->once())
            ->method('getField')
            ->with($this->equalTo('authorId'))
            ->will($this->returnValue($field))
        ;

        $relation = new Relation();
        $relation->setEntity($entity);
        $relation->addReference('authorId', 'id');

        $this->assertTrue($relation->isLocalFieldsRequired());
    }

    public function testCantGetInverseRelation()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(false);
        $foreignEntity = $this->getEntityMock('Authors');

        $localEntity = $this->getEntityMock('Books', array(
            'platform' => $platform,
            'database' => $database
        ));

        $database
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('Authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $inversedFk = new Relation();
        $inversedFk->addReference('id', 'authorId');
        $inversedFk->setEntity($localEntity);

        $foreignEntity
            ->expects($this->any())
            ->method('getRelations')
            ->will($this->returnValue(array()))
        ;

        $relation = new Relation();
        $relation->setEntity($localEntity);
        $relation->addReference('authorId', 'id');
        $relation->setForeignEntityName('Authors');

        $this->assertSame('Authors', $relation->getForeignEntityName());
        $this->assertNull($relation->getInverseFK());
        $this->assertFalse($relation->isMatchedByInverseFK());
    }

    public function testGetInverseRelation()
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(true);
        $foreignEntity = $this->getEntityMock('Authors');

        $localEntity = $this->getEntityMock('Books', array(
            'platform' => $platform,
            'database' => $database
        ));

        $database
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('Authors'))
            ->will($this->returnValue($foreignEntity))
        ;

        $inversedFk = new Relation();
        $inversedFk->addReference('id', 'authorId');
        $inversedFk->setEntity($localEntity);

        $foreignEntity
            ->expects($this->any())
            ->method('getRelations')
            ->will($this->returnValue(array($inversedFk)))
        ;

        $relation = new Relation();
        $relation->setEntity($localEntity);
        $relation->addReference('authorId', 'id');
        $relation->setForeignEntityName('Authors');

        $this->assertSame('Authors', $relation->getForeignEntityName());
        $this->assertInstanceOf('Propel\Generator\Model\Entity', $relation->getForeignEntity());
        $this->assertSame($inversedFk, $relation->getInverseFK());
        $this->assertTrue($relation->isMatchedByInverseFK());
    }

    public function testGetLocalField()
    {
        $field = $this->getFieldMock('id');

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->any())
            ->method('getField')
            ->with($this->equalTo('authorId'))
            ->will($this->returnValue($field))
        ;

        $relation = new Relation();
        $relation->setEntity($entity);
        $relation->addReference('authorId', 'id');

        $this->assertCount(1, $relation->getLocalFieldObjects());
        $this->assertInstanceOf('Propel\Generator\Model\Field', $relation->getLocalField(0));
    }

    public function testRelationIsNotLocalPrimaryKey()
    {
        $pks = array($this->getFieldMock('id'));

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $relation = new Relation();
        $relation->setEntity($entity);
        $relation->addReference('bookId', 'id');

        $this->assertFalse($relation->isLocalPrimaryKey());
    }

    public function testRelationIsLocalPrimaryKey()
    {
        $pks = array(
            $this->getFieldMock('bookId'),
            $this->getFieldMock('authorId'),
        );

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $relation = new Relation();
        $relation->setEntity($entity);
        $relation->addReference('bookId', 'id');
        $relation->addReference('authorId', 'id');

        $this->assertTrue($relation->isLocalPrimaryKey());
    }

    public function testGetOtherRelations()
    {
        $relation = new Relation();

        $relations[] = new Relation();
        $relations[] = $relation;
        $relations[] = new Relation();

        $entity = $this->getEntityMock('Books');
        $entity
            ->expects($this->once())
            ->method('getRelations')
            ->will($this->returnValue($relations))
        ;

        $relation->setEntity($entity);

        $this->assertCount(2, $relation->getOtherFks());
    }

    /*
    public function testSetForeignSchemaName()
    {
        $relation = new Relation();
        $relation->setForeignSchemaName('authors');

        $this->assertSame('authors', $relation->getForeignSchemaName());
    }
*/

    public function testClearReferences()
    {
        $relation = new Relation();
        $relation->addReference('bookId', 'id');
        $relation->addReference('authorId', 'id');
        $relation->clearReferences();

        $this->assertCount(0, $relation->getLocalFields());
        $this->assertCount(0, $relation->getForeignFields());
    }

    public function testAddMultipleReferences()
    {
        $relation = new Relation();
        $relation->addReference('bookId', 'id');
        $relation->addReference('authorId', 'id');

        $this->assertTrue($relation->isComposite());
        $this->assertCount(2, $relation->getLocalFields());
        $this->assertCount(2, $relation->getForeignFields());

        $this->assertSame('bookId', $relation->getLocalFieldName(0));
        $this->assertSame('id', $relation->getForeignFieldName(0));
        $this->assertSame('id', $relation->getMappedForeignField('bookId'));

        $this->assertSame('authorId', $relation->getLocalFieldName(1));
        $this->assertSame('id', $relation->getForeignFieldName(1));
        $this->assertSame('id', $relation->getMappedForeignField('authorId'));
    }

    public function testAddSingleStringReference()
    {
        $relation = new Relation();
        $relation->addReference('authorId', 'id');

        $this->assertFalse($relation->isComposite());
        $this->assertCount(1, $relation->getLocalFields());
        $this->assertCount(1, $relation->getForeignFields());

        $this->assertSame('authorId', $relation->getMappedLocalField('id'));
    }

    public function testAddSingleArrayReference()
    {
        $reference = array('local' => 'authorId', 'foreign' => 'id');

        $relation = new Relation();
        $relation->addReference($reference);

        $this->assertFalse($relation->isComposite());
        $this->assertCount(1, $relation->getLocalFields());
        $this->assertCount(1, $relation->getForeignFields());

        $this->assertSame($reference['local'], $relation->getMappedLocalField($reference['foreign']));
    }

    public function testAddSingleFieldReference()
    {
        $relation = new Relation();
        $relation->addReference(
            $this->getFieldMock('authorId'),
            $this->getFieldMock('id')
        );

        $this->assertFalse($relation->isComposite());
        $this->assertCount(1, $relation->getLocalFields());
        $this->assertCount(1, $relation->getForeignFields());

        $this->assertSame('authorId', $relation->getMappedLocalField('id'));
    }

    public function testSetEntity()
    {
        $entity = $this->getEntityMock('Book');
        $entity
            ->expects($this->once())
            ->method('getSchema')
            ->will($this->returnValue('books'))
        ;

        $relation = new Relation();
        $relation->setEntity($entity);

        $this->assertInstanceOf('Propel\Generator\Model\Entity', $relation->getEntity());
        $this->assertSame('books', $relation->getSchemaName());
        $this->assertSame('Book', $relation->getEntityName());
    }

    public function testSetDefaultJoin()
    {
        $relation = new Relation();
        $relation->setDefaultJoin('INNER');

        $this->assertSame('INNER', $relation->getDefaultJoin());
    }

    public function testSetNames()
    {
        $relation = new Relation();
        $relation->setName('book_author');

        $this->assertSame('bookAuthor', $relation->getName());
        $this->assertSame('book_author', $relation->getSqlName());
    }

    public function testSkipSql()
    {
        $relation = new Relation();
        $relation->setSkipSql(true);

        $this->assertTrue($relation->isSkipSql());
    }

    public function testGetOnActionBehaviors()
    {
        $relation = new Relation();
        $relation->setOnUpdate('SETNULL');
        $relation->setOnDelete('CASCADE');

        $this->assertSame('SET NULL', $relation->getOnUpdate());
        $this->assertTrue($relation->hasOnUpdate());

        $this->assertSame('CASCADE', $relation->getOnDelete());
        $this->assertTrue($relation->hasOnDelete());
    }

    /**
     * @dataProvider provideOnActionBehaviors
     *
     */
    public function testNormalizeRelation($behavior, $normalized)
    {
        $relation = new Relation();

        $this->assertSame($normalized, $relation->normalizeFKey($behavior));
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
