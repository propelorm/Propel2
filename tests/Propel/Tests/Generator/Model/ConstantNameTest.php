<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Tests\TestCase;
use Propel\Generator\Util\QuickBuilder;

/**
 * Tests for generated constants' names.
 *
 * @author Boban Acimovic <boban.acimovic@gmail.com>
 */
class ConstantNameTest extends TestCase
{
    /**
     * Test normal string as single inheritance key
     */
    public function testSingleInheritanceKeyNormalString()
    {
        $schema = <<<XML
<database name="constant_name_test" namespace="ConstantNameTest1">
  <table name="radcheck" phpName="UserCheck">
    <column name="id" type="INTEGER" sqlType="int(11) unsigned" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="attribute" type="VARCHAR" size="64" required="true" inheritance="single">
      <inheritance key="Expiration" class="UserCheckExpiration" extends="UserCheck"/>
    </column>
  </table>
</database>
XML;
        $this->buildClasses($schema);
        $this->assertTrue(class_exists('ConstantNameTest1\UserCheckExpiration'));
    }

    /**
     * Test string with dashes as single inheritance key (original cause for this whole test)
     */

    public function testSingleInheritanceKeyStringWithDashes()
    {
        $schema = <<<XML
<database name="constant_name_test" namespace="ConstantNameTest2">
  <table name="radcheck" phpName="UserCheck2">
    <column name="id" type="INTEGER" sqlType="int(11) unsigned" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="attribute" type="VARCHAR" size="64" required="true" inheritance="single">
      <inheritance key="Calling-Station-Id" class="UserCheckMacAddress" extends="UserCheck2"/>
    </column>
  </table>
</database>
XML;
        $this->buildClasses($schema);
        $this->assertTrue(class_exists('ConstantNameTest2\Map\UserCheck2Map'));
        $this->assertEquals('Calling-Station-Id', ConstantNameTest2\Map\UserCheck2Map::CLASSKEY_CALLING_STATION_ID);
    }

    /**
     * Test string with special characters as single inheritance key
     */

    public function testSingleInheritanceKeyStringWithSpecialChars()
    {
        $schema = <<<XML
<database name="constant_name_test" namespace="ConstantNameTest3">
  <table name="radcheck" phpName="UserCheck3">
    <column name="id" type="INTEGER" sqlType="int(11) unsigned" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="attribute" type="VARCHAR" size="64" required="true" inheritance="single">
      <inheritance key="Key.-_:*" class="UserCheckMacAddress" extends="UserCheck3"/>
    </column>
  </table>
</database>
XML;
        $this->buildClasses($schema);
        $this->assertTrue(class_exists('ConstantNameTest3\UserCheckMacAddress'));
    }

    protected function buildClasses($schema)
    {
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->buildClasses();
    }
}
