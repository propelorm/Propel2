<?php

use Propel\Generator\Behavior\Sluggable\SluggableBehavior;
use Propel\Generator\Behavior\Timestampable\TimestampableBehavior;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\VendorInfo;
use Propel\Generator\Platform\MysqlPlatform;

/* Fields */
$field11 = new Field('id', 'integer', 7);
$field11->setAutoIncrement();
$field11->setNotNull();
$field11->setPrimaryKey();
$field12 = new Field('authorId', 'smallint', 3);
$field12->setNotNull();
$field13 = new Field('categoryId', 'tinyint', 2);
$field13->setNotNull();
$field14 = new Field('title', 'varchar', 100);
$field14->setNotNull();
$field15 = new Field('body', 'clob');
$field16 = new Field('averageRating', 'float', 2);
$field16->setScale(2);
$field16->setDescription('The post rating in percentage');

$field21 = new Field('id', 'smallint', 3);
$field21->setAutoIncrement();
$field21->setNotNull();
$field21->setPrimaryKey();
$field22 = new Field('username', 'varchar', 15);
$field22->setNotNull();
$field23 = new Field('password', 'varchar', 40);
$field23->setNotNull();

$field31 = new Field('id', 'tinyint', 2);
$field31->setAutoIncrement();
$field31->setNotNull();
$field31->setPrimaryKey();
$field32 = new Field('name', 'varchar', 40);
$field32->setNotNull();

$field41 = new Field('id', 'integer', 7);
$field41->setAutoIncrement();
$field41->setNotNull();
$field41->setPrimaryKey();
$field42 = new Field('name', 'varchar', 40);
$field42->setNotNull();

$field51 = new Field('postId', 'integer', 7);
$field51->setNotNull();
$field51->setPrimaryKey();
$field52 = new Field('tagId', 'integer', 7);
$field52->setNotNull();
$field52->setPrimaryKey();

$field61 = new Field('id', 'integer', 5);
$field61->setNotNull();
$field61->setAutoIncrement();
$field61->setPrimaryKey();
$field62 = new Field('title', 'varchar', 150);
$field62->setNotNull();
$field63 = new Field('content', 'clob');
$field63->addVendorInfo(new VendorInfo('mysql', [
    'Charset' => 'latin1',
    'Collate' => 'latin1_general_ci',
]));
$field64 = new Field('isPublished', 'boolean');
$field64->setNotNull();
$field64->setDefaultValue('false');

/* Foreign Keys */
$fkAuthorPost = new Relation('fk_post_has_author');
$fkAuthorPost->addReference('authorId', 'id');
$fkAuthorPost->setForeignEntityName('BlogAuthor');
$fkAuthorPost->setRefField('posts');
$fkAuthorPost->setField('author');
$fkAuthorPost->setDefaultJoin('Criteria::LEFT_JOIN');
$fkAuthorPost->setOnDelete('CASCADE');

$fkCategoryPost = new Relation('fk_post_has_category');
$fkCategoryPost->addReference('categoryId', 'id');
$fkCategoryPost->setForeignEntityName('BlogCategory');
$fkCategoryPost->setRefField('posts');
$fkCategoryPost->setField('category');
$fkCategoryPost->setDefaultJoin('Criteria::INNER_JOIN');
$fkCategoryPost->setOnDelete('SETNULL');

$fkPostTag = new Relation('fk_post_has_tags');
$fkPostTag->addReference('postId', 'id');
$fkPostTag->setForeignEntityName('BlogPost');
$fkPostTag->setField('post');
$fkPostTag->setDefaultJoin('Criteria::LEFT_JOIN');
$fkPostTag->setOnDelete('CASCADE');

$fkTagPost = new Relation('fk_tag_has_posts');
$fkTagPost->addReference('tagId', 'id');
$fkTagPost->setForeignEntityName('BlogTag');
$fkTagPost->setField('tag');
$fkTagPost->setDefaultJoin('Criteria::LEFT_JOIN');
$fkTagPost->setOnDelete('CASCADE');

/* Regular Indexes */
$pageContentFulltextIdx = new Index('page_content_fulltext_idx');
$pageContentFulltextIdx->setFields([ [ 'name' => 'content' ] ]);
$pageContentFulltextIdx->addVendorInfo(new VendorInfo('mysql', array('Index_type' => 'FULLTEXT')));

/* Unique Indexes */
$authorUsernameUnique = new Unique('author_password_unique_idx');
$authorUsernameUnique->setFields([ [ 'name' => 'username', 'size' => '8' ] ]);

/* Behaviors */
$timestampableBehavior = new TimestampableBehavior();
$timestampableBehavior->setName('timestampable');
$sluggableBehavior = new SluggableBehavior();
$sluggableBehavior->setName('sluggable');
$sluggableBehavior->setParameter('slug_pattern', '/posts/{Title}');

/* Entities */
$entity1 = new Entity('BlogPost');
$entity1->setDescription('The list of posts');
$entity1->setNamespace('Blog');
$entity1->setPackage('Acme.Blog');
$entity1->addFields([ $field11, $field12, $field13, $field14, $field15, $field16 ]);
$entity1->addRelations([ $fkAuthorPost, $fkCategoryPost ]);
$entity1->addBehavior($timestampableBehavior);
$entity1->addBehavior($sluggableBehavior);

$entity2 = new Entity('BlogAuthor');
$entity2->setDescription('The list of authors');
$entity2->setNamespace('Blog');
$entity2->setPackage('Acme.Blog');
$entity2->addFields([ $field21, $field22, $field23 ]);
$entity2->addUnique($authorUsernameUnique);

$entity3 = new Entity('BlogCategory');
$entity3->setDescription('The list of categories');
$entity3->setNamespace('Blog');
$entity3->setPackage('Acme.Blog');
$entity3->addFields([ $field31, $field32 ]);

$entity4 = new Entity('BlogTag');
$entity4->setDescription('The list of tags');
$entity4->setNamespace('Blog');
$entity4->setPackage('Acme.Blog');
$entity4->addFields([ $field41, $field42 ]);

$entity5 = new Entity('BlogPostTag');
$entity5->setNamespace('Blog');
$entity5->setPackage('Acme.Blog');
$entity5->setCrossRef();
$entity5->addFields([ $field51, $field52 ]);
$entity5->addRelations([ $fkPostTag, $fkTagPost ]);

$entity6 = new Entity('CmsPage');
$entity6->setName('Page');
$entity6->setTableName('cms_page');
$entity6->setNamespace('Cms');
$entity6->setPackage('Acme.Cms');
$entity6->addFields([ $field61, $field62, $field63, $field64 ]);
$entity6->addIndex($pageContentFulltextIdx);
$entity6->addVendorInfo(new VendorInfo('mysql', array('Engine' => 'MyISAM')));

/* Database */
$database = new Database('acme_blog', new MysqlPlatform());
$database->setSchema('acme');
$database->setTablePrefix('acme_');
$database->setNamespace('Acme\\Model');
$database->setPackage('Acme');
$database->setHeavyIndexing();
$database->addVendorInfo(new VendorInfo('mysql', [ 'Engine' => 'InnoDB', 'Charset' => 'utf8' ]));
$database->addEntities([ $entity1, $entity2, $entity3, $entity4, $entity5, $entity6 ]);

return $database;
