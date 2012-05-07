<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Generator\Builder\Util;

use Propel\Generator\Builder\Util\SchemaReader;

/**
 * Tests for SchemaReader class
 *
 */
class SchemaReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testParseStringEmptySchema()
    {
        $xmlSchema = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?>';
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseString($xmlSchema);
        $expectedSchema = "<app-data>
</app-data>";
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    public function testParseStringSchemaWithoutXmlDeclaration()
    {
        $xmlSchema = '';
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseString($xmlSchema);
        $expectedSchema = "<app-data>
</app-data>";
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    /**
     * @expectedException \Propel\Generator\Exception\SchemaException
     */
    public function testParseStringIncorrectSchema()
    {
        $xmlSchema = '<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?><foo/>';
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseString($xmlSchema);
    }

    public function testParseStringDatabase()
    {
        $xmlSchema = '<database name="foo"></database>';
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseString($xmlSchema);
        $expectedDatabase = '<database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore" defaultTranslateMethod="none"/>';
        $database = $schema->getDatabase();
        $this->assertEquals($expectedDatabase, $database->toString());
        $expectedSchema = "<app-data>\n$expectedDatabase\n</app-data>";
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    public function testParseStringTable()
    {
        $xmlSchema = '<database name="foo"><table name="bar"><column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/></table></database>';
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseString($xmlSchema);
        $database = $schema->getDatabase();
        $table = $database->getTable('bar');
        $expectedTable = <<<EOF
<table name="bar" phpName="Bar" idMethod="false" readOnly="false" reloadOnInsert="false" reloadOnUpdate="false" abstract="false">
  <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
</table>
EOF;
        $this->assertEquals($expectedTable, $table->toString());
    }

    public function testParseFile()
    {
        $path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testSchema.xml');
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseFile($path);
        $expectedSchema = <<<EOF
<app-data>
<database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore" defaultTranslateMethod="none">
  <table name="bar" phpName="Bar" idMethod="false" readOnly="false" reloadOnInsert="false" reloadOnUpdate="false" abstract="false">
    <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
  </table>
</database>
</app-data>
EOF;
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    public function testParseFileExternalSchema()
    {
        $path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'outerSchema.xml');
        $schemaReader = new SchemaReader();
        $schema = $schemaReader->parseFile($path);
        $expectedSchema = <<<EOF
<app-data>
<database name="foo" defaultIdMethod="native" defaultPhpNamingMethod="underscore" defaultTranslateMethod="none">
  <table name="bar1" phpName="Bar1" idMethod="false" readOnly="false" reloadOnInsert="false" reloadOnUpdate="false" abstract="false">
    <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
  </table>
  <table name="bar2" phpName="Bar2" idMethod="false" readOnly="false" reloadOnInsert="false" reloadOnUpdate="false" forReferenceOnly="true" abstract="false">
    <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
  </table>
</database>
</app-data>
EOF;
        $this->assertEquals($expectedSchema, $schema->toString());
    }
}
