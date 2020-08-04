<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Parser;

use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Parser\XmlParser;
use Propel\Tests\TestCase;

/**
 * Test for JsonParser class
 *
 * @author Francois Zaninotto
 */
class AbstractParserTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetParser()
    {
        $parser = AbstractParser::getParser('XML');
        $this->assertTrue($parser instanceof XmlParser);
    }

    /**
     * @expectedException \Propel\Runtime\Exception\FileNotFoundException
     *
     * @return void
     */
    public function testGetParserThrowsExceptionOnWrongParser()
    {
        $parser = AbstractParser::getParser('Foo');
    }

    /**
     * @return void
     */
    public function testLoad()
    {
        $fixtureFile = __DIR__ . '/fixtures/test_data.xml';
        $parser = AbstractParser::getParser('XML');
        $content = $parser->load($fixtureFile);
        $expectedContent = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<foo>
  <bar prop="0"/>
  <bar prop="1"/>
</foo>

EOF;
        $this->assertEquals($expectedContent, $content, 'AbstractParser::load() executes PHP code in files');
    }

    /**
     * @return void
     */
    public function testDump()
    {
        $testContent = 'Foo Content';
        $testFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'propel_test_' . microtime();
        $parser = AbstractParser::getParser('XML');
        $parser->dump($testContent, $testFile);
        $content = file_get_contents($testFile);
        $this->assertEquals($testContent, $content);
        unlink($testFile);
    }
}
