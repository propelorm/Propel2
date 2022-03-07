<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Reverse;

use Propel\Generator\Reverse\MssqlSchemaParser;
use Propel\Tests\TestCase;

/**
 * Tests for Mssql database schema parser.
 *
 * @author Pierre Tachoire
 */
class MssqlSchemaParserTest extends TestCase
{
    /**
     * @return void
     */
    public function testCleanDelimitedIdentifiers()
    {
        $parser = new TestableMssqlSchemaParser(null);

        $expected = 'this is a tablename';

        $tested = $parser->cleanDelimitedIdentifiers('\'' . $expected . '\'');
        $this->assertEquals($expected, $tested);

        $tested = $parser->cleanDelimitedIdentifiers('\'' . $expected);
        $this->assertEquals('\'' . $expected, $tested);

        $tested = $parser->cleanDelimitedIdentifiers($expected . '\'');
        $this->assertEquals($expected . '\'', $tested);

        $expected = 'this is a tabl\'ename';

        $tested = $parser->cleanDelimitedIdentifiers('\'' . $expected . '\'');
        $this->assertEquals($expected, $tested);

        $tested = $parser->cleanDelimitedIdentifiers('\'' . $expected);
        $this->assertEquals('\'' . $expected, $tested);

        $tested = $parser->cleanDelimitedIdentifiers($expected . '\'');
        $this->assertEquals($expected . '\'', $tested);

        $expected = 'this is a\'tabl\'ename';

        $tested = $parser->cleanDelimitedIdentifiers('\'' . $expected . '\'');
        $this->assertEquals($expected, $tested);

        $tested = $parser->cleanDelimitedIdentifiers('\'' . $expected);
        $this->assertEquals('\'' . $expected, $tested);

        $tested = $parser->cleanDelimitedIdentifiers($expected . '\'');
        $this->assertEquals($expected . '\'', $tested);
    }
}

class TestableMssqlSchemaParser extends MssqlSchemaParser
{
    public function cleanDelimitedIdentifiers($identifier): string
    {
        return parent::cleanDelimitedIdentifiers($identifier);
    }
}
