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
class MakeRootMethod extends BuildComponent
{
    use NamingTrait;
    
    public function process()
    {
        $body = "
\$this->getRepository()->getNestedManager()->makeRoot(\$this);

return \$this;
";

        $this->addMethod('makeRoot')
            ->setDescription('Creates the supplied node as the root node.')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object (for fluent API support)')
            ->setBody($body)
        ;
    }
}
