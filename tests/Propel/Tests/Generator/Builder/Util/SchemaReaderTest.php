<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Util;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Exception\SchemaException;
use Propel\Tests\TestCase;

class SchemaReaderTest extends TestCase
{
    /**
     * The schema reader.
     *
     * @var \Propel\Generator\Builder\Util\SchemaReader
     */
    private $reader;

    /**
     * @return void
     */
    public function testParseStringEmptySchema()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM does not support yet xml with no elements.');
        }

        $schema = $this->reader->parseString('<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>');

        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data/>
EOF;
        $this->assertEquals($xml, $schema->toString());
    }

    /**
     * @return void
     */
    public function testParseStringSchemaWithoutXmlDeclaration()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM does not support yet xml with no elements.');
        }

        $schema = $this->reader->parseString('');

        $xml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data/>
EOF;

        $this->assertEquals($xml, $schema->toString());
    }

    /**
     * @return void
     */
    public function testParseStringIncorrectSchema()
    {
        $this->expectException(SchemaException::class);

        $this->reader->parseString('<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?><foo/>');
    }

    /**
     * @return void
     */
    public function testParseStringDatabase()
    {
        $schema = $this->reader->parseString('<database name="foo"></database>');

        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore"/>
</app-data>
EOF;

        $this->assertEquals($expectedSchema, $schema->toString());
    }

    /**
     * @return void
     */
    public function testParseStringTable()
    {
        $xmlSchema = '<database name="foo"><table name="bar"><column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/></table></database>';

        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore">
    <table name="bar" idMethod="native" phpName="Bar">
      <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </table>
  </database>
</app-data>
EOF;

        $schema = $this->reader->parseString($xmlSchema);

        $this->assertEquals($expectedSchema, $schema->toString());
    }

    /**
     * @return void
     */
    public function testParseFile()
    {
        $schema = $this->reader->parseFile($this->getSchemaFile('testSchema.xml'));
        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore">
    <table name="bar" idMethod="native" phpName="Bar">
      <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </table>
  </database>
</app-data>
EOF;
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    /**
     * @return void
     */
    public function testParseFileExternalSchema()
    {
        $schema = $this->reader->parseFile($this->getSchemaFile('outerSchema.xml'));
        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore">
    <table name="bar1" idMethod="native" phpName="Bar1">
      <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </table>
    <table name="bar2" idMethod="native" phpName="Bar2" skipSql="true" forReferenceOnly="true">
      <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </table>
  </database>
</app-data>
EOF;
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    /**
     * @return void
     */
    public function testXmlErrorReporting()
    {
        $brokenSchema = 'lorem ipsum';
        $this->expectException(SchemaException::class);
        try {
            $this->reader->parseString($brokenSchema);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('XML error: Not well-formed', $message);

            throw $e;
        }
    }

    /**
     * @return void
     */
    public function testMissingAttributeError()
    {
        $schemaWithMissingColumnName = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<database name="leDatabase">
  <table name="leTable">
    <behavior name="timestampable">
      <parameter-list>
      </parameter-list>
    </behavior>
  </table>
</database>
EOF;
        $this->expectException(SchemaException::class);
        try {
            $this->reader->parseString($schemaWithMissingColumnName);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Parameter misses expected attribute "name"', $message);

            throw $e;
        }
    }

    /**
     * @return void
     */
    public function testExceptionContainsFilename()
    {
        $schemaWithoutDatabase = '<table name="leTable"></table>';
        $xmlFileName = 'LeSchema.xml';
        $this->expectException(SchemaException::class);
        try {
            $this->reader->parseString($schemaWithoutDatabase, $xmlFileName);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString($xmlFileName, $message);

            throw $e;
        }
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->reader = new SchemaReader();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->reader = null;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    protected function getSchemaFile($filename)
    {
        return realpath(FIXTURES . 'generator' . DS . 'builder' . DS . $filename);
    }
}
