<?php

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\SqlDefaultPlatform;

class EntityDiffTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultObjectState()
    {
        $fromEntity = new Entity('article');
        $toEntity   = new Entity('article');

        $diff = $this->createEntityDiff($fromEntity, $toEntity);

        $this->assertSame($fromEntity, $diff->getFromEntity());
        $this->assertSame($toEntity, $diff->getToEntity());
        $this->assertFalse($diff->hasAddedFields());
        $this->assertFalse($diff->hasAddedFks());
        $this->assertFalse($diff->hasAddedIndices());
        $this->assertFalse($diff->hasAddedPkFields());
        $this->assertFalse($diff->hasModifiedFields());
        $this->assertFalse($diff->hasModifiedFks());
        $this->assertFalse($diff->hasModifiedIndices());
        $this->assertFalse($diff->hasModifiedPk());
        $this->assertFalse($diff->hasRemovedFields());
        $this->assertFalse($diff->hasRemovedFks());
        $this->assertFalse($diff->hasRemovedIndices());
        $this->assertFalse($diff->hasRemovedPkFields());
        $this->assertFalse($diff->hasRenamedFields());
        $this->assertFalse($diff->hasRenamedPkFields());
    }

    public function testSetAddedFields()
    {
        $field = new Field('is_published', 'boolean');

        $diff = $this->createEntityDiff();
        $diff->setAddedFields([ $field ]);

        $this->assertCount(1, $diff->getAddedFields());
        $this->assertSame($field, $diff->getAddedField('isPublished'));
        $this->assertTrue($diff->hasAddedFields());
    }

    public function testRemoveAddedField()
    {
        $diff = $this->createEntityDiff();
        $diff->addAddedField('is_published', new Field('is_published'));
        $diff->removeAddedField('is_published');

        $this->assertEmpty($diff->getAddedFields());
        $this->assertNull($diff->getAddedField('isPublished'));
        $this->assertFalse($diff->hasAddedFields());
    }

    public function testSetRemovedFields()
    {
        $field = new Field('is_active');

        $diff = $this->createEntityDiff();
        $diff->setRemovedFields([ $field ]);

        $this->assertCount(1, $diff->getRemovedFields());
        $this->assertSame($field, $diff->getRemovedField('is_active'));
        $this->assertTrue($diff->hasRemovedFields());
    }

    public function testSetRemoveRemovedField()
    {
        $diff = $this->createEntityDiff();

        $this->assertNull($diff->getRemovedField('is_active'));

        $diff->addRemovedField('is_active', new Field('is_active'));
        $diff->removeRemovedField('is_active');

        $this->assertFalse($diff->hasRemovedFields());
    }

    public function testSetModifiedFields()
    {
        $fieldDiff = new FieldDiff();

        $diff = $this->createEntityDiff();
        $diff->setModifiedFields([ 'title' => $fieldDiff ]);

        $this->assertCount(1, $diff->getModifiedFields());
        $this->assertTrue($diff->hasModifiedFields());
    }

    public function testAddRenamedField()
    {
        $fromField = new Field('is_published', 'boolean');
        $toField   = new Field('is_active', 'boolean');

        $diff = $this->createEntityDiff();
        $diff->setRenamedFields([ [ $fromField, $toField ] ]);

        $this->assertCount(1, $diff->getRenamedFields());
        $this->assertTrue($diff->hasRenamedFields());
    }

    public function testSetAddedPkFields()
    {
        $field = new Field('id', 'integer', 7);
        $field->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setAddedPkFields([ $field ]);

        $this->assertCount(1, $diff->getAddedPkFields());
        $this->assertTrue($diff->hasAddedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testRemoveAddedPkField()
    {
        $field = new Field('id', 'integer', 7);
        $field->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setAddedPkFields([ $field ]);
        $diff->removeAddedPkField('id');

        $this->assertEmpty($diff->getRemovedPkFields());
        $this->assertFalse($diff->hasAddedPkFields());
    }

    /**
     * @expectedException \Propel\Generator\Exception\DiffException
     */
    public function testCantAddNonPrimaryKeyField()
    {
        $diff = $this->createEntityDiff();
        $diff->addAddedPkField('id', new Field('id', 'integer'));
    }

    public function testSetRemovedPkFields()
    {
        $field = new Field('id', 'integer');
        $field->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setRemovedPkFields([ $field ]);

        $this->assertCount(1, $diff->getRemovedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testRemoveRemovedPkField()
    {
        $diff = $this->createEntityDiff();
        $diff->addRemovedPkField('id', new Field('id', 'integer'));
        $diff->removeRemovedPkField('id');

        $this->assertEmpty($diff->getRemovedPkFields());
    }

    public function testSetRenamedPkFields()
    {
        $diff = $this->createEntityDiff();
        $diff->setRenamedPkFields([ [ new Field('id', 'integer'), new Field('post_id', 'integer') ] ]);

        $this->assertCount(1, $diff->getRenamedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetAddedIndices()
    {
        $entity = new Entity();
        $entity->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($entity);

        $diff = $this->createEntityDiff();
        $diff->setAddedIndices([ $index ]);

        $this->assertCount(1, $diff->getAddedIndices());
        $this->assertTrue($diff->hasAddedIndices());
    }

    public function testSetRemovedIndices()
    {
        $entity = new Entity();
        $entity->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($entity);

        $diff = $this->createEntityDiff();
        $diff->setRemovedIndices([ $index ]);

        $this->assertCount(1, $diff->getRemovedIndices());
        $this->assertTrue($diff->hasRemovedIndices());
    }

    public function testSetModifiedIndices()
    {
        $entity = new Entity('users');
        $entity->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('username_unique_idx');
        $fromIndex->setEntity($entity);
        $fromIndex->setFields([ new Field('username') ]);

        $toIndex = new Index('username_unique_idx');
        $toIndex->setEntity($entity);
        $toIndex->setFields([ new Field('client_id'), new Field('username') ]);

        $diff = $this->createEntityDiff();
        $diff->setModifiedIndices([ [ $fromIndex, $toIndex ]]);

        $this->assertCount(1, $diff->getModifiedIndices());
        $this->assertTrue($diff->hasModifiedIndices());
    }

    public function testSetAddedFks()
    {
        $fk = new Relation('fk_blog_author');

        $diff = $this->createEntityDiff();
        $diff->setAddedFks([ $fk ]);

        $this->assertCount(1, $diff->getAddedFks());
        $this->assertTrue($diff->hasAddedFks());
    }

    public function testRemoveAddedFk()
    {
        $diff = $this->createEntityDiff();
        $diff->addAddedFk('fk_blog_author', new Relation('fk_blog_author'));
        $diff->removeAddedFk('fk_blog_author');

        $this->assertEmpty($diff->getAddedFks());
        $this->assertFalse($diff->hasAddedFks());
    }

    public function testSetRemovedFk()
    {
        $diff = $this->createEntityDiff();
        $diff->setRemovedFks([ new Relation('fk_blog_post_author') ]);

        $this->assertCount(1, $diff->getRemovedFks());
        $this->assertTrue($diff->hasRemovedFks());
    }

    public function testRemoveRemovedFk()
    {
        $diff = $this->createEntityDiff();
        $diff->addRemovedFk('blog_post_author', new Relation('blog_post_author'));
        $diff->removeRemovedFk('blog_post_author');

        $this->assertEmpty($diff->getRemovedFks());
        $this->assertFalse($diff->hasRemovedFks());
    }

    public function testSetModifiedFks()
    {
        $diff = $this->createEntityDiff();
        $diff->setModifiedFks([ [ new Relation('blog_post_author'), new Relation('blog_post_has_author') ] ]);

        $this->assertCount(1, $diff->getModifiedFks());
        $this->assertTrue($diff->hasModifiedFks());
    }

    public function testGetSimpleReverseDiff()
    {
        $entityA = new Entity('users');
        $entityB = new Entity('users');

        $diff = $this->createEntityDiff($entityA, $entityB);
        $reverseDiff = $diff->getReverseDiff();

        $this->assertInstanceOf('Propel\Generator\Model\Diff\EntityDiff', $reverseDiff);
        $this->assertSame($entityA, $reverseDiff->getToEntity());
        $this->assertSame($entityB, $reverseDiff->getFromEntity());
    }

    public function testReverseDiffHasModifiedFields()
    {
        $c1 = new Field('title', 'varchar', 50);
        $c2 = new Field('title', 'varchar', 100);

        $fieldDiff = new FieldDiff($c1, $c2);
        $reverseFieldDiff = $fieldDiff->getReverseDiff();

        $diff = $this->createEntityDiff();
        $diff->addModifiedField('title', $fieldDiff);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFields());
        $this->assertEquals([ 'title' => $reverseFieldDiff ], $reverseDiff->getModifiedFields());
    }

    public function testReverseDiffHasRemovedFields()
    {
        $field = new Field('slug', 'varchar', 100);

        $diff = $this->createEntityDiff();
        $diff->addAddedField('slug', $field);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ 'slug' => $field], $reverseDiff->getRemovedFields());
        $this->assertSame($field, $reverseDiff->getRemovedField('slug'));
    }

    public function testReverseDiffHasAddedFields()
    {
        $field = new Field('slug', 'varchar', 100);

        $diff = $this->createEntityDiff();
        $diff->addRemovedField('slug', $field);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ 'slug' => $field], $reverseDiff->getAddedFields());
        $this->assertSame($field, $reverseDiff->getAddedField('slug'));
    }

    public function testReverseDiffHasRenamedFields()
    {
        $fieldA = new Field('login', 'varchar', 15);
        $fieldB = new Field('username', 'varchar', 15);

        $diff = $this->createEntityDiff();
        $diff->addRenamedField($fieldA, $fieldB);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ [ $fieldB, $fieldA ] ], $reverseDiff->getRenamedFields());
    }

    public function testReverseDiffHasAddedPkFields()
    {
        $field = new Field('client_id', 'integer');
        $field->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->addRemovedPkField('client_id', $field);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertCount(1, $reverseDiff->getAddedPkFields());
        $this->assertTrue($reverseDiff->hasAddedPkFields());
    }

    public function testReverseDiffHasRemovedPkFields()
    {
        $field = new Field('client_id', 'integer');
        $field->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->addAddedPkField('client_id', $field);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertCount(1, $reverseDiff->getRemovedPkFields());
        $this->assertTrue($reverseDiff->hasRemovedPkFields());
    }

    public function testReverseDiffHasRenamedPkField()
    {
        $fromField = new Field('post_id', 'integer');
        $fromField->setPrimaryKey();

        $toField = new Field('id', 'integer');
        $toField->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->addRenamedPkField($fromField, $toField);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRenamedPkFields());
        $this->assertSame([[ $toField, $fromField ]], $reverseDiff->getRenamedPkFields());
    }

    public function testReverseDiffHasAddedIndices()
    {
        $entity = new Entity();
        $entity->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($entity);

        $diff = $this->createEntityDiff();
        $diff->addRemovedIndex('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedIndices());
        $this->assertCount(1, $reverseDiff->getAddedIndices());
    }

    public function testReverseDiffHasRemovedIndices()
    {
        $entity = new Entity();
        $entity->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($entity);

        $diff = $this->createEntityDiff();
        $diff->addAddedIndex('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedIndices());
        $this->assertCount(1, $reverseDiff->getRemovedIndices());
    }

    public function testReverseDiffHasModifiedIndices()
    {
        $entity = new Entity();
        $entity->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('i1');
        $fromIndex->setEntity($entity);

        $toIndex = new Index('i1');
        $toIndex->setEntity($entity);

        $diff = $this->createEntityDiff();
        $diff->addModifiedIndex('i1', $fromIndex, $toIndex);

        $reverseDiff = $diff->getReverseDiff();

        $this->assertTrue($reverseDiff->hasModifiedIndices());
        $this->assertSame([ 'i1' => [ $toIndex, $fromIndex ]], $reverseDiff->getModifiedIndices());
    }

    public function testReverseDiffHasRemovedFks()
    {
        $diff = $this->createEntityDiff();
        $diff->addAddedFk('fk_post_author', new Relation('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedFks());
        $this->assertCount(1, $reverseDiff->getRemovedFks());
    }

    public function testReverseDiffHasAddedFks()
    {
        $diff = $this->createEntityDiff();
        $diff->addRemovedFk('fk_post_author', new Relation('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedFks());
        $this->assertCount(1, $reverseDiff->getAddedFks());
    }

    public function testReverseDiffHasModifiedFks()
    {
        $fromFk = new Relation('fk_1');
        $toFk = new Relation('fk_1');

        $diff = $this->createEntityDiff();
        $diff->addModifiedFk('fk_1', $fromFk, $toFk);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFks());
        $this->assertSame([ 'fk1' => [ $toFk, $fromFk ]], $reverseDiff->getModifiedFks());
    }

    private function createEntityDiff(Entity $fromEntity = null, Entity $toEntity = null)
    {
        if (null === $fromEntity) {
            $fromEntity = new Entity('users');
        }

        if (null === $toEntity) {
            $toEntity = new Entity('users');
        }

        return new EntityDiff($fromEntity, $toEntity);
    }

    public function testToString()
    {
        $entityA = new Entity('A');
        $entityB = new Entity('B');

        $diff = new EntityDiff($entityA, $entityB);
        $diff->addAddedField('id', new Field('id', 'integer'));
        $diff->addRemovedField('category_id', new Field('category_id', 'integer'));

        $colFoo = new Field('foo', 'integer');
        $colBar = new Field('bar', 'integer');
        $entityA->addField($colFoo);
        $entityA->addField($colBar);

        $diff->addRenamedField($colFoo, $colBar);
        $fieldDiff = new FieldDiff($colFoo, $colBar);
        $diff->addModifiedField('foo', $fieldDiff);

        $fk = new Relation('category');
        $fk->setEntity($entityA);
        $fk->setForeignEntityName('B');
        $fk->addReference('category_id', 'id');
        $fkChanged = clone $fk;
        $fkChanged->setForeignEntityName('C');
        $fkChanged->addReference('bla', 'id2');
        $fkChanged->setOnDelete('cascade');
        $fkChanged->setOnUpdate('cascade');

        $diff->addAddedFk('category', $fk);
        $diff->addModifiedFk('category', $fk, $fkChanged);
        $diff->addRemovedFk('category', $fk);

        $index = new Index('test_index');
        $index->setEntity($entityA);
        $index->setFields([$colFoo]);

        $indexChanged = clone $index;
        $indexChanged->setFields([$colBar]);

        $diff->addAddedIndex('test_index', $index);
        $diff->addModifiedIndex('test_index', $index, $indexChanged);
        $diff->addRemovedIndex('test_index', $index);

        $string = (string) $diff;

        $expected = '  A:
    addedFields:
      - id
    removedFields:
      - category_id
    modifiedFields:
      A.FOO:
        modifiedProperties:
    renamedFields:
      foo: bar
    addedIndices:
      - test_index
    removedIndices:
      - test_index
    modifiedIndices:
      - test_index
    addedFks:
      - category
    removedFks:
      - category
    modifiedFks:
      category:
          localFields: from ["categoryId"] to ["categoryId","bla"]
          foreignFields: from ["id"] to ["id","id2"]
          onUpdate: from  to CASCADE
          onDelete: from  to CASCADE
';

        $this->assertEquals($expected, $string);
    }

    public function testMagicClone()
    {
        $diff = new EntityDiff(new Entity('A'), new Entity('B'));

        $clonedDiff = clone $diff;

        $this->assertNotSame($clonedDiff, $diff);
        $this->assertNotSame($clonedDiff->getFromEntity(), $diff->getFromEntity());
        $this->assertNotSame($clonedDiff->getToEntity(), $diff->getToEntity());
    }
}
