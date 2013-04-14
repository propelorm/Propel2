<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Platform;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MssqlPlatform;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Generates a PHP5 base Object class for user object model (OM) for MongoDB.
 *
 * This class produces the base object class (e.g. BaseMyTable) which contains
 * all the custom-built accessor and setter methods.
 *
 */
class MongoObjectBuilder extends ObjectBuilder
{

    /**
     * get the doInsert() method code
     *
     * @return string the doInsert() method code
     */
    protected function addDoInsert()
    {
        $tableMapClassName = $this->getTableMapClassName();

        $script = "
    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface \$con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface \$con)
    {";
        $script .= "

        \$array = \$this->toArray(TableMap::TYPE_STUDLYPHPNAME);
        unset(\$array['id']);

        \$con->getCollection($tableMapClassName::TABLE_NAME)->insert(\$array);
        \$this->setId(\$array['_id']->{'\$\$id'});

        \$this->setNew(false);
    }
";

        return $script;
    }

    /**
     * get the doUpdate() method code
     *
     * @return string the doUpdate() method code
     */
    protected function addDoUpdate()
    {
        $tableMapClassName = $this->getTableMapClassName();

        return "
    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface \$con
     *
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface \$con)
    {
        \$selectCriteria = \$this->buildPkeyCriteria();

        \$array = \$this->toArray(TableMap::TYPE_STUDLYPHPNAME);
        unset(\$array['id']);
        \$con->getCollection($tableMapClassName::TABLE_NAME)->update(\$selectCriteria, \$array);

        \$this->setNew(false);
    }
";
    }

    /**
     * Adds the function body for the buildPkeyCriteria method
     * @param      string &$script The script will be modified in this method.
     * @see addBuildPkeyCriteria()
     **/
    protected function addBuildPkeyCriteriaBody(&$script)
    {
        $script .= "
        \$criteria = array();
        \$criteria['_id'] = new \\MongoID(\$this->id);
        ";
        /*
        foreach ($this->getTable()->getPrimaryKey() as $col) {
            $clo = $col->getName();
            $name = $col->getStudlyPhpName();
            $script .= "
        \$criteria['$clo'] = \$this->$name;
        ";
        }
        */
    }

}