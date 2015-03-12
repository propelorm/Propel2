<?php

use Propel\Generator\Behavior\Sluggable\SluggableBehavior;
use Propel\Generator\Behavior\Timestampable\TimestampableBehavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\VendorInfo;
use Propel\Generator\Platform\MysqlPlatform;

/* Columns */
$column11 = new Column('id', 'integer', 7);
$column11->setAutoIncrement();
$column11->setNotNull();
$column11->setPrimaryKey();
$column12 = new Column('author_id', 'smallint', 3);
$column12->setNotNull();
$column13 = new Column('category_id', 'tinyint', 2);
$column13->setNotNull();
$column14 = new Column('title', 'varchar', 100);
$column14->setNotNull();
$column15 = new Column('body', 'clob');
$column16 = new Column('average_rating', 'float', 2);
$column16->setScale(2);
$column16->setDescription('The post rating in percentage');
$column17 = new Column('price_without_decimal_places', 'DECIMAL', 10);
$column17->setScale(0);
$column17->setDescription('The Price without decimal places');

$column21 = new Column('id', 'smallint', 3);
$column21->setAutoIncrement();
$column21->setNotNull();
$column21->setPrimaryKey();
$column22 = new Column('username', 'varchar', 15);
$column22->setNotNull();
$column23 = new Column('password', 'varchar', 40);
$column23->setNotNull();

$column31 = new Column('id', 'tinyint', 2);
$column31->setAutoIncrement();
$column31->setNotNull();
$column31->setPrimaryKey();
$column32 = new Column('name', 'varchar', 40);
$column32->setNotNull();

$column41 = new Column('id', 'integer', 7);
$column41->setAutoIncrement();
$column41->setNotNull();
$column41->setPrimaryKey();
$column42 = new Column('name', 'varchar', 40);
$column42->setNotNull();

$column51 = new Column('post_id', 'integer', 7);
$column51->setNotNull();
$column51->setPrimaryKey();
$column52 = new Column('tag_id', 'integer', 7);
$column52->setNotNull();
$column52->setPrimaryKey();

$column61 = new Column('id', 'integer', 5);
$column61->setNotNull();
$column61->setAutoIncrement();
$column61->setPrimaryKey();
$column62 = new Column('title', 'varchar', 150);
$column62->setNotNull();
$column63 = new Column('content', 'clob');
$column63->addVendorInfo(new VendorInfo('mysql', [
    'Charset' => 'latin1',
    'Collate' => 'latin1_general_ci',
]));
$column64 = new Column('is_published', 'boolean');
$column64->setNotNull();
$column64->setDefaultValue('false');

/* Foreign Keys */
$fkAuthorPost = new ForeignKey('fk_post_has_author');
$fkAuthorPost->addReference('author_id', 'id');
$fkAuthorPost->setForeignTableCommonName('blog_author');
$fkAuthorPost->setRefPhpName('Posts');
$fkAuthorPost->setPhpName('Author');
$fkAuthorPost->setDefaultJoin('Criteria::LEFT_JOIN');
$fkAuthorPost->setOnDelete('CASCADE');

$fkCategoryPost = new ForeignKey('fk_post_has_category');
$fkCategoryPost->addReference('category_id', 'id');
$fkCategoryPost->setForeignTableCommonName('blog_category');
$fkCategoryPost->setRefPhpName('Posts');
$fkCategoryPost->setPhpName('Category');
$fkCategoryPost->setDefaultJoin('Criteria::INNER_JOIN');
$fkCategoryPost->setOnDelete('SETNULL');

$fkPostTag = new ForeignKey('fk_post_has_tags');
$fkPostTag->addReference('post_id', 'id');
$fkPostTag->setForeignTableCommonName('blog_post');
$fkPostTag->setPhpName('Post');
$fkPostTag->setDefaultJoin('Criteria::LEFT_JOIN');
$fkPostTag->setOnDelete('CASCADE');

$fkTagPost = new ForeignKey('fk_tag_has_posts');
$fkTagPost->addReference('tag_id', 'id');
$fkTagPost->setForeignTableCommonName('blog_tag');
$fkTagPost->setPhpName('Tag');
$fkTagPost->setDefaultJoin('Criteria::LEFT_JOIN');
$fkTagPost->setOnDelete('CASCADE');

/* Regular Indexes */
$pageContentFulltextIdx = new Index('page_content_fulltext_idx');
$pageContentFulltextIdx->setColumns([ [ 'name' => 'content' ] ]);
$pageContentFulltextIdx->addVendorInfo(new VendorInfo('mysql', array('Index_type' => 'FULLTEXT')));

/* Unique Indexes */
$authorUsernameUnique = new Unique('author_password_unique_idx');
$authorUsernameUnique->setColumns([ [ 'name' => 'username', 'size' => '8' ] ]);

/* Behaviors */
$timestampableBehavior = new TimestampableBehavior();
$timestampableBehavior->setName('timestampable');
$sluggableBehavior = new SluggableBehavior();
$sluggableBehavior->setName('sluggable');

/* Tables */
$table1 = new Table('blog_post');
$table1->setDescription('The list of posts');
$table1->setNamespace('Blog');
$table1->setPackage('Acme.Blog');
$table1->addColumns([ $column11, $column12, $column13, $column14, $column15, $column16, $column17 ]);
$table1->addForeignKeys([ $fkAuthorPost, $fkCategoryPost ]);
$table1->addBehavior($timestampableBehavior);
$table1->addBehavior($sluggableBehavior);

$table2 = new Table('blog_author');
$table2->setDescription('The list of authors');
$table2->setNamespace('Blog');
$table2->setPackage('Acme.Blog');
$table2->addColumns([ $column21, $column22, $column23 ]);
$table2->addUnique($authorUsernameUnique);

$table3 = new Table('blog_category');
$table3->setDescription('The list of categories');
$table3->setNamespace('Blog');
$table3->setPackage('Acme.Blog');
$table3->addColumns([ $column31, $column32 ]);

$table4 = new Table('blog_tag');
$table4->setDescription('The list of tags');
$table4->setNamespace('Blog');
$table4->setPackage('Acme.Blog');
$table4->addColumns([ $column41, $column42 ]);

$table5 = new Table('blog_post_tag');
$table5->setNamespace('Blog');
$table5->setPackage('Acme.Blog');
$table5->setCrossRef();
$table5->addColumns([ $column51, $column52 ]);
$table5->addForeignKeys([ $fkPostTag, $fkTagPost ]);

$table6 = new Table('cms_page');
$table6->setPhpName('Page');
$table6->setNamespace('Cms');
$table6->setBaseClass('Acme\\Model\\PublicationActiveRecord');
$table6->setPackage('Acme.Cms');
$table6->addColumns([ $column61, $column62, $column63, $column64 ]);
$table6->addIndex($pageContentFulltextIdx);
$table6->addVendorInfo(new VendorInfo('mysql', array('Engine' => 'MyISAM')));

/* Database */
$database = new Database('acme_blog', new MysqlPlatform());
$database->setSchema('acme');
$database->setTablePrefix('acme_');
$database->setNamespace('Acme\\Model');
$database->setBaseClass('Acme\\Model\\ActiveRecord');
$database->setPackage('Acme');
$database->setHeavyIndexing();
$database->addVendorInfo(new VendorInfo('mysql', [ 'Engine' => 'InnoDB', 'Charset' => 'utf8' ]));
$database->addTables([ $table1, $table2, $table3, $table4, $table5, $table6 ]);

return $database;
