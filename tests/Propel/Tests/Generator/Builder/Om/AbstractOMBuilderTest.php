<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Generator\Builder\Om\AbstractOMBuilder;

use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\TestCase;

/**
 * Test class for OMBuilder.
 *
 * @author François Zaninotto
 * @version    $Id: OMBuilderBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 */
class AbstractOMBuilderTest extends TestCase
{

    public function testClear()
    {
        $b = new Book();
        $b->setNew(false);
        $b->clear();
        $this->assertTrue($b->isNew(), 'clear() sets the object to new');
        $b = new Book();
        $b->setDeleted(true);
        $b->clear();
        $this->assertFalse($b->isDeleted(), 'clear() sets the object to not deleted');
    }

    public function testToStringUsesDefaultStringFormat()
    {
        $author = new Author();
        $author->setFirstName('John');
        $author->setLastName('Doe');
        $expected = <<<EOF
Id: null
FirstName: John
LastName: Doe
Email: null
Age: null

EOF;
        $this->assertEquals($expected, (string) $author, 'generated __toString() uses default string format and exportTo()');

        $publisher = new Publisher();
        $publisher->setId(345345);
        $publisher->setName('Peguinoo');
        $expected = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <Id>345345</Id>
  <Name><![CDATA[Peguinoo]]></Name>
</data>

EOF;
        $this->assertEquals($expected, (string) $publisher, 'generated __toString() uses default string format and exportTo()');
    }

    /**
     * @dataProvider dataGetPackagePath
     */
    public function testGetPackagePath($package, $expectedPath)
    {
        $builder = new OMBuilderMock();
        $builder->setPackage($package);

        $this->assertEquals($expectedPath, $builder->getPackagePath());
    }

    public function dataGetPackagePath()
    {
        return array(
            array('', ''),
            array('foo.bar', 'foo/bar'),
            array('foo/bar', 'foo/bar'),
            array('foo.bar.map', 'foo/bar/map'),
            array('foo.bar.om', 'foo/bar/om'),
            array('foo.bar.baz', 'foo/bar/baz'),
            array('foo.bar.baz.om', 'foo/bar/baz/om'),
            array('foo.bar.baz.map', 'foo/bar/baz/map'),
            array('foo/bar/baz', 'foo/bar/baz'),
            array('foo/bar/baz/map', 'foo/bar/baz/map'),
            array('foo/bar/baz/om', 'foo/bar/baz/om'),
            array('foo/bar.baz', 'foo/bar.baz'),
            array('foo/bar.baz.map', 'foo/bar.baz/map'),
            array('foo/bar.baz.om', 'foo/bar.baz/om'),
            array('foo.bar/baz', 'foo.bar/baz'),
            array('foo.bar/baz.om', 'foo.bar/baz/om'),
            array('foo.bar/baz.map', 'foo.bar/baz/map'),
        );
    }

}

class OMBuilderMock extends AbstractOMBuilder
{
    protected $pkg;

    public function __construct()
    {
    }

    public function setPackage($pkg)
    {
        $this->pkg = $pkg;
    }

    public function getPackage()
    {
        return $this->pkg;
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
