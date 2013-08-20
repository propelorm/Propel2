<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
    public function basicSchemaDataProvider()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable" />
    </table>
</database>
EOF;

        return array(array($schema));
    }

    /**
     * @dataProvider basicSchemaDataProvider
     */
    public function testModifyTableAddsVersionColumn($schema)
    {
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
    version INTEGER DEFAULT 0
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModifyTableAddsVersionColumnCustomName()
    {
            $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable">
            <parameter name="version_column" value="foo_ver" />
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
    foo_ver INTEGER DEFAULT 0
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModifyTableDoesNotAddVersionColumnIfExists()
    {
            $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="version" type="BIGINT" />
        <behavior name="versionable" />
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
    version BIGINT
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function foreignTableSchemaDataProvider()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="foreign_id" type="INTEGER" />
        <foreign-key foreignTable="versionable_behavior_test_1">
            <reference local="foreign_id" foreign="id" />
        </foreign-key>
        <behavior name="versionable" />
    </table>
    <table name="versionable_behavior_test_1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable" />
    </table>
</database>
EOF;

        return array(array($schema));
    }

    /**
     * @dataProvider foreignTableSchemaDataProvider
     */
    public function testModifyTableAddsVersionColumnForForeignKeysIfForeignTableIsVersioned($schema)
    {
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
    foreign_id INTEGER,
    version INTEGER DEFAULT 0,
    FOREIGN KEY (foreign_id) REFERENCES versionable_behavior_test_1 (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
        $expected = <<<EOF

-----------------------------------------------------------------------
-- versionable_behavior_test_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0_version;

CREATE TABLE versionable_behavior_test_0_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    foreign_id INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    foreign_id_version INTEGER DEFAULT 0,
    PRIMARY KEY (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider foreignTableSchemaDataProvider
     */
    public function testModifyTableAddsVersionColumnForReferrersIfForeignTableIsVersioned($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- versionable_behavior_test_1
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_1;

CREATE TABLE versionable_behavior_test_1
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
        $expected = <<<EOF

-----------------------------------------------------------------------
-- versionable_behavior_test_1_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_1_version;

CREATE TABLE versionable_behavior_test_1_version
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    versionable_behavior_test_0_ids MEDIUMTEXT,
    versionable_behavior_test_0_versions MEDIUMTEXT,
    PRIMARY KEY (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_1 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider basicSchemaDataProvider
     */
    public function testModifyTableAddsVersionTable($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
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
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModifyTableAddsVersionTableCustomName()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
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
-- foo_ver
-----------------------------------------------------------------------

DROP TABLE IF EXISTS foo_ver;

CREATE TABLE foo_ver
(
    id INTEGER NOT NULL,
    bar INTEGER,
    version INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModifyTableDoesNotAddVersionTableIfExists()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
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
-- versionable_behavior_test_0
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0;

CREATE TABLE versionable_behavior_test_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    bar INTEGER,
    version INTEGER DEFAULT 0
);

-----------------------------------------------------------------------
-- versionable_behavior_test_0_version
-----------------------------------------------------------------------

DROP TABLE IF EXISTS versionable_behavior_test_0_version;

CREATE TABLE versionable_behavior_test_0_version
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    baz INTEGER
);

EOF;
        $this->assertEquals($expected, $builder->getSQL());
    }

    public function logSchemaDataProvider()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable">
          <parameter name="log_created_at" value="true" />
          <parameter name="log_created_by" value="true" />
          <parameter name="log_comment" value="true" />
        </behavior>
    </table>
</database>
EOF;

        return array(array($schema));
    }

    /**
     * @dataProvider logSchemaDataProvider
     */
    public function testModifyTableAddsLogColumns($schema)
    {
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
    version_created_at TIMESTAMP,
    version_created_by VARCHAR(100),
    version_comment VARCHAR(255)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider logSchemaDataProvider
     */
    public function testModifyTableAddsVersionTableLogColumns($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
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
    version_created_at TIMESTAMP,
    version_created_by VARCHAR(100),
    version_comment VARCHAR(255),
    PRIMARY KEY (id,version),
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testDatabaseLevelBehavior()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <behavior name="versionable" />
    <table name="versionable_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
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
    FOREIGN KEY (id) REFERENCES versionable_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testSkipSqlParameterOnParentTable()
    {
        $schema = <<<EOF
<database name="versionable_behavior_test_0">
    <table name="versionable_behavior_test_0" skipSql="true">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <behavior name="versionable" />
    </table>
</database>
EOF;

        $builder = new QuickBuilder();
        $builder->setSchema($schema);

        $this->assertEmpty($builder->getSQL());
    }

}
