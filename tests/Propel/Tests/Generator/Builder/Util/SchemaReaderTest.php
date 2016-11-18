<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Util;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Tests\TestCase;

class SchemaReaderTest extends TestCase
{
    /**
     * The schema reader.
     *
     * @var SchemaReader
     */
    private $reader;

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
     * @expectedException \Propel\Generator\Exception\SchemaException
     */
    public function testParseStringIncorrectSchema()
    {
        $this->reader->parseString('<?xml version="1.0" encoding="ISO-8859-1" standalone="no"?><foo/>');
    }

    public function testParseStringDatabase()
    {
        $schema = $this->reader->parseString('<database name="foo"></database>');

        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native"/>
</app-data>
EOF;

        $this->assertEquals($expectedSchema, $schema->toString());
    }

    public function testParseStringTable()
    {
        $xmlSchema = '<database name="foo"><entity name="bar" tableName="b_ar"><field name="id" columnName="i_d" primaryKey="true" type="INTEGER" autoIncrement="true"/></entity></database>';

        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native">
    <entity name="bar" idMethod="native" tableName="b_ar">
      <field name="id" columnName="i_d" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </entity>
  </database>
</app-data>
EOF;

        $schema = $this->reader->parseString($xmlSchema);

        $this->assertEquals($expectedSchema, $schema->toString());
    }

    public function testParseFile()
    {
        $schema = $this->reader->parseFile($this->getSchemaFile('testSchema.xml'));
        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native">
    <entity name="Bar" idMethod="native" tableName="bar">
      <field name="id" columnName="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </entity>
  </database>
</app-data>
EOF;
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    public function testParseFileExternalSchema()
    {
        $schema = $this->reader->parseFile($this->getSchemaFile('outerSchema.xml'));
        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="foo" defaultIdMethod="native">
    <entity name="Bar1" idMethod="native" tableName="bar1">
      <field name="id" columnName="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </entity>
    <entity name="Bar2" idMethod="native" tableName="bar2" skipSql="true" forReferenceOnly="true">
      <field name="id" columnName="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </entity>
  </database>
</app-data>
EOF;
        $this->assertEquals($expectedSchema, $schema->toString());
    }

    protected function setUp()
    {
        $this->reader = new SchemaReader();
    }

    protected function tearDown()
    {
        $this->reader = null;
    }

    protected function getSchemaFile($filename)
    {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . $filename);
    }
}
