<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\ConfigLoad;

use Propel\Generator\Behavior\ConfigStore\ConfigOperationBehavior;
use Propel\Generator\Behavior\ConfigStore\ConfigurationStore;
use Propel\Generator\Exception\SchemaException;

class ConfigLoadBehavior extends ConfigOperationBehavior
{
    /**
     * @var string
     */
    public const ATTRIBUTE_KEY_REF = 'ref';

    /**
     * @var string
     */
    public const ATTRIBUTE_KEY_MULTIPLE = 'multiple';

    /**
     * @return string
     */
    protected function getKey(): string
    {
        return $this->getAttribute(static::ATTRIBUTE_KEY_REF);
    }

    /**
     * @param \Propel\Generator\Model\Database|\Propel\Generator\Model\Table $behaviorable
     *
     * @return void
     */
    protected function apply($behaviorable): void
    {
        $this->validateAttributes();
        $this->createBehavior($behaviorable);
    }

    /**
     * @param \Propel\Generator\Model\Database|\Propel\Generator\Model\Table $behaviorable
     *
     * @return void
     */
    public function createBehavior($behaviorable): void
    {
        $configuration = ConfigurationStore::getInstance()->loadPreconfiguration($this->getKey());
        $fullAttributes = array_merge([], $configuration->getBehaviorAttributes(), $this->getAuxilaryAttributes());
        $fullParams = array_merge([], $configuration->getParameters(), $this->parameters);
        $behavior = $behaviorable->addBehavior($fullAttributes);
        $behavior->setParameters(array_merge($behavior->getParameters(), $fullParams));
    }

    /**
     * @return array
     */
    protected function getAuxilaryAttributes(): array
    {
        $ownAttributes = [
            static::ATTRIBUTE_KEY_REF => 1,
            static::ATTRIBUTE_KEY_MULTIPLE => 1,
            'name' => 1,
            'id' => 1,
        ];

        $attributes = array_diff_key($this->attributes, $ownAttributes);

        if ($this->getAttribute(static::ATTRIBUTE_KEY_MULTIPLE, false)) {
            $attributes['id'] = $this->getKey() . '_' . uniqid();
        }

        return $attributes;
    }

    /**
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    protected function validateAttributes(): void
    {
        if (!$this->getAttribute(static::ATTRIBUTE_KEY_REF)) {
            throw new SchemaException(sprintf("%s behavior: required parameter '%s' is missing.", $this->getName(), static::ATTRIBUTE_KEY_REF));
        }
    }
}
