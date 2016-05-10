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
class MoveToTopMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$this->getRepository()->getSortableManager()->moveToTop(\$this, \$con);

return \$this;
";

        $this->addMethod('moveToTop')
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setDescription('Move the object to the top of the list')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object for fluid interface')
            ->setBody($body)
        ;
    }
}
