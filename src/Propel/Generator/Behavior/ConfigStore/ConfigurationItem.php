<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\ConfigStore;

class ConfigurationItem
{
    /**
     * @var array
     */
    protected $behaviorAttributes;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param array $behaviorAttributes
     * @param array $parameters
     */
    public function __construct(array $behaviorAttributes, array $parameters)
    {
        $this->behaviorAttributes = $behaviorAttributes;
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getBehaviorAttributes(): array
    {
        return $this->behaviorAttributes;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
