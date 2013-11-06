<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\ConcreteInheritance;

use Propel\Generator\Model\Behavior;

/**
 * Symmetrical behavior of the concrete_inheritance. When model A extends model B,
 * model A gets the concrete_inheritance behavior, and model B gets the
 * concrete_inheritance_parent
 *
 * @author François Zaninotto
 */
class ConcreteInheritanceParentBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'descendant_column' => 'descendant_class'
    );

    public function modifyTable()
    {
        $table = $this->getTable();
        if (!$table->hasColumn($this->getParameter('descendant_column'))) {
            $table->addColumn(array(
                'name' => $this->getParameter('descendant_column'),
                'type' => 'VARCHAR',
                'size' => 100
            ));
        }
    }

    public function getColumnGetter()
    {
        return 'get' . $this->getColumnForParameter('descendant_column')->getPhpName();
    }

    public function getTemplateDirectory()
    {
        $directory = parent::getTemplateDirectory();

        if($directory !== null) {
            return $directory . '_parent';
        }

        return $directory;
    }
}
