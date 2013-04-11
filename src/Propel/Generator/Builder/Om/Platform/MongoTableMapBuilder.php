<?php

namespace Propel\Generator\Builder\Om\Platform;

use \Propel\Generator\Builder\Om\TableMapBuilder;

class MongoTableMapBuilder extends TableMapBuilder
{

    /**
     * Adds the populateObject() method.
     * @param string &$script The script will be modified in this method.
     */
    protected function addPo22ulateObject(&$script)
    {
        $table = $this->getTable();
        $script .= "
    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array \$row name indexed row.
     * @param int   \$startcol The 0-based offset for reading from the resultset row.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     * @return array (" . $this->getObjectClassName(). " object, last column rank)
     */
    public static function populateObject(\$row)
    {
        \$key = {$this->getTableMapClassName()}::getPrimaryKeyHashFromRow(\$row, \$startcol);
        if (null !== (\$obj = {$this->getTableMapClassName()}::getInstanceFromPool(\$key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // \$obj->hydrate(\$row, \$startcol, true); // rehydrate
            \$col = \$startcol + " . $this->getTableMapClass() . "::NUM_HYDRATE_COLUMNS;";
        if ($table->isAbstract()) {
            $script .= "
        } elseif (null == \$key) {
            // empty resultset, probably from a left join
            // since this table is abstract, we can't hydrate an empty object
            \$obj = null;
            \$col = \$startcol + " . $this->getTableMapClass() . "::NUM_HYDRATE_COLUMNS;";
        }
        $script .= "
        } else {";
        if (!$table->getChildrenColumn()) {
            $script .= "
            \$cls = " . $this->getTableMapClass() . "::OM_CLASS;";
        } else {
            $script .= "
            \$cls = static::getOMClass(\$row, \$startcol, false);";
        }
        $script .= "
            \$obj = new \$cls();
            \$col = \$obj->hydrate(\$row, \$startcol);
            {$this->getTableMapClassName()}::addInstanceToPool(\$obj, \$key);
        }

        return array(\$obj, \$col);
    }
";
    }
}