<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command\Console\Input;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * ArrayInput represents an input provided as an array.
 *
 * Usage:
 *
 *     $input = new ArrayInput(['command' => 'foo:bar', 'foo' => 'bar', '--bar' => 'foobar']);
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ArrayInput extends Input
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @param array $parameters
     * @param \Symfony\Component\Console\Input\InputDefinition|null $definition
     */
    public function __construct(array $parameters, ?InputDefinition $definition = null)
    {
        $this->parameters = $parameters;

        parent::__construct($definition);
    }

    /**
     * @inheritDoc
     */
    public function getFirstArgument()
    {
        foreach ($this->parameters as $key => $value) {
            if ($key && $key[0] === '-') {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function hasParameterOption($values, $onlyParams = false)
    {
        $values = (array)$values;

        foreach ($this->parameters as $k => $v) {
            if (!is_int($k)) {
                $v = $k;
            }

            if ($onlyParams && $v === '--') {
                return false;
            }

            if (in_array($v, $values)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
        $values = (array)$values;

        foreach ($this->parameters as $k => $v) {
            if ($onlyParams && ($k === '--' || (is_int($k) && $v === '--'))) {
                return $default;
            }

            if (is_int($k)) {
                if (in_array($v, $values)) {
                    return true;
                }
            } elseif (in_array($k, $values)) {
                return $v;
            }
        }

        return $default;
    }

    /**
     * Returns a stringified representation of the args passed to the command.
     *
     * @return string
     */
    public function __toString()
    {
        $params = [];
        foreach ($this->parameters as $param => $val) {
            if ($param && $param[0] === '-') {
                if (is_array($val)) {
                    foreach ($val as $v) {
                        $params[] = $param . ($v != '' ? '=' . $this->escapeToken($v) : '');
                    }
                } else {
                    $params[] = $param . ($val != '' ? '=' . $this->escapeToken($val) : '');
                }
            } else {
                $params[] = is_array($val) ? implode(' ', array_map([$this, 'escapeToken'], $val)) : $this->escapeToken($val);
            }
        }

        return implode(' ', $params);
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function parse()
    {
        foreach ($this->parameters as $key => $value) {
            if ($key === '--') {
                return;
            }
            if (strpos($key, '--') === 0) {
                $this->addLongOption(substr($key, 2), $value);
            } elseif (strpos($key, '-') === 0) {
                $this->addShortOption(substr($key, 1), $value);
            } else {
                $this->addArgument($key, $value);
            }
        }
    }

    /**
     * Adds a short option value.
     *
     * @param string $shortcut
     * @param mixed|null $value
     *
     * @throws \Symfony\Component\Console\Exception\InvalidOptionException When option given doesn't exist
     *
     * @return void
     */
    private function addShortOption(string $shortcut, $value)
    {
        if (!$this->definition->hasShortcut($shortcut)) {
            throw new InvalidOptionException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        $this->addLongOption($this->definition->getOptionForShortcut($shortcut)->getName(), $value);
    }

    /**
     * Adds a long option value.
     *
     * @param string $name
     * @param mixed|null $value
     *
     * @throws \Symfony\Component\Console\Exception\InvalidOptionException When a required value is missing
     *
     * @return void
     */
    private function addLongOption(string $name, $value)
    {
        if (!$this->definition->hasOption($name)) {
            throw new InvalidOptionException(sprintf('The "--%s" option does not exist.', $name));
        }

        $option = $this->definition->getOption($name);

        if ($value === null) {
            if ($option->isValueRequired()) {
                throw new InvalidOptionException(sprintf('The "--%s" option requires a value.', $name));
            }

            if (!$option->isValueOptional()) {
                $value = true;
            }
        }

        $this->options[$name] = $value;
    }

    /**
     * Adds an argument value.
     *
     * @param string|int $name The argument name
     * @param mixed $value The value for the argument
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException When argument given doesn't exist
     *
     * @return void
     */
    private function addArgument($name, $value)
    {
        if (!$this->definition->hasArgument($name)) {
            throw new InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        $this->arguments[$name] = $value;
    }
}
