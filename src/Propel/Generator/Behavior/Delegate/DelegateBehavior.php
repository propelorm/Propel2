<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Delegate;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\ForeignKey;

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
            $delegate = $database->getEntityPrefix() . trim($delegate);
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
                            'Delegate table "%s" has a relationship with table "%s", but it\'s a one-to-many relationship. The `delegate` behavior only supports one-to-one relationships in this case.',
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

    protected function relateDelegateToMainEntity($delegateEntity, $mainEntity)
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
        $fk = new ForeignKey();
        $fk->setForeignEntityCommonName($mainEntity->getCommonName());
        $fk->setForeignSchemaName($mainEntity->getSchema());
        $fk->setDefaultJoin('LEFT JOIN');
        $fk->setOnDelete(ForeignKey::CASCADE);
        $fk->setOnUpdate(ForeignKey::NONE);
        foreach ($pks as $field) {
            $fk->addReference($field->getName(), $field->getName());
        }
        $delegateEntity->addForeignKey($fk);
    }

    protected function getDelegateEntity($delegateEntityName)
    {
        return $this->getEntity()->getDatabase()->getEntity($delegateEntityName);
    }

    public function objectCall($builder)
    {
        $plural = false;
        $script = '';
        foreach ($this->delegates as $delegate => $type) {
            $delegateEntity = $this->getDelegateEntity($delegate);
            if ($type == self::ONE_TO_ONE) {
                $fks = $delegateEntity->getForeignKeysReferencingEntity($this->getEntity()->getName());
                $fk = $fks[0];
                $ARClassName = $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($fk->getEntity()));
                $ARFQCN = $builder->getNewStubObjectBuilder($fk->getEntity())->getFullyQualifiedClassName();
                $relationName = $builder->getRefFKPhpNameAffix($fk, $plural);
            } else {
                $fks = $this->getEntity()->getForeignKeysReferencingEntity($delegate);
                $fk = $fks[0];
                $ARClassName = $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($delegateEntity));
                $ARFQCN = $builder->getNewStubObjectBuilder($delegateEntity)->getFullyQualifiedClassName();
                $relationName = $builder->getFKPhpNameAffix($fk);
            }
                $script .= "
if (is_callable(array('$ARFQCN', \$name))) {
    if (!\$delegate = \$this->get$relationName()) {
        \$delegate = new $ARClassName();
        \$this->set$relationName(\$delegate);
    }

    return call_user_func_array(array(\$delegate, \$name), \$params);
}";
        }

        return $script;
    }
}
