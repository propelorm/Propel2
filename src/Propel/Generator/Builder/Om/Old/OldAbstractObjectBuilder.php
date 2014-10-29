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
 * Base class for object-building classes.
 *
 * This class is designed so that it can be extended the "standard" ObjectBuilder
 * and ComplexOMObjectBuilder.  Hence, this class should not have any actual
 * template code in it -- simply basic logic & utility methods.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class OldAbstractObjectBuilder extends AbstractOMBuilder
{

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     * @param  string  $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @return boolean
     */
    public function hasBehaviorModifier($hookName, $modifier = null)
    {
         return parent::hasBehaviorModifier($hookName, 'ObjectBuilderModifier');
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param string &$script  The script will be modified in this method.
     * @param string $tab
     */
    public function applyBehaviorModifier($hookName, &$script, $tab = "        ")
    {
        $this->applyBehaviorModifierBase($hookName, 'ObjectBuilderModifier', $script, $tab);
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
