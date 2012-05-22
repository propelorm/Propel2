<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Model\PropelTypes;

/**
 * Base class for Peer-building classes.
 *
 * This class is designed so that it can be extended the "standard" PeerBuilder
 * and ComplexOMPeerBuilder.  Hence, this class should not have any actual
 * template code in it -- simply basic logic & utility methods.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class AbstractObjectBuilder extends AbstractOMBuilder
{
    /**
     * This method adds the contents of the generated class to the script.
     *
     * This method is abstract and should be overridden by the subclasses.
     *
     * Hint: Override this method in your subclass if you want to reorganize or
     * drastically change the contents of the generated peer class.
     *
     * @param      string &$script The script will be modified in this method.
     */
    abstract protected function addClassBody(&$script);

    /**
     * Adds the getter methods for the column values.
     * This is here because it is probably generic enough to apply to templates being generated
     * in different langauges (e.g. PHP4 and PHP5).
     * @param      string &$script The script will be modified in this method.
     */
    protected function addColumnAccessorMethods(&$script)
    {
        $table = $this->getTable();

        foreach ($table->getColumns() as $col) {

            // if they're not using the DateTime class than we will generate "compatibility" accessor method
            if (PropelTypes::DATE === $col->getType()
                || PropelTypes::TIME === $col->getType()
                || PropelTypes::TIMESTAMP === $col->getType()
            ) {
                $this->addTemporalAccessor($script, $col);
            } elseif (PropelTypes::OBJECT === $col->getType()) {
                $this->addObjectAccessor($script, $col);
            } elseif (PropelTypes::PHP_ARRAY === $col->getType()) {
                $this->addArrayAccessor($script, $col);
                if ($col->isNamePlural()) {
                    $this->addHasArrayElement($script, $col);
                }
            } elseif ($col->isEnumType()) {
                $this->addEnumAccessor($script, $col);
            } else {
                $this->addDefaultAccessor($script, $col);
            }

            if ($col->isLazyLoad()) {
                $this->addLazyLoader($script, $col);
            }
        }
    }

    /**
     * Adds the mutator (setter) methods for setting column values.
     * This is here because it is probably generic enough to apply to templates being generated
     * in different langauges (e.g. PHP4 and PHP5).
     * @param      string &$script The script will be modified in this method.
     */
    protected function addColumnMutatorMethods(&$script)
    {
        foreach ($this->getTable()->getColumns() as $col) {
            if ($col->isLobType()) {
                $this->addLobMutator($script, $col);
            } elseif (
                PropelTypes::DATE === $col->getType()
                || PropelTypes::TIME === $col->getType()
                || PropelTypes::TIMESTAMP === $col->getType()
            ) {
                $this->addTemporalMutator($script, $col);
            } elseif (PropelTypes::OBJECT === $col->getType()) {
                $this->addObjectMutator($script, $col);
            } elseif (PropelTypes::PHP_ARRAY === $col->getType()) {
                $this->addArrayMutator($script, $col);
                if ($col->isNamePlural()) {
                    $this->addAddArrayElement($script, $col);
                    $this->addRemoveArrayElement($script, $col);
                }
            } elseif ($col->isEnumType()) {
                $this->addEnumMutator($script, $col);
            } elseif ($col->isBooleanType()) {
                $this->addBooleanMutator($script, $col);
            } else {
                $this->addDefaultMutator($script, $col);
            }
        }
    }


    /**
     * Gets the baseClass path if specified for table/db.
     * If not, will return 'propel.om.BaseObject'
     * @return     string
     */
    protected function getBaseClass()
    {
        $class = $this->getTable()->getBaseClass();
        if (null === $class) {
            $class = 'propel.om.BaseObject';
        }

        return $class;
    }

    /**
     * Gets the interface path if specified for current table.
     * If not, will return 'propel.om.Persistent'.
     * @return     string
     */
    protected function getInterface()
    {
        $interface = $this->getTable()->getInterface();
        if (null === $interface && !$this->getTable()->isReadOnly()) {
            $interface = 'propel.om.Persistent';
        }

        return $interface;
    }

    /**
     * Whether to add the generic mutator methods (setByName(), setByPosition(), fromArray()).
     * This is based on the build property propel.addGenericMutators, and also whether the
     * table is read-only or an alias.
     */
    protected function isAddGenericMutators()
    {
        $table = $this->getTable();

        return (!$table->isAlias() && $this->getBuildProperty('addGenericMutators') && !$table->isReadOnly());
    }

    /**
     * Whether to add the generic accessor methods (getByName(), getByPosition(), toArray()).
     * This is based on the build property propel.addGenericAccessors, and also whether the
     * table is an alias.
     */
    protected function isAddGenericAccessors()
    {
        $table = $this->getTable();

        return (!$table->isAlias() && $this->getBuildProperty('addGenericAccessors'));
    }

    protected function hasDefaultValues()
    {
        foreach ($this->getTable()->getColumns() as $col) {
            if (null !== $col->getDefaultValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @return Boolean
     */
    public function hasBehaviorModifier($hookName, $modifier = null)
    {
         return parent::hasBehaviorModifier($hookName, 'ObjectBuilderModifier');
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param string &$script The script will be modified in this method.
     */
    public function applyBehaviorModifier($hookName, &$script, $tab = "        ")
    {
        return $this->applyBehaviorModifierBase($hookName, 'ObjectBuilderModifier', $script, $tab);
    }

    /**
     * Checks whether any registered behavior content creator on that table exists a contentName
     * @param string $contentName The name of the content as called from one of this class methods, e.g. "parentClassName"
     */
    public function getBehaviorContent($contentName)
    {
        return $this->getBehaviorContentBase($contentName, 'ObjectBuilderModifier');
    }
}
