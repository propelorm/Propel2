<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Platform;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\Diff\FieldComparator;
use Propel\Generator\Model\Diff\EntityComparator;

/**
 * provider for platform migration unit tests
 */
abstract class PlatformMigrationTestProvider extends PlatformTestBase
{

    public function providerForTestGetModifyDatabaseDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="blooopoo" type="INTEGER" />
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
    <table name="foo3">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="yipee" type="INTEGER" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="false" />
        <column name="baz3" type="LONGVARCHAR" />
    </table>
    <table name="foo4">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="yipee" type="INTEGER" />
    </table>
    <table name="foo5">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="lkdjfsh" type="INTEGER" />
        <column name="dfgdsgf" type="LONGVARCHAR" />
    </table>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);

        return array(array(DatabaseComparator::computeDiff($d1, $d2, $caseInsensitive = false, $withRenaming = true)));
    }

    public function providerForTestGetRenameEntityDDL()
    {
        return array(array('foo1', 'foo2'));
    }

    public function providerForTestGetModifyEntityDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
        </foreign-key>
        <foreign-key name="foo1_fk_2" foreignEntity="foo2">
            <reference local="baz" foreign="baz" />
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
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="false" />
        <column name="baz3" type="LONGVARCHAR" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar1" foreign="bar" />
        </foreign-key>
        <index name="bar_fk">
            <index-column name="bar1"/>
        </index>
        <index name="baz_fk">
            <index-column name="baz3"/>
        </index>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo');

        return array(array(EntityComparator::computeDiff($t1,$t2)));
    }

    public function providerForTestGetModifyEntityFieldsDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="false" />
        <column name="baz3" type="LONGVARCHAR" />
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareFields();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetModifyEntityPrimaryKeysDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" />
        <column name="bar" type="INTEGER" primaryKey="true" />
        <column name="baz" type="VARCHAR" size="12" required="false" />
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->comparePrimaryKeys();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetModifyEntityIndicesDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
        <index name="bar_fk">
            <index-column name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-column name="bar"/>
            <index-column name="baz"/>
        </index>
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
        <index name="bar_baz_fk">
            <index-column name="id"/>
            <index-column name="bar"/>
            <index-column name="baz"/>
        </index>
        <index name="baz_fk">
            <index-column name="baz"/>
        </index>
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareIndices();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetModifyEntityRelationsDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
        </foreign-key>
        <foreign-key name="foo1_fk_2" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
            <reference local="baz" foreign="baz" />
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
        <foreign-key name="foo1_fk_2" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
            <reference local="id" foreign="id" />
        </foreign-key>
        <foreign-key name="foo1_fk_3" foreignEntity="foo2">
            <reference local="baz" foreign="baz" />
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <column name="baz" type="VARCHAR" size="12" required="true" />
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetModifyEntityRelationsSkipSqlDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2" skipSql="true">
            <reference local="bar" foreign="bar" />
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetModifyEntityRelationsSkipSql2DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2" skipSql="true">
            <reference local="bar" foreign="bar" />
        </foreign-key>
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo1">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
    <table name="foo2">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetRemoveFieldDDL()
    {
        $table = new Entity('foo');
        $table->setIdentifierQuoting(true);
        $column = new Field('bar');
        $table->addField($column);

        return array(array($column));
    }

    public function providerForTestGetRenameFieldDDL()
    {
        $t1 = new Entity('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar1');
        $c1->getDomain()->setType('DOUBLE');
        $c1->getDomain()->setSqlType('DOUBLE');
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);

        $t2 = new Entity('foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field('bar2');
        $c2->getDomain()->setType('DOUBLE');
        $c2->getDomain()->setSqlType('DOUBLE');
        $c2->getDomain()->replaceSize(2);
        $t2->addField($c2);

        return array(array($c1, $c2));
    }

    public function providerForTestGetModifyFieldDDL()
    {
        $t1 = new Entity('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);
        $t2 = new Entity('foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field('bar');
        $c2->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceSize(3);
        $t2->addField($c2);

        return array(array(FieldComparator::computeDiff($c1, $c2)));
    }

    public function providerForTestGetModifyFieldsDDL()
    {
        $t1 = new Entity('foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar1');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);
        $c2 = new Field('bar2');
        $c2->getDomain()->setType('INTEGER');
        $c2->getDomain()->setSqlType('INTEGER');
        $t1->addField($c2);

        $t2 = new Entity('foo');
        $t2->setIdentifierQuoting(true);
        $t2->setIdentifierQuoting(true);
        $c3 = new Field('bar1');
        $c3->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c3->getDomain()->replaceSize(3);
        $t2->addField($c3);
        $c4 = new Field('bar2');
        $c4->getDomain()->setType('INTEGER');
        $c4->getDomain()->setSqlType('INTEGER');
        $c4->setNotNull(true);
        $t2->addField($c4);

        return array(array(array(
            FieldComparator::computeDiff($c1, $c3),
            FieldComparator::computeDiff($c2, $c4)
        )));
    }

    public function providerForTestGetAddFieldDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar" type="INTEGER" />
    </table>
</database>
EOF;
        $column = $this->getDatabaseFromSchema($schema)->getEntity('foo')->getField('bar');

        return array(array($column));
    }

    public function providerForTestGetAddFieldsDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="foo">
        <column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <column name="bar1" type="INTEGER" />
        <column name="bar2" type="DOUBLE" scale="2" size="3" default="-1" required="true" />
    </table>
</database>
EOF;
        $table = $this->getDatabaseFromSchema($schema)->getEntity('foo');

        return array(array(array($table->getField('bar1'), $table->getField('bar2'))));
    }

    public function providerForTestGetModifyFieldRemoveDefaultValueDDL()
    {
        $t1 = new Entity('test');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field();
        $c1->setName('test');
        $c1->getDomain()->setType('INTEGER');
        $c1->setDefaultValue(0);
        $t1->addField($c1);
        $t2 = new Entity('test');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field();
        $c2->setName('test');
        $c2->getDomain()->setType('INTEGER');
        $t2->addField($c2);

        return array(array(FieldComparator::computeDiff($c1, $c2)));
    }

    public function providerForTestGetModifyEntityRelationsSkipSql3DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="test">
        <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
        <column name="ref_test" type="INTEGER"/>
        <foreign-key foreignEntity="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test" />
        </foreign-key>
    </table>
    <table name="test2">
        <column name="test" type="integer" primaryKey="true" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <table name="test">
    <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <column name="ref_test" type="INTEGER"/>
  </table>
  <table name="test2">
    <column name="test" type="integer" primaryKey="true" />
  </table>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d1, $d2);

        return array(array($diff));
    }

    public function providerForTestGetModifyEntityRelationsSkipSql4DDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <table name="test">
        <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
        <column name="ref_test" type="INTEGER"/>
        <foreign-key foreignEntity="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test" />
        </foreign-key>
    </table>
    <table name="test2">
        <column name="test" type="integer" primaryKey="true" />
    </table>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <table name="test">
    <column name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <column name="ref_test" type="INTEGER"/>
  </table>
  <table name="test2">
    <column name="test" type="integer" primaryKey="true" />
  </table>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d2, $d1);

        return array(array($diff));
    }

}
