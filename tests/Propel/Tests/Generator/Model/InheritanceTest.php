<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Inheritance;
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
}
