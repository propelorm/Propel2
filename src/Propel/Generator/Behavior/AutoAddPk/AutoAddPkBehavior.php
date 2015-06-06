<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AutoAddPk;

use Propel\Generator\Model\Behavior;

/**
 * Adds a primary key to models defined without one
 *
 * @author François Zaninotto
 */
class AutoAddPkBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'name'          => 'id',
        'autoIncrement' => 'true',
        'type'          => 'INTEGER'
    );

    /**
     * Copy the behavior to the database entities
     * Only for entities that have no Pk
     */
    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if (!$entity->hasPrimaryKey()) {
                $b = clone $this;
                $entity->addBehavior($b);
            }
        }
    }

    /**
     * Add the primary key to the current entity
     */
    public function modifyEntity()
    {
        $entity = $this->getEntity();
        if (!$entity->hasPrimaryKey() && !$entity->hasBehavior('concrete_inheritance')) {
            $fieldAttributes = array_merge(array('primaryKey' => 'true'), $this->getParameters());
            $this->getEntity()->addField($fieldAttributes);
        }
    }
}
