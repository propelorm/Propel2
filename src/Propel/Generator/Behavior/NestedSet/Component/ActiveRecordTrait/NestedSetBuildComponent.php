<?php
/**
 * This file is part of the Propel2 package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license GPLv3 License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Class NestedSetBuildComponent
 *
 * @package Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait
 * @author  Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class NestedSetBuildComponent extends BuildComponent
{
    use NamingTrait;

    public function getNestedManagerAssignment()
    {
        $script = "\$manager = \$this->getPropelConfiguration()->getNestedManager(\"{$this->getObjectClassName(true)}\");";

        return $script;
    }
}
