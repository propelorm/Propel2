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
use Propel\Generator\Builder\Om\Component\NamingTrait;

class SetSlugMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $methodName = 'set' . ucfirst($this->getBehavior()->getFieldForParameter('slug_field')->getName());
        $this->addMethod('setSlug')
            ->setDescription("Standard setter method for the slug. Alias of `$methodName`")
            ->setType("\$this|{$this->getObjectClassName()}")
            ->addSimpleDescParameter('slug', 'string', 'The value of the slug',null)
            ->setBody("return \$this->$methodName(\$slug);")
        ;
    }
}