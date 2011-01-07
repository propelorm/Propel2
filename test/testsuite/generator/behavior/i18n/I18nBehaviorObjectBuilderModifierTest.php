<?php

/*
 *	$Id: VersionableBehaviorTest.php 1460 2010-01-17 22:36:48Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/behavior/i18n/I18nBehavior.php';
require_once dirname(__FILE__) . '/../../../../../runtime/lib/Propel.php';

/**
 * Tests for I18nBehavior class object modifier
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior.i18n
 */
class I18nBehaviorObjectBuilderModifierTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		if (!class_exists('I18nBehaviorTest1')) {
			$schema = <<<EOF
<database name="i18n_behavior_test_1">
	<table name="i18n_behavior_test_1">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="foo" type="INTEGER" />
		<column name="bar" type="VARCHAR" size="100" />
		<behavior name="i18n">
			<parameter name="i18n_columns" value="bar" />
		</behavior>
	</table>
	<table name="i18n_behavior_test_2">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="foo" type="INTEGER" />
		<column name="bar1" type="VARCHAR" size="100" />
		<column name="bar2" type="LONGVARCHAR" lazyLoad="true" />
		<column name="bar3" type="TIMESTAMP" />
		<column name="bar4" type="LONGVARCHAR" description="This is the Bar4 column" />
		<behavior name="i18n">
			<parameter name="i18n_columns" value="bar1,bar2,bar3,bar4" />
			<parameter name="default_locale" value="fr_FR" />
			<parameter name="locale_alias" value="culture" />
		</behavior>
	</table>
</database>
EOF;
PropelQuickBuilder::debugClassesForTable($schema, 'i18n_behavior_test_1');
			PropelQuickBuilder::buildSchema($schema);
		}
	}

	public function testPostDeleteEmulatesOnDeleteCascade()
	{
		I18nBehaviorTest1Query::create()->deleteAll();
		I18nBehaviorTest1I18nQuery::create()->deleteAll();
		$o = new I18nBehaviorTest1();
		$o->setFoo(123);
		$o->setLocale('en_EN');
		$o->setBar('hello');
		$o->setLocale('fr_FR');
		$o->setBar('bonjour');
		$o->save();
		$this->assertEquals(2, I18nBehaviorTest1I18nQuery::create()->count());
		$o->clearI18nBehaviorTest1I18ns();
		$o->delete();
		$this->assertEquals(0, I18nBehaviorTest1I18nQuery::create()->count());
	}

	public function testGetTranslationReturnsTranslationObject()
	{
		$o = new I18nBehaviorTest1();
		$translation = $o->getTranslation();
		$this->assertTrue($translation instanceof I18nBehaviorTest1I18n);
	}

	public function testGetTranslationOnNewObjectReturnsNewTranslation()
	{
		$o = new I18nBehaviorTest1();
		$translation = $o->getTranslation();
		$this->assertTrue($translation->isNew());
	}

	public function testGetTranslationOnPersistedObjectReturnsNewTranslation()
	{
		$o = new I18nBehaviorTest1();
		$o->save();
		$translation = $o->getTranslation();
		$this->assertTrue($translation->isNew());
	}

	public function testGetTranslationOnPersistedObjectWithTranslationReturnsExistingTranslation()
	{
		$o = new I18nBehaviorTest1();
		$translation = new I18nBehaviorTest1I18n();
		$o->addI18nBehaviorTest1I18n($translation);
		$o->save();
		$translation = $o->getTranslation();
		$this->assertFalse($translation->isNew());
	}

	public function testGetTranslationAcceptsALocaleParameter()
	{
		$o = new I18nBehaviorTest1();
		$translation1 = new I18nBehaviorTest1I18n();
		$translation1->setLocale('en_EN');
		$o->addI18nBehaviorTest1I18n($translation1);
		$translation2 = new I18nBehaviorTest1I18n();
		$translation2->setLocale('fr_FR');
		$o->addI18nBehaviorTest1I18n($translation2);
		$o->save();
		$this->assertEquals($translation1, $o->getTranslation('en_EN'));
		$this->assertEquals($translation2, $o->getTranslation('fr_FR'));
	}
	
	public function testGetTranslationSetsTheLocaleOnTheTranslation()
	{
		$o = new I18nBehaviorTest1();
		$o->save();
		$translation = $o->getTranslation();
		$this->assertEquals('en_EN', $translation->getLocale());
		$o = new I18nBehaviorTest2();
		$o->save();
		$translation = $o->getTranslation();
		$this->assertEquals('fr_FR', $translation->getLocale());
	}

	public function testGetTranslationUsesInternalCollectionIfAvailable()
	{
		$o = new I18nBehaviorTest1();
		$translation1 = new I18nBehaviorTest1I18n();
		$translation1->setLocale('en_EN');
		$o->addI18nBehaviorTest1I18n($translation1);
		$translation2 = new I18nBehaviorTest1I18n();
		$translation2->setLocale('fr_FR');
		$o->addI18nBehaviorTest1I18n($translation2);
		$translation = $o->getTranslation('en_EN');
		$this->assertEquals($translation1, $translation);
	}
	
	public function testRemoveTranslation()
	{
		$o = new I18nBehaviorTest1();
		$translation1 = new I18nBehaviorTest1I18n();
		$translation1->setLocale('en_EN');
		$o->addI18nBehaviorTest1I18n($translation1);
		$translation2 = new I18nBehaviorTest1I18n();
		$translation2->setLocale('fr_FR');
		$translation2->setBar('bonjour');
		$o->addI18nBehaviorTest1I18n($translation2);
		$o->save();
		$this->assertEquals(2, $o->countI18nBehaviorTest1I18ns());
		$o->removeTranslation('fr_FR');
		$this->assertEquals(1, $o->countI18nBehaviorTest1I18ns());
		$translation = $o->getTranslation('fr_FR');
		$this->assertNotEquals($translation->getBar(), $translation2->getBar());
	}

	public function testLocaleSetterAndGetterExist()
	{
		$this->assertTrue(method_exists('I18nBehaviorTest1', 'setLocale'));
		$this->assertTrue(method_exists('I18nBehaviorTest1', 'getLocale'));
	}

	public function testGetLocaleReturnsDefaultLocale()
	{
		$o = new I18nBehaviorTest1();
		$this->assertEquals('en_EN', $o->getLocale());
		$o = new I18nBehaviorTest2();
		$this->assertEquals('fr_FR', $o->getLocale());
	}

	public function testSetLocale()
	{
		$o = new I18nBehaviorTest1();
		$o->setLocale('fr_FR');
		$this->assertEquals('fr_FR', $o->getLocale());
	}

	public function testSetLocaleUsesDefaultLocale()
	{
		$o = new I18nBehaviorTest1();
		$o->setLocale('fr_FR');
		$o->setLocale();
		$this->assertEquals('en_EN', $o->getLocale());
	}

	public function testLocaleSetterAndGetterAliasesExist()
	{
		$this->assertTrue(method_exists('I18nBehaviorTest2', 'setCulture'));
		$this->assertTrue(method_exists('I18nBehaviorTest2', 'getCulture'));
	}

	public function testGetLocaleAliasReturnsDefaultLocale()
	{
		$o = new I18nBehaviorTest2();
		$this->assertEquals('fr_FR', $o->getCulture());
	}

	public function testSetLocaleAlias()
	{
		$o = new I18nBehaviorTest2();
		$o->setCulture('en_EN');
		$this->assertEquals('en_EN', $o->getCulture());
	}

	public function testGetCurrentTranslationUsesDefaultLocale()
	{
		$o = new I18nBehaviorTest1();
		$t = $o->getCurrentTranslation();
		$this->assertEquals('en_EN', $t->getLocale());
		$o = new I18nBehaviorTest2();
		$t = $o->getCurrentTranslation();
		$this->assertEquals('fr_FR', $t->getLocale());
	}

	public function testGetCurrentTranslationUsesCurrentLocale()
	{
		$o = new I18nBehaviorTest1();
		$o->setLocale('fr_FR');
		$this->assertEquals('fr_FR', $o->getCurrentTranslation()->getLocale());
		$o->setLocale('pt_PT');
		$this->assertEquals('pt_PT', $o->getCurrentTranslation()->getLocale());
	}
	
	public function testI18nColumnGetterUsesCurrentTranslation()
	{
		$o = new I18nBehaviorTest1();
		$t1 = $o->getCurrentTranslation();
		$t1->setBar('hello');
		$o->setLocale('fr_FR');
		$t2 = $o->getCurrentTranslation();
		$t2->setBar('bonjour');
		//$o->save();
		$o->setLocale('en_EN');
		$this->assertEquals('hello', $o->getBar());
		$o->setLocale('fr_FR');
		$this->assertEquals('bonjour', $o->getBar());
	}

	public function testI18nColumnSetterUsesCurrentTranslation()
	{
		$o = new I18nBehaviorTest1();
		$o->setBar('hello');
		$o->setLocale('fr_FR');
		$o->setBar('bonjour');
		$o->setLocale('en_EN');
		$this->assertEquals('hello', $o->getBar());
		$o->setLocale('fr_FR');
		$this->assertEquals('bonjour', $o->getBar());
	}
	
	public function testTranslationsArePersisted()
	{
		$o = new I18nBehaviorTest1();
		$o->save();
		$count = I18nBehaviorTest1I18nQuery::create()
			->filterById($o->getId())
			->count();
		$this->assertEquals(0, $count);
		$o->setBar('hello');
		$o->setLocale('fr_FR');
		$o->setBar('bonjour');
		$o->save();
		$count = I18nBehaviorTest1I18nQuery::create()
			->filterById($o->getId())
			->count();
		$this->assertEquals(2, $count);
	}

}