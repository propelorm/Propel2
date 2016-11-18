<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Delegate;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Relation;

/**
 * Gives a model class the ability to delegate methods to a relationship.
 *
 * @author François Zaninotto
 */
class DelegateBehavior extends Behavior
{
    const ONE_TO_ONE = 1;
    const MANY_TO_ONE = 2;

    // default parameters value
    protected $parameters = array(
        'to' => ''
    );

    protected $delegates = array();

    use ComponentTrait;

    /**
     * Lists the delegates and checks that the behavior can use them,
     * And adds a fk from the delegate to the main table if not already set
     */
    public function modifyEntity()
    {
        $table = $this->getEntity();
        $database = $table->getDatabase();
        $delegates = explode(',', $this->parameters['to']);
        foreach ($delegates as $delegate) {
            $delegate = trim($delegate);
            if (!$database->hasEntity($delegate)) {
                throw new \InvalidArgumentException(sprintf(
                    'No delegate table "%s" found for table "%s"',
                    $delegate,
                    $table->getName()
                ));
            }
            if (in_array($delegate, $table->getForeignEntityNames())) {
                // existing many-to-one relationship
                $type = self::MANY_TO_ONE;
            } else {
                // one_to_one relationship
                $delegateEntity = $this->getDelegateEntity($delegate);
                if (in_array($table->getName(), $delegateEntity->getForeignEntityNames())) {
                    // existing one-to-one relationship
                    $fks = $delegateEntity->getForeignKeysReferencingEntity($this->getEntity()->getName());
                    $fk = $fks[0];
                    if (!$fk->isLocalPrimaryKey()) {
                        throw new \InvalidArgumentException(sprintf(
                            'Delegate table "%s" has a relationship with entity "%s", but it\'s a one-to-many relationship. The `delegate` behavior only supports one-to-one relationships in this case.',
                            $delegate,
                            $table->getName()
                        ));
                    }
                } else {
                    // no relationship yet: must be created
                    $this->relateDelegateToMainEntity($this->getDelegateEntity($delegate), $table);
                }
                $type = self::ONE_TO_ONE;
            }
            $this->delegates[$delegate] = $type;
        }
    }

    /**
     * @return array
     */
    public function getDelegates()
    {
        return $this->delegates;
    }

    /**
     * @param Entity $delegateEntity
     * @param Entity $mainEntity
     */
    protected function relateDelegateToMainEntity(Entity $delegateEntity, Entity $mainEntity)
    {
        $pks = $mainEntity->getPrimaryKey();
        foreach ($pks as $field) {
            $mainFieldName = $field->getName();
            if (!$delegateEntity->hasField($mainFieldName)) {
                $field = clone $field;
                $field->setAutoIncrement(false);
                $delegateEntity->addField($field);
            }
        }
        // Add a one-to-one fk
        $relation = new Relation();
        $relation->setForeignEntityName($mainEntity->getName());
        $relation->setDefaultJoin('LEFT JOIN');
        $relation->setOnDelete(Relation::CASCADE);
        $relation->setOnUpdate(Relation::CASCADE);
        foreach ($pks as $field) {
            $relation->addReference($field->getName(), $field->getName());
        }
        $delegateEntity->addRelation($relation);
    }

    /**
     * @param $delegateEntityName
     *
     * @return Entity
     */
    public function getDelegateEntity($delegateEntityName)
    {
        return $this->getEntity()->getDatabase()->getEntity($delegateEntityName);
    }

    /**
     * @param ObjectBuilder $builder
     */
    public function objectBuilderModification(ObjectBuilder $builder)
    {
        $this->applyComponent('MagicCallMethod', $builder);
    }
}
