<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Tests\TestCase;

/**
 * Test class for OMBuilder.
 *
 * @author François Zaninotto
 * @version    $Id: OMBuilderBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 */
class AbstractOMBuilderNamespaceTest extends TestCase
{
    public function testNoNamespace()
    {
        $d = new Database('fooDb');
        $t = new Table('fooTable');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertNull($builder->getNamespace(), 'Builder namespace is null when neither the db nor the table have namespace');
    }

    public function testDbNamespace()
    {
        $d = new Database('fooDb');
        $d->setNamespace('Foo\\Bar');
        $t = new Table('fooTable');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the database namespace when no table namespace is set');
    }

    public function testTableNamespace()
    {
        $d = new Database('fooDb');
        $t = new Table('fooTable');
        $t->setNamespace('Foo\\Bar');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the table namespace when no database namespace is set');
    }

    public function testAbsoluteTableNamespace()
    {
        $d = new Database('fooDb');
        $t = new Table('fooTable');
        $t->setNamespace('\\Foo\\Bar');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the table namespace when it is set as absolute');
    }

    public function testAbsoluteTableNamespaceAndDbNamespace()
    {
        $d = new Database('fooDb');
        $d->setNamespace('Baz');
        $t = new Table('fooTable');
        $t->setNamespace('\\Foo\\Bar');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the table namespace when it is set as absolute');
    }

    public function testTableNamespaceAndDbNamespace()
    {
        $d = new Database('fooDb');
        $d->setNamespace('Baz');
        $t = new Table('fooTable');
        $t->setNamespace('Foo\\Bar');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Baz\\Foo\\Bar', $builder->getNamespace(), 'Builder namespace is composed from the database and table namespaces when both are set');
    }

    public function testDeclareClassNamespace()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClassNamespace('Foo');
        $this->assertEquals(array('' => array('Foo' => 'Foo')), $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Bar');
        $this->assertEquals(array('' => array('Foo' => 'Foo', 'Bar' => 'Bar')), $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Foo');
        $this->assertEquals(array('' => array('Foo' => 'Foo', 'Bar' => 'Bar')), $builder->getDeclaredClasses());
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClassNamespace('Foo', 'Foo');
        $this->assertEquals(array('Foo' => array('Foo' => 'Foo')), $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Bar', 'Foo');
        $this->assertEquals(array('Foo' => array('Foo' => 'Foo', 'Bar' => 'Bar')), $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Foo', 'Foo');
        $this->assertEquals(array('Foo' => array('Foo' => 'Foo', 'Bar' => 'Bar')), $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Bar', 'Bar', 'Bar2');
        $this->assertEquals(array('Foo' => array('Foo' => 'Foo', 'Bar' => 'Bar'), 'Bar' => array('Bar' => 'Bar2')), $builder->getDeclaredClasses());
    }

    /**
     * @expectedException \Propel\Generator\Exception\LogicException
     */
    public function testDeclareClassNamespaceDuplicateException()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClassNamespace('Bar');
        $builder->declareClassNamespace('Bar', 'Foo');
    }

    public function testGetDeclareClass()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $this->assertEquals(array(), $builder->getDeclaredClasses());
        $builder->declareClass('\\Foo');
        $this->assertEquals(array('Foo' => 'Foo'), $builder->getDeclaredClasses(''));
        $builder->declareClass('Bar');
        $this->assertEquals(array('Foo' => 'Foo', 'Bar' => 'Bar'), $builder->getDeclaredClasses(''));
        $builder->declareClass('Foo\\Bar2');
        $this->assertEquals(array('Bar2' => 'Bar2'), $builder->getDeclaredClasses('Foo'));
        $builder->declareClass('Foo\\Bar\\Baz');
        $this->assertEquals(array('Bar2' => 'Bar2'), $builder->getDeclaredClasses('Foo'));
        $this->assertEquals(array('Baz' => 'Baz'), $builder->getDeclaredClasses('Foo\\Bar'));
        $builder->declareClass('\\Hello\\World');
        $this->assertEquals(array('World' => 'World'), $builder->getDeclaredClasses('Hello'));
    }

    public function testDeclareClasses()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClasses('Foo', '\\Bar', 'Baz\\Baz', 'Hello\\Cruel\\World');
        $expected = array(
            ''             => array('Foo' => 'Foo', 'Bar' => 'Bar'),
            'Baz'          => array('Baz' => 'Baz'),
            'Hello\\Cruel' => array('World' => 'World')
        );
        $this->assertEquals($expected, $builder->getDeclaredClasses());
    }
}

class TestableOMBuilder2 extends AbstractOMBuilder
{
    public static function getRelatedBySuffix(ForeignKey $fk)
    {
        return parent::getRelatedBySuffix($fk);
    }

    public static function getRefRelatedBySuffix(ForeignKey $fk)
    {
        return parent::getRefRelatedBySuffix($fk);
    }

    public function getUnprefixedClassName()
    {
    }

    protected function addClassOpen(&$script)
    {

    }

    protected function addClassBody(&$script)
    {

    }

    protected function addClassClose(&$script)
    {

    }
}
