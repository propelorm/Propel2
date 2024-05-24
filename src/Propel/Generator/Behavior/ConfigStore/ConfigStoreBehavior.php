<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\ConfigStore;

use Propel\Generator\Exception\SchemaException;

class ConfigStoreBehavior extends ConfigOperationBehavior
{
    /**
     * Use store instad of load behavior.
     *
     * @var string
     */
    public const ATTRIBUTE_KEY_BEHAVIOR = 'behavior';

    /**
     * Make sure behavior is never applied twice.
     *
     * @var bool
     */
    protected $wasApplied = false;

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
     * @param \Propel\Generator\Model\Database|\Propel\Generator\Model\Table $behaviorable
     *
     * @return void
     */
    protected function apply($behaviorable): void
    {
        if ($this->wasApplied) {
            return;
        }
        $this->wasApplied = true;
        $this->validateAttributes();
        $this->storeConfiguration();
    }

    /**
     * @return string
     */
    protected function getKey(): string
    {
        return $this->getAttribute('id');
    }

    /**
     * @return void
     */
    protected function storeConfiguration(): void
    {
        $attributes = $this->getAuxilaryAttributes();
        $attributes['name'] = $this->getAttribute(static::ATTRIBUTE_KEY_BEHAVIOR);
        ConfigurationStore::getInstance()->storePreconfiguration($this->getKey(), $attributes, $this->parameters);
    }

    /**
     * @return array
     */
    protected function getAuxilaryAttributes(): array
    {
        $ownAttributes = [
            static::ATTRIBUTE_KEY_BEHAVIOR => 1,
            'name' => 1,
            'id' => 1,
        ];

        return array_diff_key($this->attributes, $ownAttributes);
    }

    /**
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    protected function validateAttributes(): void
    {
        if (!$this->getAttribute(static::ATTRIBUTE_KEY_BEHAVIOR)) {
            throw new SchemaException(sprintf("%s behavior: required parameter '%s' is missing.", $this->getName(), static::ATTRIBUTE_KEY_BEHAVIOR));
        }

        if (!$this->getAttribute('id')) {
            throw new SchemaException(sprintf("%s behavior: required parameter 'id' is missing.", $this->getName()));
        }

        if (ConfigurationStore::getInstance()->hasPreconfiguration($this->getKey())) {
            $format = "%s behavior for '%s': key '%s' is already in use.";
            $message = sprintf($format, $this->getName(), $this->getAttribute(static::ATTRIBUTE_KEY_BEHAVIOR), $this->getKey());

            throw new SchemaException($message);
        }
    }
}
