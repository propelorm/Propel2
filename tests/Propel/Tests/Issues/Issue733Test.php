<?php

namespace Propel\Tests\Issues;

use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;


/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/733.
 *
 * @group database
 */ 
class Issue733Test extends TestCase
{

    public function setUp()
    {
        if (!class_exists('\Issue733Test1')) {
            $schema = <<<EOF
<database name="issue_733_test">
    <entity name="Issue733Test1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <field name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_fields" value="bar" />
            <parameter name="locale_field" value="language" />
        </behavior>
    </entity>   
    <entity name="Issue733Test2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="foo" type="INTEGER" />
        <field name="bar" type="VARCHAR" size="100" />
        <behavior name="i18n">
            <parameter name="i18n_fields" value="bar" />
            <parameter name="locale_field" value="language" />
            <parameter name="locale_alias" value="culture" />
        </behavior>
    </entity>
</database>
EOF;
            $this->con = QuickBuilder::buildSchema($schema);
        }
    }


    public function testGetColumnTranslation()
    {
        $o = new \Issue733Test1();

        // name of the column set in locale_column parameter
        $o->setLanguage('cs_CZ');
        $o->setBar('test');

        // before the fix, this would throw an exception
        $this->assertEquals($o->getBar(), 'test');
    }


    public function testGetColumnTranslationWithAlias()
    {
        $o = new \Issue733Test2();

        // name of the column set in locale_alias
        // before the fix, this would throw an exception
        $o->setCulture('cs_CZ');

        // before the fix, this would throw an exception
        $this->assertEquals($o->getCulture(), 'cs_CZ');

        // before the fix, this would throw an exception
        $this->assertEquals($o->getCulture(), $o->getLanguage());

    }

}