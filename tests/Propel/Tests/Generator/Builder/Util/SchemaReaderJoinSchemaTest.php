<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Util;

use Propel\Generator\Builder\Util\SchemaReader;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Tests\TestCase;

class SchemaReaderJoinSchemaTest extends TestCase
{
    /**
     * @return void
     */
    public function testJoinXmlSchemaWithMultipleDatabaseSchema()
    {
        $expectedSchema = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<app-data>
  <database name="default" defaultIdMethod="native" schema="foo" defaultPhpNamingMethod="underscore">
    <table name="table1" idMethod="native" phpName="Table1">
      <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    </table>
    <table name="table2" schema="bar" idMethod="native" phpName="Table2">
      <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
      <column name="table1_id" phpName="Table1Id" type="INTEGER"/>
      <foreign-key foreignTable="table1" foreignSchema="foo" name="table2_fk_6e7121">
        <reference local="table1_id" foreign="id"/>
      </foreign-key>
    </table>
  </database>
</app-data>
EOF;

        $fooReader = new SchemaReader(new PgsqlPlatform());
        $barReader = new SchemaReader(new PgsqlPlatform());

        $fooSchema = $fooReader->parseFile($this->getSchemaFile('fooSchema.xml'));
        $barSchema = $barReader->parseFile($this->getSchemaFile('barSchema.xml'));
        $fooSchema->joinSchemas([$barSchema]);

        $this->assertEquals($expectedSchema, $fooSchema->toString());
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
