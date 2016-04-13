<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sluggable\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;

class GetSlugMethod extends BuildComponent
{
    public function process()
    {
        $methodName = 'get' . ucfirst($this->getBehavior()->getFieldForParameter('slug_field')->getName());
        $this->addMethod('getSlug')
            ->setDescription("Standard getter method for the slug. Alias of `$methodName`")
            ->setType('string')
            ->setBody("return \$this->$methodName();")
        ;
    }
}