<?php

/*
 *	$Id$
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

use Map\ArchivableTest1ArchiveTableMap;
use Map\ArchivableTest1TableMap;
use Map\ArchivableTest2ArchiveTableMap;
use Map\ArchivableTest2TableMap;
use Map\ArchivableTest3TableMap;
use Map\ArchivableTest4TableMap;
use Map\ArchivableTest5TableMap;
use Map\MyOldArchivableTest3TableMap;
use Propel\Generator\Exception\SchemaException;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;
use function substr_count;

/**
 * Tests for ArchivableBehavior class
 *
 * @author François Zaninotto
 */
class ArchivableBehaviorTest extends TestCase
{
    /**
     * @var string
     */
    protected static $generatedSQL;

    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\ArchivableTest1')) {
            $schema = <<<EOF
<database name="archivable_behavior_test_0">

    <table name="archivable_test_1">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="age" type="INTEGER"/>
        <column name="foo_id" type="INTEGER"/>
        <foreign-key foreignTable="archivable_test_2">
            <reference local="foo_id" foreign="id"/>
        </foreign-key>
        <index name="archivable_test_1_idx_title_age">
            <index-column name="title"/>
            <index-column name="age"/>
        </index>
        <behavior name="archivable"/>
    </table>

    <table name="archivable_test_2">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <behavior name="archivable"/>
    </table>

    <table name="archivable_test_2_archive">
        <column name="id" required="true" primaryKey="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
    </table>

    <table name="archivable_test_3">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="age" type="INTEGER"/>
        <column name="foo_id" type="INTEGER"/>
        <unique>
            <unique-column name="title"/>
        </unique>
        <index>
            <index-column name="title"/>
        </index>
        <behavior name="archivable">
            <parameter name="log_archived_at" value="false"/>
            <parameter name="archive_table" value="my_old_archivable_test_3"/>
            <parameter name="archive_on_insert" value="true"/>
            <parameter name="archive_on_update" value="true"/>
            <parameter name="archive_on_delete" value="false"/>
        </behavior>
    </table>

    <table name="archivable_test_4">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <column name="age" type="INTEGER"/>
        <behavior name="archivable">
            <parameter name="archive_class" value="\Propel\Tests\Generator\Behavior\Archivable\FooArchive"/>
        </behavior>
    </table>

    <table name="archivable_test_5">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <behavior name="archivable">
            <parameter name="archive_table" value="archivable_test_5_backup"/>
            <parameter name="archive_phpname" value="ArchivableTest5MyBackup"/>
        </behavior>
    </table>

</database>
EOF;
            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            self::$generatedSQL = $builder->getSQL();
            $builder->build();
        }
    }

    /**
     * @return void
     */
    public function testCreatesArchiveTable()
    {
        $table = ArchivableTest1TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_1_archive'));
        $this->assertSame('ArchivableTest1Archive', $table->getDatabaseMap()->getTable('archivable_test_1_archive')->getPhpName());
    }

    /**
     * @return void
     */
    public function testDoesNotCreateCustomArchiveTableIfExists()
    {
        $table = ArchivableTest2TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_2_archive'));
    }

    /**
     * @return void
     */
    public function testCanCreateCustomArchiveTableName()
    {
        $table = ArchivableTest3TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('my_old_archivable_test_3'));
        $this->assertSame('MyOldArchivableTest3', $table->getDatabaseMap()->getTable('my_old_archivable_test_3')->getPhpName());
    }

    /**
     * @return void
     */
    public function testDoesNotCreateCustomArchiveTableIfArchiveClassIsSpecified()
    {
        $table = ArchivableTest4TableMap::getTableMap();
        $this->assertFalse($table->getDatabaseMap()->hasTable('archivable_test_4_archive'));
    }

    /**
     * @return void
     */
    public function testCanCreateCustomArchiveTableNameAndPhpName()
    {
        $table = ArchivableTest5TableMap::getTableMap();
        $this->assertTrue($table->getDatabaseMap()->hasTable('archivable_test_5_backup'));
        $this->assertSame('ArchivableTest5MyBackup', $table->getDatabaseMap()->getTable('archivable_test_5_backup')->getPhpName());
    }

    /**
     * @return void
     */
    public function testCopiesColumnsToArchiveTable()
    {
        $table = ArchivableTest1ArchiveTableMap::getTableMap();
        $this->assertTrue($table->hasColumn('id'));
        $this->assertStringContainsString('id INTEGER NOT NULL,', self::$generatedSQL, 'copied columns are not autoincremented');
        $this->assertTrue($table->hasColumn('title'));
        $this->assertTrue($table->hasColumn('age'));
        $this->assertTrue($table->hasColumn('foo_id'));
    }

    /**
     * @return void
     */
    public function testDoesNotCopyForeignKeys()
    {
        $table = ArchivableTest1ArchiveTableMap::getTableMap();
        $this->assertEquals([], $table->getRelations());
    }

    public function invalidFkTestDataProvider(): array
    {
        return [
            [
                'missing `local_column` parameter','
                    <parameter name="foreign_table" value="archived_fk_table" />
                    <parameter name="foreign_column" value="id" />
            '],[
                'missing `foreign_table` parameter','
                    <parameter name="local_column" value="fk_column" />
                    <parameter name="foreign_column" value="id" />
            '],[
                'missing `foreign_column` parameter','
                    <parameter name="local_column" value="fk_column" />
                    <parameter name="foreign_table" value="archived_fk_table" />
            ']
        ];
    }

    /**
     * @dataProvider invalidFkTestDataProvider
     *
     * @return void
     */
    public function testMissingFkParametersThrowsException(string $description, string $parameters)
    {
        //$this->markTestSkipped();
        $schema = <<<EOF
        <database name="archivable_behavior_test_0">
        
            <table name="source_table">
                <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        
                <behavior name="archivable">
                    <parameter-list name="foreign_keys">
                        <parameter-list-item>
                            $parameters
                        </parameter-list-item>
                    </parameter-list>
                </behavior>
            </table>
        
        </database>
        EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);

        $this->expectException(SchemaException::class);
        $builder->getDatabase();
    }

    public function addFkTestDataProvider(): array
    {
        return [
            // description, behavior parameters, local fk, foreign table name, foreign column name
            [
                'Should not export fks per default',
                '',
                null, null, null, false
            ], [
                'Parameter `inherit_foreign_key_relations` should preserve old fks without constraints',
                '
                    <parameter name="inherit_foreign_key_relations" value="true"/>
                ',
                'fk_column', 'fk_table', 'id', false
            ], [
                'Parameter `inherit_foreign_key_constraints` should preserve old fks with constraints',
                '
                    <parameter name="inherit_foreign_key_relations" value="true"/>
                    <parameter name="inherit_foreign_key_constraints" value="true"/>
                ',
                'fk_column', 'fk_table', 'id', true
            ], [
                'Parameter `foreign_keys` allows to redefine fks with db constraint as default',
                '
                    <parameter-list name="foreign_keys">
                        <parameter-list-item>
                            <parameter name="localColumn" value="fk_column" />
                            <parameter name="foreignTable" value="archived_fk_table" />
                            <parameter name="foreignColumn" value="id" />
                        </parameter-list-item>
                    </parameter-list>
                ',
                'fk_column', 'archived_fk_table', 'id', true
            ], [
                'Parameter `foreign_keys` allows to redefine fks without db constraint',
                '
                    <parameter-list name="foreign_keys">
                        <parameter-list-item>
                            <parameter name="localColumn" value="fk_column" />
                            <parameter name="foreignTable" value="archived_fk_table" />
                            <parameter name="foreignColumn" value="id" />
                            <parameter name="skipSql" value="true" />
                        </parameter-list-item>
                    </parameter-list>
                ',
                'fk_column', 'archived_fk_table', 'id', false
            ]
        ];
    }

    /**
     * @dataProvider addFkTestDataProvider
     *
     * @return void
     */
    public function testAddFkParameter(
        string $description,
        string $parameters,
        ?string $localColumnName, 
        ?string $fkTableName,
        ?string $fkColumnName,
        bool $withDbConstraint
    ): void
    {
        $archiveTableName = 'archive_table_with_FK';

        // Two archived tables connected by a foreign key 
        $schema = <<<EOF
<database name="archivable_behavior_test_0">

    <table name="table_with_FK">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="fk_column" type="INTEGER"/>

        <foreign-key foreignTable="fk_table">
            <reference local="fk_column" foreign="id"/>
        </foreign-key>

        <behavior name="archivable">
            <parameter name="archive_table" value="$archiveTableName"/>
            $parameters
        </behavior>
    </table>

    <table name="fk_table">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>

        <behavior name="archivable">
            <parameter name="archive_table" value="archived_fk_table"/>
        </behavior>
    </table>

</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $fks = $builder->getDatabase()->getTable($archiveTableName)->getForeignKeys();

        $expectedCount = $fkColumnName ? 1 : 0;
        $this->assertCount($expectedCount, $fks, $description);

        if(!$localColumnName){
            return;
        }

        /** @var \Propel\Generator\Model\ForeignKey */
        $fk = reset($fks);

        $this->assertSame($localColumnName, $fk->getLocalColumnName(), 'Local column name should match');
        $this->assertSame($fkTableName, $fk->getForeignTableCommonName(), 'Foreign table name should match');
        $this->assertSame($fkColumnName, $fk->getForeignColumnName(), 'Foreign column name should match');
        $this->assertSame(!$withDbConstraint, $fk->isSkipSql());
    }

    /**
     * @return void
     */
    public function testCopiesIndices()
    {
        $table = ArchivableTest1ArchiveTableMap::getTableMap();
        $expected = 'CREATE INDEX archivable_test_1_archive_i_6c947f ON archivable_test_1_archive (title,age);';
        $this->assertStringContainsString($expected, self::$generatedSQL);
    }

    /**
     * @return void
     */
    public function testCopiesUniquesToIndices()
    {
        $table = ArchivableTest2ArchiveTableMap::getTableMap();
        $expected = 'CREATE INDEX my_old_archivable_test_3_i_639136 ON my_old_archivable_test_3 (title);';
        $this->assertStringContainsString($expected, self::$generatedSQL);
    }

    /**
     * @return void
     */
    public function testCopiedUniqueDoesNotDuplicateCopiedIndex()
    {
        $expectedSqlMigration = 'CREATE INDEX my_old_archivable_test_3_i_639136 ON my_old_archivable_test_3 (title);';
        $this->assertSame(1, substr_count(self::$generatedSQL, $expectedSqlMigration));
    }

    /**
     * @return void
     */
    public function testAddsArchivedAtColumnToArchiveTableByDefault()
    {
        $table = ArchivableTest1ArchiveTableMap::getTableMap();
        $this->assertTrue($table->hasColumn('archived_at'));
    }

    /**
     * @return void
     */
    public function testDoesNotAddArchivedAtColumnToArchiveTableIfSpecified()
    {
        $table = MyOldArchivableTest3TableMap::getTableMap();
        $this->assertFalse($table->hasColumn('archived_at'));
    }

    /**
     * @return void
     */
    public function testDatabaseLevelBehavior()
    {
        $schema = <<<EOF
<database name="archivable_behavior_test_0">
    <behavior name="archivable"/>
    <table name="archivable_test_01">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <behavior name="archivable"/>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $sql = $builder->getSQL();

        $this->assertNotEmpty($sql);
    }

    /**
     * @return array
     */
    public function tablePrefixDataProvider()
    {
        $schema = <<<XML
<database name="archivable_behavior_test_0" tablePrefix="foo_">
    <table name="bar_prefix_test_1">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="title" type="VARCHAR" size="100" primaryString="true"/>
        <behavior name="archivable"/>
    </table>
</database>
XML;
        $sql = <<<SQL

-----------------------------------------------------------------------
-- foo_bar_prefix_test_1
-----------------------------------------------------------------------

DROP TABLE IF EXISTS foo_bar_prefix_test_1;

CREATE TABLE foo_bar_prefix_test_1
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(100),
    UNIQUE (id)
);

-----------------------------------------------------------------------
-- foo_bar_prefix_test_1_archive
-----------------------------------------------------------------------

DROP TABLE IF EXISTS foo_bar_prefix_test_1_archive;

CREATE TABLE foo_bar_prefix_test_1_archive
(
    id INTEGER NOT NULL,
    title VARCHAR(100),
    archived_at TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (id)
);

SQL;
        $classes = ['Base\\BarPrefixTest1Archive', 'Base\\BarPrefixTest1ArchiveQuery', 'Map\\BarPrefixTest1ArchiveTableMap'];

        return [[$schema, $sql, $classes]];
    }

    /**
     * @dataProvider tablePrefixDataProvider
     *
     * @return void
     */
    public function testGeneratedSqlWithTablePrefix($schema, $expectSQL, $expectClasses)
    {
        $builder = new QuickBuilder();

        $builder->setSchema($schema);

        $actualSQL = $builder->getSQL();

        $this->assertEquals($expectSQL, $actualSQL);
    }

    /**
     * @dataProvider tablePrefixDataProvider
     *
     * @return void
     */
    public function testGeneratedClassesWithTablePrefix($schema, $expectSQL, $expectClasses)
    {
        $builder = new QuickBuilder();

        $builder->setSchema($schema);
        $builder->buildClasses();

        foreach ($expectClasses as $expectClass) {
            $this->assertTrue(class_exists($expectClass), sprintf('expected class "%s" not exists', $expectClass));
        }
    }
}
