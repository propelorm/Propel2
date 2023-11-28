<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Builder\Om\TableMapBuilder;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\TestCase;

/**
 * Unit test suite for the Inheritance model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class InheritanceTest extends TestCase
{
    /**
     * @return void
     */
    public function testCreateNewInheritance()
    {
        $column = $this
            ->getMockBuilder('Propel\Generator\Model\Column')
            ->disableOriginalConstructor()
            ->getMock();

        $inheritance = new Inheritance();
        $inheritance->setPackage('Foo');
        $inheritance->setAncestor('BaseObject');
        $inheritance->setKey('baz');
        $inheritance->setClassName('Foo\Bar');
        $inheritance->setColumn($column);

        $this->assertInstanceOf('Propel\Generator\Model\Column', $inheritance->getColumn());
        $this->assertSame('Foo', $inheritance->getPackage());
        $this->assertSame('BaseObject', $inheritance->getAncestor());
        $this->assertSame('baz', $inheritance->getKey());
        $this->assertSame('Foo\Bar', $inheritance->getClassName());
    }

    /**
     * @return void
     */
    public function testSetupObject()
    {
        $inheritance = new Inheritance();
        $inheritance->loadMapping([
            'key' => 'baz',
            'extends' => 'BaseObject',
            'class' => 'Foo\Bar',
            'package' => 'Foo',
        ]);

        $this->assertSame('Foo', $inheritance->getPackage());
        $this->assertSame('BaseObject', $inheritance->getAncestor());
        $this->assertSame('baz', $inheritance->getKey());
        $this->assertSame('Foo\Bar', $inheritance->getClassName());
    }

    public function singleInheritanceTestDataProvider(){
        return [
            // string $type, $key, string $expectedClasskey
            ['varchar', 'le_key', "LE_KEY = 'le_key'"],
            ['enum', 'default', "DEFAULT = 'default'"],
            ['integer', 4, '4 = 4'],
            ['smallint', 4, '4 = 4'],
            ['float', 0.5, "0_5 = 0.5"],
            ['decimal', '0.33', "0_33 = '0.33'"],
        ];
    }

    /**
     * @dataProvider singleInheritanceTestDataProvider
     * @return void
     */
    public function testSingleInheritanceKeyType(string $type, $key, string $expectedClasskey)
    {
        
        $databaseXml = <<<XML
<database namespace="SingleTableInheritanceTest">
    <table name="Inheriter">
        <column
            name="type_indicator"
            type="$type"
            inheritance="single"
        >
            <inheritance key="$key" class="Inheriter"/>
        </column>
    </table>
</database>
XML;
        $schemaBuilder = new QuickBuilder();
        $schemaBuilder->setSchema($databaseXml);
        $database = $schemaBuilder->getDatabase();
        $table = $database->getTable('Inheriter');
        $builder = new TableMapBuilder($table);
        $builder->setGeneratorConfig(new QuickGeneratorConfig());
        $script = '';
        $builder->addInheritanceColumnConstants($script);
        
        $expectedClasskeyDeclaration = "public const CLASSKEY_$expectedClasskey;";
        $description = "Inheritance column of type $type should generate $type classkey values";

        $this->assertStringContainsString($expectedClasskeyDeclaration, $script, $description);
    }
}
