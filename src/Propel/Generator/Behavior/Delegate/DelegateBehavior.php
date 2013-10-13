<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Delegate;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
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
    public function modifyTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $delegates = explode(',', $this->parameters['to']);
        foreach ($delegates as $delegate) {
            $delegate = $database->getTablePrefix() . trim($delegate);
            if (!$database->hasTable($delegate)) {
                throw new \InvalidArgumentException(sprintf(
                    'No delegate table "%s" found for table "%s"',
                    $delegate,
                    $table->getName()
                ));
            }
            if (in_array($delegate, $table->getForeignTableNames())) {
                // existing many-to-one relationship
                $type = self::MANY_TO_ONE;
            } else {
                // one_to_one relationship
                $delegateTable = $this->getDelegateTable($delegate);
                if (in_array($table->getName(), $delegateTable->getForeignTableNames())) {
                    // existing one-to-one relationship
                    $fks = $delegateTable->getForeignKeysReferencingTable($this->getTable()->getName());
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
                    $this->relateDelegateToMainTable($this->getDelegateTable($delegate), $table);
                }
                $type = self::ONE_TO_ONE;
            }
            $this->delegates[$delegate] = $type;
        }
    }

    protected function relateDelegateToMainTable($delegateTable, $mainTable)
    {
        $pks = $mainTable->getPrimaryKey();
        foreach ($pks as $column) {
            $mainColumnName = $column->getName();
            if (!$delegateTable->hasColumn($mainColumnName)) {
                $column = clone $column;
                $column->setAutoIncrement(false);
                $delegateTable->addColumn($column);
            }
        }
        // Add a one-to-one fk
        $fk = new ForeignKey();
        $fk->setForeignTableCommonName($mainTable->getCommonName());
        $fk->setForeignSchemaName($mainTable->getSchema());
        $fk->setDefaultJoin('LEFT JOIN');
        $fk->setOnDelete(ForeignKey::CASCADE);
        $fk->setOnUpdate(ForeignKey::NONE);
        foreach ($pks as $column) {
            $fk->addReference($column->getName(), $column->getName());
        }
        $delegateTable->addForeignKey($fk);
    }

    protected function getDelegateTable($delegateTableName)
    {
        return $this->getTable()->getDatabase()->getTable($delegateTableName);
    }

    public function getDelegates()
    {
        return $this->delegates;
    }

    public function isOneToOne($type)
    {
        return $type === static::ONE_TO_ONE;
    }

    public function getDelegateForeignKey($type, $delegate)
    {
        if($type === static::ONE_TO_ONE) {
            return $this->getDelegateTable($delegate)->getForeignKeysReferencingTable($this->getTable()->getName())[0];
        } else {
            return $this->getTable()->getForeignKeysReferencingTable($delegate)[0];
        }
    }

    public function getDelegateObjectBuilder(AbstractOMBuilder $builder, $type, $delegate)
    {
        if($type === static::ONE_TO_ONE) {
            return $builder->getNewStubObjectBuilder($this->getDelegateForeignKey($type, $delegate)->getTable());
        } else {
            return $builder->getNewStubObjectBuilder($this->getDelegateTable($delegate));
        }
    }

    public function getDelegateMethodName(AbstractOMBuilder $builder, $type, $delegate)
    {
        if($type === static::ONE_TO_ONE) {
            return $builder->getRefFKPhpNameAffix($this->getDelegateForeignKey($type, $delegate));
        } else {
            return $builder->getFKPhpNameAffix($this->getDelegateForeignKey($type, $delegate));
        }
    }
}
