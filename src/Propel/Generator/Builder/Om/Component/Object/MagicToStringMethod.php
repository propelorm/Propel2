<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\NamingTool;

/**
 * Adds __toString method if a string column was defined as primary string
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class MagicToStringMethod extends BuildComponent
{
    public function process()
    {
        $body = '';

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isPrimaryString()) {
                $method = 'get' . NamingTool::toUpperCamelCase($field->getName());
                $body = "return (string) \$this->$method();";
            }
        }

        if ('' === $body) {
            //no primary string
            $body = "return (string) serialize(\$this);";
        }

        $this->addMethod('__toString')
            ->setDescription('Return the string representation of this object')
            ->setType('string')
            ->setBody($body);
    }
}
