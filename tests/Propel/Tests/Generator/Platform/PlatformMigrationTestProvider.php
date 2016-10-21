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
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="blooopoo" type="INTEGER" />
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
    <entity name="foo3">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="yipee" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="LONGVARCHAR" />
    </entity>
    <entity name="foo4">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="yipee" type="INTEGER" />
    </entity>
    <entity name="foo5">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="lkdjfsh" type="INTEGER" />
        <field name="dfgdsgf" type="LONGVARCHAR" />
    </entity>
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
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
        </foreign-key>
        <foreign-key name="foo1_fk_2" foreignEntity="foo2">
            <reference local="baz" foreign="baz" />
        </foreign-key>
        <index name="bar_fk">
            <index-field name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-field name="bar"/>
            <index-field name="baz"/>
        </index>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="LONGVARCHAR" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar1" foreign="bar" />
        </foreign-key>
        <index name="bar_fk">
            <index-field name="bar1"/>
        </index>
        <index name="baz_fk">
            <index-field name="baz3"/>
        </index>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo');

        return array(array(EntityComparator::computeDiff($t1,$t2)));
    }

    public function providerForTestGetModifyEntityFieldsDDL()
    {
        $schema1 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
        <field name="baz3" type="LONGVARCHAR" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo');
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
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" />
        <field name="bar" type="INTEGER" primaryKey="true" />
        <field name="baz" type="VARCHAR" size="12" required="false" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo');
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
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <index name="bar_fk">
            <index-field name="bar"/>
        </index>
        <index name="bar_baz_fk">
            <index-field name="bar"/>
            <index-field name="baz"/>
        </index>
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <index name="bar_baz_fk">
            <index-field name="id"/>
            <index-field name="bar"/>
            <index-field name="baz"/>
        </index>
        <index name="baz_fk">
            <index-field name="baz"/>
        </index>
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo');
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
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
        </foreign-key>
        <foreign-key name="foo1_fk_2" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
            <reference local="baz" foreign="baz" />
        </foreign-key>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
        <foreign-key name="foo1_fk_2" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
            <reference local="id" foreign="id" />
        </foreign-key>
        <foreign-key name="foo1_fk_3" foreignEntity="foo2">
            <reference local="baz" foreign="baz" />
        </foreign-key>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <field name="baz" type="VARCHAR" size="12" required="true" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo1');
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
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2">
            <reference local="bar" foreign="bar" />
        </foreign-key>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2" skipSql="true">
            <reference local="bar" foreign="bar" />
        </foreign-key>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo1');
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
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
        <foreign-key name="foo1_fk_1" foreignEntity="foo2" skipSql="true">
            <reference local="bar" foreign="bar" />
        </foreign-key>
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo1">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
    <entity name="foo2">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $t1 = $this->getDatabaseFromSchema($schema1)->getEntity('Foo1');
        $t2 = $this->getDatabaseFromSchema($schema2)->getEntity('Foo1');
        $tc = new EntityComparator();
        $tc->setFromEntity($t1);
        $tc->setToEntity($t2);
        $tc->compareRelations();

        return array(array($tc->getEntityDiff()));
    }

    public function providerForTestGetRemoveFieldDDL()
    {
        $entity = new Entity('Foo');
        $entity->setIdentifierQuoting(true);
        $field = new Field('bar');
        $entity->addField($field);

        return array(array($field));
    }

    public function providerForTestGetRenameFieldDDL()
    {
        $t1 = new Entity('Foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar1');
        $c1->getDomain()->setType('DOUBLE');
        $c1->getDomain()->setSqlType('DOUBLE');
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);

        $t2 = new Entity('Foo');
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
        $t1 = new Entity('Foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);
        $t2 = new Entity('Foo');
        $t2->setIdentifierQuoting(true);
        $c2 = new Field('bar');
        $c2->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceSize(3);
        $t2->addField($c2);

        return array(array(FieldComparator::computeDiff($c1, $c2)));
    }

    public function providerForTestGetModifyFieldsDDL()
    {
        $t1 = new Entity('Foo');
        $t1->setIdentifierQuoting(true);
        $c1 = new Field('bar1');
        $c1->getDomain()->copy($this->getPlatform()->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceSize(2);
        $t1->addField($c1);
        $c2 = new Field('bar2');
        $c2->getDomain()->setType('INTEGER');
        $c2->getDomain()->setSqlType('INTEGER');
        $t1->addField($c2);

        $t2 = new Entity('Foo');
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
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar" type="INTEGER" />
    </entity>
</database>
EOF;
        $field = $this->getDatabaseFromSchema($schema)->getEntity('Foo')->getField('bar');

        return array(array($field));
    }

    public function providerForTestGetAddFieldsDDL()
    {
        $schema = <<<EOF
<database name="test" identifierQuoting="true">
    <entity name="foo">
        <field name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
        <field name="bar1" type="INTEGER" />
        <field name="bar2" type="DOUBLE" scale="2" size="3" default="-1" required="true" />
    </entity>
</database>
EOF;
        $entity = $this->getDatabaseFromSchema($schema)->getEntity('Foo');

        return array(array(array($entity->getField('bar1'), $entity->getField('bar2'))));
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
    <entity name="test">
        <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
        <field name="ref_test" type="INTEGER"/>
        <foreign-key foreignEntity="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test" />
        </foreign-key>
    </entity>
    <entity name="test2">
        <field name="test" type="integer" primaryKey="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <entity name="test">
    <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <field name="ref_test" type="INTEGER"/>
  </entity>
  <entity name="test2">
    <field name="test" type="integer" primaryKey="true" />
  </entity>
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
    <entity name="test">
        <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
        <field name="ref_test" type="INTEGER"/>
        <foreign-key foreignEntity="test2" onDelete="CASCADE" onUpdate="CASCADE" skipSql="true">
            <reference local="ref_test" foreign="test" />
        </foreign-key>
    </entity>
    <entity name="test2">
        <field name="test" type="integer" primaryKey="true" />
    </entity>
</database>
EOF;
        $schema2 = <<<EOF
<database name="test" identifierQuoting="true">
  <entity name="test">
    <field name="test" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <field name="ref_test" type="INTEGER"/>
  </entity>
  <entity name="test2">
    <field name="test" type="integer" primaryKey="true" />
  </entity>
</database>
EOF;
        $d1 = $this->getDatabaseFromSchema($schema1);
        $d2 = $this->getDatabaseFromSchema($schema2);
        $diff = DatabaseComparator::computeDiff($d2, $d1);

        return array(array($diff));
    }

}
