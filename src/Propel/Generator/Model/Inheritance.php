<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * A class for information regarding possible objects representing a table.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Inheritance extends MappingModel
{
    /**
     * @var string|null
     */
    private $key;

    /**
     * @var string|null
     */
    private $className;

    /**
     * @var string|null
     */
    private $package;

    /**
     * @var string|null
     */
    private $ancestor;

    /**
     * @var \Propel\Generator\Model\Column|null
     */
    private $column;

    /**
     * Returns a key name.
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Get constant names' safe value of the key name.
     *
     * @return string
     */
    public function getConstantSuffix(): string
    {
        $separator = PhpNameGenerator::STD_SEPARATOR_CHAR;

        return strtoupper(rtrim(preg_replace('/(\W|_)+/', $separator, $this->key), $separator));
    }

    /**
     * Sets a key name.
     *
     * @param string $key
     *
     * @return void
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Sets the parent column
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return void
     */
    public function setColumn(Column $column): void
    {
        $this->column = $column;
    }

    /**
     * Returns the parent column.
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getColumn(): ?Column
    {
        return $this->column;
    }

    /**
     * Returns the class name.
     *
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Sets the class name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setClassName(string $name): void
    {
        $this->className = $name;
    }

    /**
     * Returns the package.
     *
     * @return string|null
     */
    public function getPackage(): ?string
    {
        return $this->package;
    }

    /**
     * Sets the package.
     *
     * @param string $package
     *
     * @return void
     */
    public function setPackage(string $package): void
    {
        $this->package = $package;
    }

    /**
     * Returns the ancestor value.
     *
     * @return string|null
     */
    public function getAncestor(): ?string
    {
        return $this->ancestor;
    }

    /**
     * Sets the ancestor.
     *
     * @param string $ancestor
     *
     * @return void
     */
    public function setAncestor(string $ancestor): void
    {
        $this->ancestor = $ancestor;
    }

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        $this->key = $this->getAttribute('key');
        $this->className = $this->getAttribute('class');
        $this->package = $this->getAttribute('package');
        $this->ancestor = $this->getAttribute('extends');
    }
}
