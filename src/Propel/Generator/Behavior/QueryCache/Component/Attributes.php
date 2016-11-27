<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache\Component;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;

class Attributes extends BuildComponent
{
    public function process()
    {
        $this->addProperty('queryKey', '');

        $cacheBackend = $this->addProperty('cacheBackend')->setStatic(true);

        if ('backend' == $this->getBehavior()->getParameter('backend')) {
            $cacheBackend->setValue(PhpConstant::create('[]'));
        }
    }

}