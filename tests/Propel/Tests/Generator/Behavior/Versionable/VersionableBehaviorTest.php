<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Versionable;

use Propel\Generator\Util\QuickBuilder;

/**
 * Tests for VersionableBehavior class
 *
 * @author François Zaninotto
 */
class VersionableBehaviorTest extends TestCase
{

    const SCHEMA_NAME = 'test';

    public function basicSchemaDataProvider()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
</database>
EOF;

        return [
            ['versionable_behavior_test_0', $schema],
            [
                self::SCHEMA_NAME . '§versionable_behavior_test_0',
                str_replace('<database ', '<database schema="' . self::SCHEMA_NAME . '" ', $schema),
            ],
        ];
    }


    /**
     * @dataProvider basicSchemaDataProvider
     *
     * @param string $table table name
     * @param string $schema schema xml
     *
     * @return void
     */
    public function testModifyTableAddsVersionColumn($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- $table
-----------------------------------------------------------------------

DROP TABLE IF EXISTS $table;

CREATE TABLE $table
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0,
    UNIQUE (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testModifyTableAddsVersionColumnCustomName()
    {
        $schema  = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable">
            <parameter name="version_column" value="foo_ver"/>
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- versionable_behavior_test_0
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0;

CREATE TABLE versionable_behavior_test_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    foo_ver INTEGER DEFAULT 0,
    UNIQUE (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testModifyTableDoesNotAddVersionColumnIfExists()
    {
        $schema  = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="version" type="BIGINT"/>
        <behavior name="versionable"/>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- versionable_behavior_test_0
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0;

CREATE TABLE versionable_behavior_test_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version BIGINT,
    UNIQUE (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function foreignTableSchemaDataProvider()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="foreign_id" type="INTEGER"/>
        <foreign-key foreignTable="versionable_behavior_test_1">
            <reference local="foreign_id" foreign="id"/>
        </foreign-key>
        <behavior name="versionable"/>
    </table>
    <table name="versionable_behavior_test_1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
</database>
EOF;

        return [
            ['versionable_behavior_test', $schema],
            [
                self::SCHEMA_NAME . '§versionable_behavior_test',
                str_replace('<database ', '<database schema="' . self::SCHEMA_NAME . '" ', $schema),
            ],
        ];
    }

    /**
     * @dataProvider foreignTableSchemaDataProvider
     *
     * @param string $table table name without prefix
     * @param string $schema schema xml
     *
     * @return void
     */
    public function testModifyTableAddsVersionColumnForForeignKeysIfForeignTableIsVersioned($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- {$table}_0
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_0;

CREATE TABLE {$table}_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    foreign_id INTEGER,
    version INTEGER DEFAULT 0,
    UNIQUE (id),
    FOREIGN KEY (foreign_id) REFERENCES {$table}_1 (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
        $expected = <<<EOF

-----------------------------------------------------------------------
-- {$table}_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_0_version;

CREATE TABLE {$table}_0_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    foreign_id INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    foreign_id_version INTEGER DEFAULT 0,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES {$table}_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider foreignTableSchemaDataProvider
     *
     * @param string $table table name without prefix
     * @param string $schema schema xml
     *
     * @return void
     */
    public function testModifyTableAddsVersionColumnForReferrersIfForeignTableIsVersioned($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- {$table}_1
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_1;

CREATE TABLE {$table}_1
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0,
    UNIQUE (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());

        $column   = strtr($table, [self::SCHEMA_NAME . '§' => '']);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- {$table}_1_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_1_version;

CREATE TABLE {$table}_1_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    {$column}_0_ids MEDIUMTEXT,
    {$column}_0_versions MEDIUMTEXT,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES {$table}_1 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider basicSchemaDataProvider
     *
     * @param string $table table name
     * @param string $schema schema xml
     *
     * @return void
     */
    public function testModifyTableAddsVersionTable($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- {$table}_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_version;

CREATE TABLE {$table}_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES {$table} (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testModifyTableAddsVersionTableCustomName()
    {
        $schema  = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable">
          <parameter name="version_table" value="foo_ver"/>
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- foo_ver
-----------------------------------------------------------------------

DROP TABLE IF EXISTS foo_ver;

CREATE TABLE foo_ver
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModifyTableAddsVersionTableCustomNameAndCustomSchemaName()
    {
        $schema  = <<<EOF
<database name="versionable_behavior_test_0" schema="test">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable">
          <parameter name="version_table" value="foo_ver" />
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- test§foo_ver
-----------------------------------------------------------------------

DROP TABLE IF EXISTS test§foo_ver;

CREATE TABLE test§foo_ver
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES test§versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testModifyTableDoesNotAddVersionTableIfExists()
    {
        $schema  = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
    <table name="versionable_behavior_test_0_version">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="baz" type="INTEGER"/>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- versionable_behavior_test_0
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0;

CREATE TABLE versionable_behavior_test_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0,
    UNIQUE (id)
);

-----------------------------------------------------------------------
-- versionable_behavior_test_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0_version;

CREATE TABLE versionable_behavior_test_0_version
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    baz INTEGER,
    UNIQUE (id)
);

EOF;
        $this->assertEquals($expected, $builder->getSQL());
    }

    public function testModifyTableDoesNotAddVersionTableIfExistsCustomSchemaName()
    {
        $schema  = <<<EOF
<database name="versionable_behavior_test_0" schema="test">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable" />
    </table>
    <table name="versionable_behavior_test_0_version">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="baz" type="INTEGER" />
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF

-----------------------------------------------------------------------
-- test§versionable_behavior_test_0
-----------------------------------------------------------------------

DROP TABLE IF EXISTS test§versionable_behavior_test_0;

CREATE TABLE test§versionable_behavior_test_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0,
    UNIQUE (id)
);

-----------------------------------------------------------------------
-- test§versionable_behavior_test_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS test§versionable_behavior_test_0_version;

CREATE TABLE test§versionable_behavior_test_0_version
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    baz INTEGER,
    UNIQUE (id)
);

EOF;
        $this->assertEquals($expected, $builder->getSQL());
    }

    public function logSchemaDataProvider()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable">
          <parameter name="log_created_at" value="true"/>
          <parameter name="log_created_by" value="true"/>
          <parameter name="log_comment" value="true"/>
        </behavior>
    </table>
</database>
EOF;

        return [
            ['versionable_behavior_test_0', $schema],
            [
                self::SCHEMA_NAME . '§versionable_behavior_test_0',
                str_replace('<database ', '<database schema="' . self::SCHEMA_NAME . '" ', $schema),
            ],
        ];
    }

    /**
     * @dataProvider logSchemaDataProvider
     *
     * @return void
     */
    public function testModifyTableAddsLogColumns($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- $table
-----------------------------------------------------------------------

DROP TABLE IF EXISTS $table;

CREATE TABLE $table
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0,
    version_created_at TIMESTAMP,
    version_created_by VARCHAR(100),
    version_comment VARCHAR(255),
    UNIQUE (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider logSchemaDataProvider
     *
     * @return void
     */
    public function testModifyTableAddsVersionTableLogColumns($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- {$table}_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_version;

CREATE TABLE {$table}_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    version_created_at TIMESTAMP,
    version_created_by VARCHAR(100),
    version_comment VARCHAR(255),
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES {$table} (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testDatabaseLevelBehavior()
    {
        $schema   = <<<EOF
<database name="versionable_behavior_test_0">
    <behavior name="versionable"/>
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $expected = <<<EOF
-----------------------------------------------------------------------
-- versionable_behavior_test_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0_version;

CREATE TABLE versionable_behavior_test_0_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $builder  = new QuickBuilder();
        $builder->setSchema($schema);
        $this->assertContains($expected, $builder->getSQL());
    }


    public function testDatabaseLevelBehaviorWithSchemaName()
    {
        $schema   = <<<EOF
<database name="versionable_behavior_test_0" schema="test">
    <behavior name="versionable" />
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $expected = <<<EOF
-----------------------------------------------------------------------
-- test§versionable_behavior_test_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS test§versionable_behavior_test_0_version;

CREATE TABLE test§versionable_behavior_test_0_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES test§versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $builder  = new QuickBuilder();
        $builder->setSchema($schema);
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testIndicesParameter()
    {
        $schema   = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <index>
            <index-column name="bar"/>
        </index>
        <behavior name="versionable">
            <parameter name="indices" value="true"/>
        </behavior>
    </table>
</database>
EOF;
        $expected = <<<EOF
CREATE TABLE versionable_behavior_test_0_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);

CREATE INDEX versionable_behavior_test_0_version_i_14f552 ON versionable_behavior_test_0_version (bar);
EOF;
        $builder  = new QuickBuilder();
        $builder->setSchema($schema);
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testIndicesParameterWithSchemaName()
    {
        $schema   = <<<EOF
<database name="versionable_behavior_test_0" schema="test">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <index>
            <index-column name="bar"/>
        </index>
        <behavior name="versionable">
            <parameter name="indices" value="true" />
        </behavior>
    </table>
</database>
EOF;
        $expected = <<<EOF
CREATE TABLE test§versionable_behavior_test_0_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES test§versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);

CREATE INDEX versionable_behavior_test_0_version_i_14f552 ON test§versionable_behavior_test_0_version (bar);
EOF;
        $builder  = new QuickBuilder();
        $builder->setSchema($schema);
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @return void
     */
    public function testSkipSqlParameterOnParentTable()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0" skipSql="true">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
</database>
EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);

        $this->assertEmpty($builder->getSQL());
    }

    public function tablePrefixSchemaDataProvider()
    {
        $schema = <<<XML
<database name="versionable_behavior_test_0" tablePrefix="prefix_">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <behavior name="versionable"/>
    </table>
</database>
XML;

        return [
            ['prefix_versionable_behavior_test_0', $schema],
            [
                self::SCHEMA_NAME . '§prefix_versionable_behavior_test_0',
                str_replace('<database ', '<database schema="' . self::SCHEMA_NAME . '" ', $schema),
            ],
        ];
    }

    /**
     * @dataProvider tablePrefixSchemaDataProvider
     *
     * @return void
     */
    public function testModifyTableAddsVersionColumnWithPrefix($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<SQL
-----------------------------------------------------------------------
-- $table
-----------------------------------------------------------------------

DROP TABLE IF EXISTS $table;

CREATE TABLE $table
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0,
    UNIQUE (id)
);
SQL;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider tablePrefixSchemaDataProvider
     *
     * @return void
     */
    public function testModifyTableAddsVersionTableWithPrefix($table, $schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<SQL
-----------------------------------------------------------------------
-- {$table}_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS {$table}_version;

CREATE TABLE {$table}_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    UNIQUE (id,version),
    FOREIGN KEY (id) REFERENCES {$table} (id)
        ON DELETE CASCADE
);
SQL;
        $this->assertContains($expected, $builder->getSQL());
    }
}
