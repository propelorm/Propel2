<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use ConstantNameTest1\Map\UserCheck1TableMap;
use ConstantNameTest2\Map\UserCheck2TableMap;
use ConstantNameTest3\Map\UserCheck3TableMap;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Tests for generated constants' names.
 *
 * @author Boban Acimovic <boban.acimovic@gmail.com>
 */
class ConstantNameTest extends TestCase
{
    /**
     * Test normal string as single inheritance key
     *
     * @return void
     */
    public function testSingleInheritanceKeyNormalString()
    {
        $schema = <<<XML
<database name="constant_name_test" namespace="ConstantNameTest1">
  <table name="radcheck" phpName="UserCheck1">
    <column name="id" type="INTEGER" sqlType="int(11) unsigned" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="attribute" type="VARCHAR" size="64" required="true" inheritance="single">
      <inheritance key="Expiration" class="UserCheck1Expiration" extends="UserCheck1"/>
    </column>
  </table>
</database>
XML;
        $this->buildClasses($schema);
        $this->assertTrue(class_exists('ConstantNameTest1\UserCheck1'));
        $this->assertTrue(class_exists('ConstantNameTest1\UserCheck1Expiration'));
        $this->assertTrue(class_exists('ConstantNameTest1\Map\UserCheck1TableMap'));
        $this->assertTrue(defined('\ConstantNameTest1\Map\UserCheck1TableMap::CLASSKEY_EXPIRATION'));
        $this->assertEquals('Expiration', UserCheck1TableMap::CLASSKEY_EXPIRATION);
    }

    /**
     * Test string with dashes as single inheritance key (original cause for this whole test)
     *
     * @return void
     */
    public function testSingleInheritanceKeyStringWithDashes()
    {
        $schema = <<<XML
<database name="constant_name_test" namespace="ConstantNameTest2">
  <table name="radcheck" phpName="UserCheck2">
    <column name="id" type="INTEGER" sqlType="int(11) unsigned" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="attribute" type="VARCHAR" size="64" required="true" inheritance="single">
      <inheritance key="Calling-Station-Id" class="UserCheck2MacAddress" extends="UserCheck2"/>
    </column>
  </table>
</database>
XML;
        $this->buildClasses($schema);
        $this->assertTrue(class_exists('ConstantNameTest2\UserCheck2'));
        $this->assertTrue(class_exists('ConstantNameTest2\UserCheck2MacAddress'));
        $this->assertTrue(class_exists('ConstantNameTest2\Map\UserCheck2TableMap'));
        $this->assertTrue(defined('\ConstantNameTest2\Map\UserCheck2TableMap::CLASSKEY_CALLING_STATION_ID'));
        $this->assertEquals('Calling-Station-Id', UserCheck2TableMap::CLASSKEY_CALLING_STATION_ID);
    }

    /**
     * Test string with special characters as single inheritance key
     *
     * @return void
     */
    public function testSingleInheritanceKeyStringWithSpecialChars()
    {
        $schema = <<<XML
<database name="constant_name_test" namespace="ConstantNameTest3">
  <table name="radcheck" phpName="UserCheck3">
    <column name="id" type="INTEGER" sqlType="int(11) unsigned" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="attribute" type="VARCHAR" size="64" required="true" inheritance="single">
      <inheritance key="Key.-_:*" class="UserCheck3MacAddress" extends="UserCheck3"/>
    </column>
  </table>
</database>
XML;
        $this->buildClasses($schema);
        $this->assertTrue(class_exists('ConstantNameTest3\UserCheck3'));
        $this->assertTrue(class_exists('ConstantNameTest3\UserCheck3MacAddress'));
        $this->assertTrue(class_exists('ConstantNameTest3\Map\UserCheck3TableMap'));
        $this->assertTrue(defined('\ConstantNameTest3\Map\UserCheck3TableMap::CLASSKEY_KEY'));
        $this->assertEquals('Key.-_:*', UserCheck3TableMap::CLASSKEY_KEY);
    }

    /**
     * @param string $schema
     *
     * @return void
     */
    protected function buildClasses($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses();
    }
}
