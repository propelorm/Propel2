<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\QueryCache\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

class DoCountMethod extends BuildComponent
{
    use SimpleTemplateTrait;

    public function process()
    {
        $this->addMethod('doCount')->setBody($this->renderTemplate());
    }
}
