<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Exception\LogicException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Tests\TestCase;

/**
 * Test class for OMBuilder.
 *
 * @author François Zaninotto
 */
class AbstractOMBuilderNamespaceTest extends TestCase
{
    /**
     * @return void
     */
    public function testNoNamespace()
    {
        $d = new Database('fooDb');
        $t = new Table('fooTable');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertNull($builder->getNamespace(), 'Builder namespace is null when neither the db nor the table have namespace');
    }

    /**
     * @return void
     */
    public function testDbNamespace()
    {
        $d = new Database('fooDb');
        $d->setNamespace('Foo\\Bar');
        $t = new Table('fooTable');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the database namespace when no table namespace is set');
    }

    /**
     * @return void
     */
    public function testTableNamespace()
    {
        $d = new Database('fooDb');
        $t = new Table('fooTable');
        $t->setNamespace('Foo\\Bar');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the table namespace when no database namespace is set');
    }

    /**
     * @return void
     */
    public function testAbsoluteTableNamespace()
    {
        $d = new Database('fooDb');
        $t = new Table('fooTable');
        $t->setNamespace('\\Foo\\Bar');
        $d->addTable($t);
        $builder = new TestableOMBuilder2($t);
        $this->assertEquals('Foo\\Bar', $builder->getNamespace(), 'Builder namespace is the table namespace when it is set as absolute');
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testDeclareClassNamespace()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClassNamespace('Foo');
        $this->assertEquals(['' => ['Foo' => 'Foo']], $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Bar');
        $this->assertEquals(['' => ['Foo' => 'Foo', 'Bar' => 'Bar']], $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Foo');
        $this->assertEquals(['' => ['Foo' => 'Foo', 'Bar' => 'Bar']], $builder->getDeclaredClasses());
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClassNamespace('Foo', 'Foo');
        $this->assertEquals(['Foo' => ['Foo' => 'Foo']], $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Bar', 'Foo');
        $this->assertEquals(['Foo' => ['Foo' => 'Foo', 'Bar' => 'Bar']], $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Foo', 'Foo');
        $this->assertEquals(['Foo' => ['Foo' => 'Foo', 'Bar' => 'Bar']], $builder->getDeclaredClasses());
        $builder->declareClassNamespace('Bar', 'Bar', 'Bar2');
        $this->assertEquals(['Foo' => ['Foo' => 'Foo', 'Bar' => 'Bar'], 'Bar' => ['Bar' => 'Bar2']], $builder->getDeclaredClasses());
    }

    /**
     * @return void
     */
    public function testDeclareClassNamespaceDuplicateException()
    {
        $this->expectException(LogicException::class);

        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClassNamespace('Bar');
        $builder->declareClassNamespace('Bar', 'Foo');
    }

    /**
     * @return void
     */
    public function testGetDeclareClass()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $this->assertEquals([], $builder->getDeclaredClasses());
        $builder->declareClass('\\Foo');
        $this->assertEquals(['Foo' => 'Foo'], $builder->getDeclaredClasses(''));
        $builder->declareClass('Bar');
        $this->assertEquals(['Foo' => 'Foo', 'Bar' => 'Bar'], $builder->getDeclaredClasses(''));
        $builder->declareClass('Foo\\Bar2');
        $this->assertEquals(['Bar2' => 'Bar2'], $builder->getDeclaredClasses('Foo'));
        $builder->declareClass('Foo\\Bar\\Baz');
        $this->assertEquals(['Bar2' => 'Bar2'], $builder->getDeclaredClasses('Foo'));
        $this->assertEquals(['Baz' => 'Baz'], $builder->getDeclaredClasses('Foo\\Bar'));
        $builder->declareClass('\\Hello\\World');
        $this->assertEquals(['World' => 'World'], $builder->getDeclaredClasses('Hello'));
    }

    /**
     * @return void
     */
    public function testDeclareClasses()
    {
        $builder = new TestableOMBuilder2(new Table('fooTable'));
        $builder->declareClasses('Foo', '\\Bar', 'Baz\\Baz', 'Hello\\Cruel\\World');
        $expected = [
            '' => ['Foo' => 'Foo', 'Bar' => 'Bar'],
            'Baz' => ['Baz' => 'Baz'],
            'Hello\\Cruel' => ['World' => 'World'],
        ];
        $this->assertEquals($expected, $builder->getDeclaredClasses());
    }

    /**
     * @return array
     */
    public function namespaceDataProvider(): array
    {
        //[<table namespace>, <class namespace>, <message>]]
        return [
            ['\\My\\Namespace', '\\My\\Namespace', 'slashed namespace should work'],
            ['My\\Namespace', 'My\\Namespace', 'non-slashed namespace should work'],
            ['My\\Namespace', '\\My\\Namespace', 'slashes are stripped from class namespace anyway'],
            ['\\My\\Namespace', 'My\\Namespace', 'slashes are stripped from table namespace anyway'],
        ];
    }

    /**
     * @dataProvider namespaceDataProvider
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testDeclareClassNamespaceIgnoresLeadingSlashInNamespace(string $tableNamespace, string $classNamespace, string $message): void
    {
        $table = new Table('Table1');
        $table->setNamespace($tableNamespace);

        $builder = new TestableOMBuilder2($table);

        $builder->declareClassNamespace('MyTable1Class', $classNamespace . '\\Base');
        try{
            $builder->declareClassNamespace('MyTable1Class', $classNamespace);
        } catch(LogicException $e) {
            $this->fail($message);
        }
    }
}

class TestableOMBuilder2 extends AbstractOMBuilder
{
    public static function getRelatedBySuffix(ForeignKey $fk): string
    {
        return parent::getRelatedBySuffix($fk);
    }

    public static function getRefRelatedBySuffix(ForeignKey $fk): string
    {
        return parent::getRefRelatedBySuffix($fk);
    }

    /**
     * @return string
     */
    public function getUnprefixedClassName(): string
    {
        return '';
    }

    /**
     * @return void
     */
    protected function addClassOpen(&$script): void
    {
    }

    /**
     * @return void
     */
    protected function addClassBody(&$script): void
    {
    }

    /**
     * @return void
     */
    protected function addClassClose(&$script): void
    {
    }
}
