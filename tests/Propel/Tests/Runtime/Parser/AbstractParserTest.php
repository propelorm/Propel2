<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Parser;

use Propel\Runtime\Exception\FileNotFoundException;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Parser\XmlParser;
use Propel\Tests\TestCase;
use Propel\Generator\Util\VfsTrait;

/**
 * Test for JsonParser class
 *
 * @author Francois Zaninotto
 */
class AbstractParserTest extends TestCase
{
    use VfsTrait;

    /**
     * @return void
     */
    public function testGetParser()
    {
        $parser = AbstractParser::getParser('XML');
        $this->assertTrue($parser instanceof XmlParser);
    }

    /**
     * @return void
     */
    public function testGetParserThrowsExceptionOnWrongParser()
    {
        $this->expectException(FileNotFoundException::class);

        $parser = AbstractParser::getParser('Foo');
    }

    /**
     * @return void
     */
    public function testLoad()
    {
        $file = $this->newFile('test_data.xml', <<<XML
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\\n" ?>
<foo>
<?php for(\$i=0;\$i<2;\$i++): ?>
  <bar prop="<?php echo \$i ?>"/>
<?php endfor ?>
</foo>

XML
);
        $parser = AbstractParser::getParser('XML');
        $content = $parser->load($file->url());
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

        $testFile = $this->newFile('propel_test');
        $parser = AbstractParser::getParser('XML');
        $parser->dump($testContent, $testFile->url());
        $content = file_get_contents($testFile->url());
        $this->assertEquals($testContent, $content);
    }
}
