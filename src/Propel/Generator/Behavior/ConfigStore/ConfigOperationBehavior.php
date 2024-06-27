<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\ConfigStore;

use Propel\Generator\Model\Behavior;

abstract class ConfigOperationBehavior extends Behavior
{
    /**
     * Indicates whether the behavior can be applied several times on the same
     * table or not.
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    abstract protected function getKey(): string;

     /**
      * @return void
      */
    public function modifyDatabase(): void
    {
        $this->apply($this->database);
    }

    /**
     * @return void
     */
    public function modifyTable(): void
    {
        $this->apply($this->table);
    }

    /**
     * @param \Propel\Generator\Model\Database|\Propel\Generator\Model\Table $behaviorable
     *
     * @return void
     */
    abstract protected function apply($behaviorable): void;
}
