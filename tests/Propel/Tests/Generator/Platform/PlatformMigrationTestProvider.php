<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Diff\ColumnComparator;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Table;

/**
 * provider for platform migration unit tests
 */
abstract class PlatformMigrationTestProvider extends PlatformTestBase
{
    /**
     * @return array
     */
    public function providerForTestGetModifyDatabaseDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="blooopoo" type="INTEGER"/>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
    <table name="foo3">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="yipee" type="INTEGER"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar1" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="false"/>
        <column name="baz3" type="LONGVARCHAR"/>
    </table>
    <table name="foo4">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="yipee" type="INTEGER"/>
    </table>
    <table name="foo5">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="lkdjfsh" type="INTEGER"/>
        <column name="dfgdsgf" type="LONGVARCHAR"/>
    </table>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);

        return [[DatabaseComparator::computeDiff($d1, $d2, $caseInsensitive = false, $withRenaming = true)]];
    }

    public function providerForTestGetRenameTableDDL()
    {
        return [['foo1', 'foo2']];
    }

    public function providerForTestGetModifyTableDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
        <foreign-key name="foo1_fk_1" foreignTable="foo2">
            <reference local="bar" foreign="bar"/>
        </foreign-key>
        <foreign-key name="foo1_fk_2" foreignTable="foo2">
            <reference local="baz" foreign="baz"/>
        </foreign-key>
        <index name="bar_fk">
            <index-column name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-column name="bar"/>
            <index-column name="baz"/>
        </index>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar1" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="false"/>
        <column name="baz3" type="LONGVARCHAR"/>
        <foreign-key name="foo1_fk_1" foreignTable="foo2">
            <reference local="bar1" foreign="bar"/>
        </foreign-key>
        <index name="bar_fk">
            <index-column name="bar1"/>
        </index>
        <index name="baz_fk">
            <index-column name="baz3"/>
        </index>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo');

        return [[TableComparator::computeDiff($t1, $t2)]];
    }

    public function providerForTestGetModifyTableColumnsDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar1" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="false"/>
        <column name="baz3" type="LONGVARCHAR"/>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo');
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->compareColumns();

        return [[$tc->getTableDiff()]];
    }

    public function providerForTestGetModifyTablePrimaryKeysDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER"/>
        <column name="bar" type="INTEGER" primaryKey="true"/>
        <column name="baz" type="VARCHAR" size="12" required="false"/>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo');
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->comparePrimaryKeys();

        return [[$tc->getTableDiff()]];
    }

    public function providerForTestGetModifyTableIndicesDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
        <column name="bax" type="VARCHAR" size="12" required="true"/>
        <index name="bar_fk">
            <index-column name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-column name="bar"/>
            <index-column name="baz"/>
        </index>
        <unique name="bax_unique">
            <unique-column name="bax"/>
        </unique>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
        <column name="bay" type="VARCHAR" size="12" required="true"/>
        <column name="bax" type="VARCHAR" size="12" required="true"/>
        <index name="bar_baz_fk">
            <index-column name="id"/>
            <index-column name="bar"/>
            <index-column name="baz"/>
        </index>
        <index name="baz_fk">
            <index-column name="baz"/>
        </index>
        <unique name="bax_bay_unique">
            <unique-column name="bax"/>
            <unique-column name="bay"/>
        </unique>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo');
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->compareIndices();

        return [[$tc->getTableDiff()]];
    }

    public function providerForTestGetModifyTableForeignKeysDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
        <foreign-key name="foo1_fk_1" foreignTable="foo2">
            <reference local="bar" foreign="bar"/>
        </foreign-key>
        <foreign-key name="foo1_fk_2" foreignTable="foo2">
            <reference local="bar" foreign="bar"/>
            <reference local="baz" foreign="baz"/>
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
        <foreign-key name="foo1_fk_2" foreignTable="foo2">
            <reference local="bar" foreign="bar"/>
            <reference local="id" foreign="id"/>
        </foreign-key>
        <foreign-key name="foo1_fk_3" foreignTable="foo2">
            <reference local="baz" foreign="baz"/>
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <column name="baz" type="VARCHAR" size="12" required="true"/>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo1');
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->compareForeignKeys();

        return [[$tc->getTableDiff()]];
    }

    public function providerForTestGetModifyTableForeignKeysSkipSqlDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <foreign-key name="foo1_fk_1" foreignTable="foo2">
            <reference local="bar" foreign="bar"/>
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <foreign-key name="foo1_fk_1" foreignTable="foo2" skipSql="true">
            <reference local="bar" foreign="bar"/>
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo1');
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->compareForeignKeys();

        return [[$tc->getTableDiff()]];
    }

    public function providerForTestGetModifyTableForeignKeysSkipSql2DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
        <foreign-key name="foo1_fk_1" foreignTable="foo2" skipSql="true">
            <reference local="bar" foreign="bar"/>
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getTable('foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable('foo1');
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->compareForeignKeys();

        return [[$tc->getTableDiff()]];
    }

    public function providerForTestGetRemoveColumnDDL()
    {
        $table = new Table('foo');
        $table->setIdentifierQuoting(true);
        $column = new Column('bar');
        $table->addColumn($column);

        return [[$column]];
    }

    public function providerForTestGetRenameColumnDDL()
    {
        $t1 = new Table('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('bar1');
        $c1->getDomain()->setType('DOUBLE');
        $c1->getDomain()->setSqlType('DOUBLE');
        $c1->getDomain()->replaceSize(2);
        $t1->addColumn($c1);

        $t2 = new Table('foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Column('bar2');
        $c2->getDomain()->setType('DOUBLE');
        $c2->getDomain()->setSqlType('DOUBLE');
        $c2->getDomain()->replaceSize(2);
        $t2->addColumn($c2);

        return [[$c1, $c2]];
    }

    public function providerForTestGetModifyColumnDDL()
    {
        $t1 = new Table('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('bar');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addColumn($c1);
        $t2 = new Table('foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Column('bar');
        $c2->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceSize(3);
        $t2->addColumn($c2);

        return [[ColumnComparator::computeDiff($c1, $c2)]];
    }

    public function providerForTestGetModifyColumnsDDL()
    {
        $t1 = new Table('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('bar1');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addColumn($c1);
        $c2 = new Column('bar2');
        $c2->getDomain()->setType('INTEGER');
        $c2->getDomain()->setSqlType('INTEGER');
        $t1->addColumn($c2);

        $t2 = new Table('foo');
        $t2->setIdentifierQuoting(true);
        $t2->setIdentifierQuoting(true);
        $c3 = new Column('bar1');
        $c3->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceSize(3);
        $t2->addColumn($c3);
        $c4 = new Column('bar2');
        $c4->getDomain()->setType('INTEGER');
        $c4->getDomain()->setSqlType('INTEGER');
        $c4->setNotNull(true);
        $t2->addColumn($c4);

        return [[[
        ColumnComparator::computeDiff($c1, $c3),
        ColumnComparator::computeDiff($c2, $c4),
        ]]];
    }

    public function providerForTestGetAddColumnDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar" type="INTEGER"/>
    </table>
</database>
EOF;
        $column = $this->getDatabaseFromSchema($schema)->getTable('foo')->getColumn('bar');

        return [[$column]];
    }

    public function providerForTestGetAddColumnsDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true"/>
        <column name="bar1" type="INTEGER"/>
        <column name="bar2" type="DOUBLE" scale="2" size="3" default="-1" required="true"/>
    </table>
</database>
EOF;
        $table = $this->getDatabaseFromSchema($schema)->getTable('foo');

        return [[[$table->getColumn('bar1'), $table->getColumn('bar2')]]];
    }

    public function providerForTestGetModifyColumnRemoveDefaultValueDDL()
    {
        $t1 = new Table('test');
        $t1->setIdentifierQuoting(true);
        $c1 = new Column('');
        $c1->setName('test');
        $c1->getDomain()->setType('INTEGER');
        $c1->setDefaultValue(0);
        $t1->addColumn($c1);
        $t2 = new Table('test');
        $t2->setIdentifierQuoting(true);
        $c2 = new Column('');
        $c2->setName('test');
        $c2->getDomain()->setType('INTEGER');
        $t2->addColumn($c2);

        return [[ColumnComparator::computeDiff($c1, $c2)]];
    }

    public function providerForTestGetModifyTableForeignKeysSkipSql3DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="test">
        <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="ref_test" type="INTEGER"/>
        <foreign-key foreignTable="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test"/>
        </foreign-key>
    </table>
    <table name="test2">
        <column name="test" type="integer" primaryKey="true"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <table name="test">
    <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="ref_test" type="INTEGER"/>
  </table>
  <table name="test2">
    <column name="test" type="integer" primaryKey="true"/>
  </table>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d1, $d2);

        return [[$diff]];
    }

    public function providerForTestGetModifyTableForeignKeysSkipSql4DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="test">
        <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="ref_test" type="INTEGER"/>
        <foreign-key foreignTable="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test"/>
        </foreign-key>
    </table>
    <table name="test2">
        <column name="test" type="integer" primaryKey="true"/>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <table name="test">
    <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
    <column name="ref_test" type="INTEGER"/>
  </table>
  <table name="test2">
    <column name="test" type="integer" primaryKey="true"/>
  </table>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d2, $d1);

        return [[$diff]];
    }

    protected function buildTableDiff(string $tableName, string $tableColumnsFrom, string $tableColumnsTo): TableDiff
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="$tableName">
        $tableColumnsFrom
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="$tableName">
        $tableColumnsTo
    </table>
</database>
EOF;

        $t1 = $this->getDatabaseFromSchema($schema1)->getTable($tableName);
        $t2 = $this->getDatabaseFromSchema($schema2)->getTable($tableName);
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $tc->compareColumns();

        return $tc->getTableDiff();
    }

    public function providerForTestMigrateToUUIDColumn()
    {
        $tableColumnsFrom = <<<EOF
        <column name="id" primaryKey="true" type="VARCHAR" size="36" autoIncrement="true"/>
EOF;
        $tableColumnsTo = <<<EOF
        <column name="id" primaryKey="true" type="UUID" default="vendor_specific_uuid_generator_function()"/>
EOF;

        return [[$this->buildTableDiff('foo', $tableColumnsFrom, $tableColumnsTo)]];
    }

    public function providerForTestMigrateToUuidBinColumn()
    {
        $tableColumnsFrom = <<<EOF
        <column name="id" primaryKey="true" type="VARCHAR" size="36"/>
EOF;
        $tableColumnsTo = <<<EOF
        <column name="id" primaryKey="true" type="UUID_BINARY" default="vendor_specific_uuid_generator_function()"/>
EOF;

        return [[$this->buildTableDiff('foo', $tableColumnsFrom, $tableColumnsTo)]];
    }

    public function providerForTestMigrateFromUuidBinColumn()
    {
        $tableColumnsFrom = <<<EOF
        <column name="id" primaryKey="true" type="UUID_BINARY" default="vendor_specific_uuid_generator_function()"/>
EOF;
        $tableColumnsTo = <<<EOF
        <column name="id" primaryKey="true" type="VARCHAR" size="36" content="UUID"/>
EOF;

        return [[$this->buildTableDiff('foo', $tableColumnsFrom, $tableColumnsTo)]];
    }
}
