<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\I18n;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests for I18nBehavior class
 *
 * @author François Zaninotto
 * @group skip
 */
class I18nBehaviorTest extends TestCase
{
    public function testModifyDatabaseOverridesDefaultLocale()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <behavior name="i18n">
        <parameter name="default_locale" value="fr_FR" />
    </behavior>
    <entity name="BehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n" />
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- behavior_test0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS behavior_test0_i18n;

CREATE TABLE behavior_test0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'fr_FR' NOT NULL,
    PRIMARY KEY (id,locale),
    UNIQUE (id,locale),
    FOREIGN KEY (id) REFERENCES behavior_test0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModifyDatabaseDoesNotOverrideTableLocale()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <behavior name="i18n">
        <parameter name="default_locale" value="fr_FR" />
    </behavior>
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="default_locale" value="pt_PT" />
        </behavior>
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test0_i18n;

CREATE TABLE i18n_behavior_test0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'pt_PT' NOT NULL,
    PRIMARY KEY (id,locale),
    UNIQUE (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testSkipSqlParameterOnParentTable()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0" skipSql="true">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <field name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_fields" value="bar" />
        </behavior>
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);

        $this->assertEmpty($builder->getSQL());
    }

    public function schemaDataProvider()
    {
        $schema1 = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <field name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_fields" value="bar" />
        </behavior>
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <behavior name="i18n" />
    </entity>
    <entity name="I18nBehaviorTest0I18n">
        <field name="id" primaryKey="true" type="INTEGER" />
        <field name="locale" primaryKey="true" type="VARCHAR" size="5" default="en_US" />
        <field name="bar" type="VARCHAR" size="100" />
        <relation target="I18nBehaviorTest0" />
    </entity>
</database>
EOF;

        return array(array($schema1), array($schema1));
    }

    /**
     * @dataProvider schemaDataProvider
     */
    public function testModifyTableAddsI18nTable($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test0_i18n;

CREATE TABLE i18n_behavior_test0_i18n
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider schemaDataProvider
     */
    public function testModifyTableRelatesI18nTableToMainTable($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider schemaDataProvider
     */
    public function testModifyTableAddsLocaleColumnToI18n($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
CREATE TABLE i18n_behavior_test0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider schemaDataProvider
     */
    public function testModifyTableMovesI18nColumns($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
CREATE TABLE i18n_behavior_test0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    bar VARCHAR(100),
    PRIMARY KEY (id,locale),
    UNIQUE (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    /**
     * @dataProvider schemaDataProvider
     */
    public function testModifyTableDoesNotMoveNonI18nColumns($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
CREATE TABLE i18n_behavior_test0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    foo INTEGER,
    UNIQUE (id)
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomI18nTableName()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="i18n_entity" value="foo_table" />
        </behavior>
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- foo_table
-----------------------------------------------------------------------

DROP TABLE IF EXISTS foo_table;

CREATE TABLE foo_table
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    PRIMARY KEY (id,locale),
    UNIQUE (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomLocaleColumnName()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="locale_field" value="culture" />
        </behavior>
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test0_i18n;

CREATE TABLE i18n_behavior_test0_i18n
(
    id INTEGER NOT NULL,
    culture VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    PRIMARY KEY (id,culture),
    UNIQUE (id,culture),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomLocaleDefault()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="default_locale" value="fr_FR" />
        </behavior>
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test0_i18n;

CREATE TABLE i18n_behavior_test0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'fr_FR' NOT NULL,
    PRIMARY KEY (id,locale),
    UNIQUE (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomI18nLocaleLength()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <entity name="I18nBehaviorTest0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="locale_length" value="6" />
        </behavior>
    </entity>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test0_i18n;

CREATE TABLE i18n_behavior_test0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(6) DEFAULT 'en_US' NOT NULL,
    PRIMARY KEY (id,locale),
    UNIQUE (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function customPkSchemaDataProvider()
    {
        $schema1 = <<<EOF
<database name="i18n_behavior_test_custom_pk_0">
    <entity name="I18nBehaviorTestCustomPk0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <field name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_fields" value="bar" />
            <parameter name="i18n_relation_field" value="custom_id" />
        </behavior>
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="i18n_behavior_test_custom_pk_0">
    <entity name="I18nBehaviorTestCustomPk0">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <behavior name="i18n" />
    </entity>
    <entity name="I18nBehaviorTestCustomPk0I18n">
        <field name="custom_id" primaryKey="true" type="INTEGER" />
        <field name="locale" primaryKey="true" type="VARCHAR" size="5" default="en_US" />
        <field name="bar" type="VARCHAR" size="100" />
        <relation target="I18nBehaviorTestCustomPk0">
            <reference local="custom_id" foreign="id" />
        </relation>
    </entity>
</database>
EOF;

        return array(array($schema1), array($schema2));
    }

    /**
     * @dataProvider customPkSchemaDataProvider
     */
    public function testModifyTableRelatesI18nTableToMainTableWithCustomPk($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test_custom_pk0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_custom_pk0_i18n;

CREATE TABLE i18n_behavior_test_custom_pk0_i18n
(
    custom_id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    bar VARCHAR(100),
    PRIMARY KEY (custom_id,locale),
    UNIQUE (custom_id,locale),
    FOREIGN KEY (custom_id) REFERENCES i18n_behavior_test_custom_pk0 (id)
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }
}
