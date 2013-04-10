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
class MongoQuery extends QueryBuilder
{

    protected function addFindPk(&$script)
    {

        $table = $this->getTable();
        $platform = $this->getPlatform();
        $peerClassName = $this->getPeerClassName();
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

    public function findPk(\$key, \$con = null)
    {
        \$row = \$con->getCollection($tableMapClassName::TABLE_NAME)->find(array($find));

";

        if ($table->getChildrenColumn()) {
            $script .="
            \$cls = {$peerClassName}::getOMClass(\$row, 0, false);
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