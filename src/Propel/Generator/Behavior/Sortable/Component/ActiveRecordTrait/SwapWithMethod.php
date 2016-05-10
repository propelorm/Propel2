<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class SwapWithMethod extends BuildComponent
{
    use NamingTrait;
    
    public function process()
    {
        $body = "
\$this->getRepository()->getSortableManager()->swapWith(\$this, \$object, \$con);

return \$this;
";

        $this->addMethod('swapWith')
            ->setType("\$this|{$this->getObjectClassName()}", "The current object for fluid interface")
            ->addSimpleDescParameter('object', $this->getObjectClassName(), 'The object to exchange the rank with')
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setDescription('Exchange the rank of the object with the one passed as argument, and saves both objects')
            ->setBody($body)
        ;
    }
}
