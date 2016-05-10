<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class DeleteDescendantsMethod extends BuildComponent
{
    use NamingTrait;
    
    public function process()
    {
        $body = "
return \$this->getRepository()->getNestedManager()->deleteDescendants(\$this, \$con);
";
        $this->addMethod('deleteDescendants')
            ->setDescription("Deletes all descendants for the given node.
Instance pooling is wiped out by this command,
so existing {$this->getObjectClassName()} instances are probably invalid (except for the current one)
")
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }
}
