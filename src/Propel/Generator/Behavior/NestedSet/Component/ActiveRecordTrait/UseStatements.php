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
 * Comodo component to centralize all `use ....` statements for ActiveRecordTrait.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class UseStatements extends BuildComponent
{
    public function process()
    {
        $definition = $this->getDefinition();
        $definition
            ->addUseStatement('Propel\Runtime\Connection\ConnectionInterface')
            ->addUseStatement('Propel\Runtime\ActiveQuery\Criteria')
            ->addUseStatement('Propel\Runtime\ActiveRecord\NestedSetRecursiveIterator')
        ;
    }
}
