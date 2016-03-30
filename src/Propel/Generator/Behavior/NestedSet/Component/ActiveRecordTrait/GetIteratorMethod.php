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

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class GetIteratorMethod extends BuildComponent
{
    public function process()
    {
        $this->addMethod('getIterator')
            ->setDescription('Returns a pre-order iterator for this node and its children.')
            ->setType('NestedSetRecursiveIterator')
            ->setBody("return new NestedSetRecursiveIterator(\$this);")
        ;
    }
}
