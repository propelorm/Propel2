<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\CustomObjectCollection;

use Propel\Generator\Model\Behavior;

/**
 * Sets the default formatter to an object collection unique to the class
 *
 * @author Lee Leathers
 */
class CustomObjectCollectionBehavior extends Behavior
{
    protected $additionalBuilders = array('\Propel\Generator\Behavior\CustomObjectCollection\AddObjectCollectionBuilder');

    /**
    * @return string the PHP code to be added to the builder
    */
    public function preSelectQuery($builder)
    {
        $className = '\\ObjectCollection\\' . $this->getTable()->getPhpName(). 'ObjectCollection';

        if ($this->getTable()->getNamespace()) {
            $className = '\\' . $this->getTable()->getNamespace() . $className;
        }

        return sprintf('$formatter = new \Propel\Runtime\Formatter\ObjectFormatter();
$formatter->setCollectionClassName("%s");

$this->setFormatter($formatter);', $className);
    }

}
