<?php

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\FieldDiff;
use Propel\Generator\Model\Diff\EntityDiff;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
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
        $column = new Field('is_published', 'boolean');

        $diff = $this->createEntityDiff();
        $diff->setAddedFields([ $column ]);

        $this->assertCount(1, $diff->getAddedFields());
        $this->assertSame($column, $diff->getAddedField('is_published'));
        $this->assertTrue($diff->hasAddedFields());
    }

    public function testRemoveAddedField()
    {
        $diff = $this->createEntityDiff();
        $diff->addAddedField('is_published', new Field('is_published'));
        $diff->removeAddedField('is_published');

        $this->assertEmpty($diff->getAddedFields());
        $this->assertNull($diff->getAddedField('is_published'));
        $this->assertFalse($diff->hasAddedFields());
    }

    public function testSetRemovedFields()
    {
        $column = new Field('is_active');

        $diff = $this->createEntityDiff();
        $diff->setRemovedFields([ $column ]);

        $this->assertCount(1, $diff->getRemovedFields());
        $this->assertSame($column, $diff->getRemovedField('is_active'));
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
        $columnDiff = new FieldDiff();

        $diff = $this->createEntityDiff();
        $diff->setModifiedFields([ 'title' => $columnDiff ]);

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
        $column = new Field('id', 'integer', 7);
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setAddedPkFields([ $column ]);

        $this->assertCount(1, $diff->getAddedPkFields());
        $this->assertTrue($diff->hasAddedPkFields());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testRemoveAddedPkField()
    {
        $column = new Field('id', 'integer', 7);
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setAddedPkFields([ $column ]);
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
        $column = new Field('id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->setRemovedPkFields([ $column ]);

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
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->setAddedIndices([ $index ]);

        $this->assertCount(1, $diff->getAddedIndices());
        $this->assertTrue($diff->hasAddedIndices());
    }

    public function testSetRemovedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->setRemovedIndices([ $index ]);

        $this->assertCount(1, $diff->getRemovedIndices());
        $this->assertTrue($diff->hasRemovedIndices());
    }

    public function testSetModifiedIndices()
    {
        $table = new Entity('users');
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('username_unique_idx');
        $fromIndex->setEntity($table);
        $fromIndex->setFields([ new Field('username') ]);

        $toIndex = new Index('username_unique_idx');
        $toIndex->setEntity($table);
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
        $tableA = new Entity('users');
        $tableB = new Entity('users');

        $diff = $this->createEntityDiff($tableA, $tableB);
        $reverseDiff = $diff->getReverseDiff();

        $this->assertInstanceOf('Propel\Generator\Model\Diff\EntityDiff', $reverseDiff);
        $this->assertSame($tableA, $reverseDiff->getToEntity());
        $this->assertSame($tableB, $reverseDiff->getFromEntity());
    }

    public function testReverseDiffHasModifiedFields()
    {
        $c1 = new Field('title', 'varchar', 50);
        $c2 = new Field('title', 'varchar', 100);

        $columnDiff = new FieldDiff($c1, $c2);
        $reverseFieldDiff = $columnDiff->getReverseDiff();

        $diff = $this->createEntityDiff();
        $diff->addModifiedField('title', $columnDiff);
        
        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFields());
        $this->assertEquals([ 'title' => $reverseFieldDiff ], $reverseDiff->getModifiedFields());
    }

    public function testReverseDiffHasRemovedFields()
    {
        $column = new Field('slug', 'varchar', 100);

        $diff = $this->createEntityDiff();
        $diff->addAddedField('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ 'slug' => $column], $reverseDiff->getRemovedFields());
        $this->assertSame($column, $reverseDiff->getRemovedField('slug'));
    }

    public function testReverseDiffHasAddedFields()
    {
        $column = new Field('slug', 'varchar', 100);

        $diff = $this->createEntityDiff();
        $diff->addRemovedField('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ 'slug' => $column], $reverseDiff->getAddedFields());
        $this->assertSame($column, $reverseDiff->getAddedField('slug'));
    }

    public function testReverseDiffHasRenamedFields()
    {
        $columnA = new Field('login', 'varchar', 15);
        $columnB = new Field('username', 'varchar', 15);

        $diff = $this->createEntityDiff();
        $diff->addRenamedField($columnA, $columnB);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([ [ $columnB, $columnA ] ], $reverseDiff->getRenamedFields());
    }

    public function testReverseDiffHasAddedPkFields()
    {
        $column = new Field('client_id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->addRemovedPkField('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertCount(1, $reverseDiff->getAddedPkFields());
        $this->assertTrue($reverseDiff->hasAddedPkFields());
    }

    public function testReverseDiffHasRemovedPkFields()
    {
        $column = new Field('client_id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createEntityDiff();
        $diff->addAddedPkField('client_id', $column);

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
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->addRemovedIndex('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedIndices());
        $this->assertCount(1, $reverseDiff->getAddedIndices());
    }

    public function testReverseDiffHasRemovedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setEntity($table);

        $diff = $this->createEntityDiff();
        $diff->addAddedIndex('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedIndices());
        $this->assertCount(1, $reverseDiff->getRemovedIndices());
    }

    public function testReverseDiffHasModifiedIndices()
    {
        $table = new Entity();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('i1');
        $fromIndex->setEntity($table);

        $toIndex = new Index('i1');
        $toIndex->setEntity($table);

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
        $this->assertSame([ 'fk_1' => [ $toFk, $fromFk ]], $reverseDiff->getModifiedFks());
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
        $tableA = new Entity('A');
        $tableB = new Entity('B');

        $diff = new EntityDiff($tableA, $tableB);
        $diff->addAddedField('id', new Field('id', 'integer'));
        $diff->addRemovedField('category_id', new Field('category_id', 'integer'));

        $colFoo = new Field('foo', 'integer');
        $colBar = new Field('bar', 'integer');
        $tableA->addField($colFoo);
        $tableA->addField($colBar);

        $diff->addRenamedField($colFoo, $colBar);
        $columnDiff = new FieldDiff($colFoo, $colBar);
        $diff->addModifiedField('foo', $columnDiff);

        $fk = new Relation('category');
        $fk->setEntity($tableA);
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
        $index->setEntity($tableA);
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
          localFields: from ["category_id"] to ["category_id","bla"]
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
