<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class GetNestedManagerMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = <<<EOF
if (!isset(\$this->nestedManager)) {
    \$this->nestedManager = new \\{$this->getObjectClassName(true)}NestedManager();
}

return \$this->nestedManager;
EOF;

        $this->addMethod('getNestedManager')
            ->setDescription('Return the nested set entity manager object')
            ->setType('\Propel\Runtime\EntityManager\NestedManagerInterface')
            ->setBody($body)
        ;
    }
}
