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
use Propel\Generator\Behavior\I18n\I18nBehavior;

use Propel\Runtime\Propel;
use Propel\Tests\TestCase;

/**
 * Tests for I18nBehavior class
 *
 * @author François Zaninotto
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
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n" />
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test_0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_0_i18n;

CREATE TABLE i18n_behavior_test_0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'fr_FR' NOT NULL,
    PRIMARY KEY (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
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
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="default_locale" value="pt_PT" />
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test_0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_0_i18n;

CREATE TABLE i18n_behavior_test_0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'pt_PT' NOT NULL,
    PRIMARY KEY (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testSkipSqlParameterOnParentTable()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <table name="i18n_behavior_test_0" skipSql="true">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="foo" type="INTEGER" />
        <column name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_columns" value="bar" />
        </behavior>
    </table>
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
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="foo" type="INTEGER" />
        <column name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_columns" value="bar" />
        </behavior>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="i18n_behavior_test_0">
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="foo" type="INTEGER" />
        <behavior name="i18n" />
    </table>
    <table name="i18n_behavior_test_0_i18n">
        <column name="id" primaryKey="true" type="INTEGER" />
        <column name="locale" primaryKey="true" type="VARCHAR" size="5" default="en_US" />
        <column name="bar" type="VARCHAR" size="100" />
        <foreign-key foreignTable="i18n_behavior_test_0">
            <reference local="id" foreign="id" />
        </foreign-key>
    </table>
</database>
EOF;

        return array(array($schema1), array($schema2));
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
-- i18n_behavior_test_0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_0_i18n;

CREATE TABLE i18n_behavior_test_0_i18n
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
FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
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
CREATE TABLE i18n_behavior_test_0_i18n
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
CREATE TABLE i18n_behavior_test_0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    bar VARCHAR(100),
    PRIMARY KEY (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
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
CREATE TABLE i18n_behavior_test_0
(
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    foo INTEGER
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomI18nTableName()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="i18n_table" value="foo_table" />
        </behavior>
    </table>
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
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomLocaleColumnName()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="locale_column" value="culture" />
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test_0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_0_i18n;

CREATE TABLE i18n_behavior_test_0_i18n
(
    id INTEGER NOT NULL,
    culture VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    PRIMARY KEY (id,culture),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomLocaleDefault()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="default_locale" value="fr_FR" />
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test_0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_0_i18n;

CREATE TABLE i18n_behavior_test_0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(5) DEFAULT 'fr_FR' NOT NULL,
    PRIMARY KEY (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }

    public function testModiFyTableUsesCustomI18nLocaleLength()
    {
        $schema = <<<EOF
<database name="i18n_behavior_test_0">
    <table name="i18n_behavior_test_0">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <behavior name="i18n">
            <parameter name="locale_length" value="6" />
        </behavior>
    </table>
</database>
EOF;
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $expected = <<<EOF
-----------------------------------------------------------------------
-- i18n_behavior_test_0_i18n
-----------------------------------------------------------------------

DROP TABLE IF EXISTS i18n_behavior_test_0_i18n;

CREATE TABLE i18n_behavior_test_0_i18n
(
    id INTEGER NOT NULL,
    locale VARCHAR(6) DEFAULT 'en_US' NOT NULL,
    PRIMARY KEY (id,locale),
    FOREIGN KEY (id) REFERENCES i18n_behavior_test_0 (id)
        ON DELETE CASCADE
);
EOF;
        $this->assertContains($expected, $builder->getSQL());
    }
}
