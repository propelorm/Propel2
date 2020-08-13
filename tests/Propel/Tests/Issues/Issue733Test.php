<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Issue733Test1;
use Issue733Test2;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/733.
 *
 * @group database
 */
class Issue733Test extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\Issue733Test1')) {
            $schema = <<<EOF
<database name="issue_733_test">
    <table name="issue_733_test_1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="INTEGER"/>
        <column name="bar" type="VARCHAR" size="100"/>
        <behavior name="i18n">
            <parameter name="i18n_columns" value="bar"/>
            <parameter name="locale_column" value="language"/>
        </behavior>
    </table>
    <table name="issue_733_test_2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="foo" type="INTEGER"/>
        <column name="bar" type="VARCHAR" size="100"/>
        <behavior name="i18n">
            <parameter name="i18n_columns" value="bar"/>
            <parameter name="locale_column" value="language"/>
            <parameter name="locale_alias" value="culture"/>
        </behavior>
    </table>
</database>
EOF;
            $this->con = QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testGetColumnTranslation()
    {
        $o = new Issue733Test1();

        // name of the column set in locale_column parameter
        $o->setLanguage('cs_CZ');
        $o->setBar('test');

        // before the fix, this would throw an exception
        $this->assertEquals($o->getBar(), 'test');
    }

    /**
     * @return void
     */
    public function testGetColumnTranslationWithAlias()
    {
        $o = new Issue733Test2();

        // name of the column set in locale_alias
        // before the fix, this would throw an exception
        $o->setCulture('cs_CZ');

        // before the fix, this would throw an exception
        $this->assertEquals($o->getCulture(), 'cs_CZ');

        // before the fix, this would throw an exception
        $this->assertEquals($o->getCulture(), $o->getLanguage());
    }
}
