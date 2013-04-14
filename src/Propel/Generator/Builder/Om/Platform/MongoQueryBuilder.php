<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Platform;

use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\PropelTypes;

/**
 * Generates a PHP5 base Query class for user object model (OM).
 *
 * This class produces the base query class (e.g. BaseBookQuery) which contains
 * all the custom-built query methods.
 *
 * @author Francois Zaninotto
 */
class MongoQueryBuilder extends QueryBuilder
{

    public function getParentClass()
    {
        return 'DocumentModelCriteria';
    }

    protected function addClassBody(&$script)
    {
        // namespaces
        $this->declareClasses(
            '\Propel\Runtime\Propel',
            '\Propel\Runtime\ActiveQuery\ModelCriteria',
            '\Propel\Runtime\ActiveQuery\DocumentModelCriteria',
            '\Propel\Runtime\ActiveQuery\Criteria',
            '\Propel\Runtime\ActiveQuery\ModelJoin',
            '\Exception',
            '\Propel\Runtime\Exception\PropelException'
        );
        $this->declareClassFromBuilder($this->getStubQueryBuilder(), 'Child');
        $this->declareClassFromBuilder($this->getTableMapBuilder());

        // apply behaviors
        $this->applyBehaviorModifier('queryAttributes', $script, "    ");
        $this->addConstructor($script);
        $this->addFactory($script);
        $this->addFindPk($script);
        $this->addFindPkSimple($script);
        $this->addFindPkComplex($script);
        $this->addFindPks($script);
        $this->addFilterByPrimaryKey($script);
        $this->addFilterByPrimaryKeys($script);
        foreach ($this->getTable()->getColumns() as $col) {
            $this->addFilterByCol($script, $col);
            if ($col->getType() === PropelTypes::PHP_ARRAY && $col->isNamePlural()) {
                $this->addFilterByArrayCol($script, $col);
            }
        }
        foreach ($this->getTable()->getForeignKeys() as $fk) {
            $this->addFilterByFK($script, $fk);
            $this->addJoinFk($script, $fk);
            $this->addUseFKQuery($script, $fk);
        }
        foreach ($this->getTable()->getReferrers() as $refFK) {
            $this->addFilterByRefFK($script, $refFK);
            $this->addJoinRefFk($script, $refFK);
            $this->addUseRefFKQuery($script, $refFK);
        }
        foreach ($this->getTable()->getCrossFks() as $fkList) {
            list($refFK, $crossFK) = $fkList;
            $this->addFilterByCrossFK($script, $refFK, $crossFK);
        }
        $this->addPrune($script);
        $this->addBasePreSelect($script);
        $this->addBasePreDelete($script);
        $this->addBasePostDelete($script);
        $this->addBasePreUpdate($script);
        $this->addBasePostUpdate($script);
        // apply behaviors
        $this->applyBehaviorModifier('queryMethods', $script, "    ");
    }

    protected function addFindPkSimple(&$script)
    {

        $table = $this->getTable();
        $tableMapClassName = $this->getTableMapClassName();
        $ARClassName = $this->getObjectClassName();
        $this->declareClassFromBuilder($this->getStubObjectBuilder());

        $find = array();
        $pks = $table->getPrimaryKey();
        if ($table->hasCompositePrimaryKey()) {
            foreach ($pks as $index => $column) {
                $find []= sprintf("'%s' => %s", $column->getName(), "\$key[$index]");
            }
        } else {
            $find []= sprintf("'%s' => %s", $pks[0]->getName(), "\$key");
        }
        $find = implode(', ', $find);

        $pks = array();
        if ($table->hasCompositePrimaryKey()) {
            foreach ($table->getPrimaryKey() as $index => $column) {
                $pks []= "\$key[$index]";
            }
        } else {
            $pks []= "\$key";
        }
        $pkHashFromRow = $this->getTableMapBuilder()->getInstancePoolKeySnippet($pks);

        $script .= "

    public function findPkSimple(\$key, \$con = null)
    {

        if (\$con === null) {
            \$con = Propel::getServiceContainer()->getReadConnection({$this->getTableMapClass()}::DATABASE_NAME);
        }

        \$row = \$con->getCollection($tableMapClassName::TABLE_NAME)->findOne(array($find));

";

        if ($table->getChildrenColumn()) {
            $script .="
            \$cls = {$tableMapClassName}::getOMClass(\$row, 0, false);
            \$obj = new \$cls();";
        } else {
            $script .="
            \$obj = new $ARClassName();";
        }
        $script .= "
            \$obj->hydrate(\$row);
            {$tableMapClassName}::addInstanceToPool(\$obj, $pkHashFromRow);

        return \$obj;
    }
";
    }
}
