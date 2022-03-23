<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\LogicException;

/**
 * Data about an element with a name and optional namespace, schema and package
 * attributes.
 *
 * @author Ulf Hermann <ulfhermann@kulturserver.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
abstract class ScopedMappingModel extends MappingModel
{
    /**
     * @var string|null
     */
    protected ?string $package = null;

    /**
     * @var bool
     */
    protected bool $packageOverridden = false;

    /**
     * @var string|null
     */
    protected ?string $namespace = null;

    /**
     * @var string|null
     */
    protected ?string $schema = null;

    /**
     * Constructs a new scoped model object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the package has been overriden.
     *
     * @return bool
     */
    public function isPackageOverriden(): bool
    {
        return $this->packageOverridden;
    }

    /**
     * Returns a build property by its name.
     *
     * @param string $name
     *
     * @return string
     */
    abstract protected function getBuildProperty(string $name): string;

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        $this->setPackage($this->getAttribute('package', $this->package));
        $this->setSchema($this->getAttribute('schema', $this->schema));
        $this->setNamespace($this->getAttribute('namespace', $this->namespace));
    }

    /**
     * Returns the namespace.
     *
     * @param bool $getAbsoluteNamespace
     *
     * @return string|null
     */
    public function getNamespace(bool $getAbsoluteNamespace = false): ?string
    {
        if ($getAbsoluteNamespace) {
            return $this->makeNamespaceAbsolute($this->namespace);
        }

        return $this->namespace;
    }

    /**
     * Returns the namespace.
     *
     * @param bool $getAbsoluteNamespace
     *
     * @throws \Propel\Generator\Exception\LogicException
     *
     * @return string
     */
    public function getNamespaceOrFail(bool $getAbsoluteNamespace = false): string
    {
        $namespace = $this->getNamespace($getAbsoluteNamespace);

        if ($namespace === null) {
            throw new LogicException('Namespace is not defined.');
        }

        return $namespace;
    }

    /**
     * Sets the namespace.
     *
     * @param string|null $namespace
     *
     * @return void
     */
    public function setNamespace(?string $namespace): void
    {
        $namespace = $namespace === null
            ? ''
            : rtrim(trim($namespace), '\\');

        if ($namespace === $this->namespace) {
            return;
        }

        $this->namespace = $namespace;
        if ($namespace && (!$this->package || $this->packageOverridden) && $this->getBuildProperty('generator.namespaceAutoPackage')) {
            $this->package = str_replace('\\', '.', $namespace);
            $this->packageOverridden = true;
        }
    }

    /**
     * Returns whether the namespace is absolute.
     *
     * A namespace is absolute if it starts with a "\".
     *
     * @param string|null $namespace
     *
     * @return bool
     */
    public function isAbsoluteNamespace(?string $namespace): bool
    {
        return ($namespace && substr($namespace, 0, 1) === '\\');
    }

    /**
     * Prepends a backslash to a namespace if there is none.
     *
     * A namespace with a backslash is considered absolute.
     *
     * @param string|null $namespace
     *
     * @return string|null
     */
    protected function makeNamespaceAbsolute(?string $namespace): ?string
    {
        $prependBackslash = ($namespace && !$this->isAbsoluteNamespace($namespace));

        return ($prependBackslash) ? "\\$namespace" : $namespace;
    }

    /**
     * Returns the package name.
     *
     * @return string|null
     */
    public function getPackage(): ?string
    {
        return $this->package;
    }

    /**
     * Sets the package name.
     *
     * @param string|null $package
     *
     * @return void
     */
    public function setPackage(?string $package): void
    {
        if ($package === $this->package) {
            return;
        }

        $this->package = $package;
        $this->packageOverridden = false;
    }

    /**
     * Returns the schema name.
     *
     * @return string|null
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * Sets the schema name.
     *
     * @param string|null $schema
     *
     * @return void
     */
    public function setSchema(?string $schema): void
    {
        if ($schema === $this->schema) {
            return;
        }

        $this->schema = $schema;
        if ($schema && !$this->package && $this->getBuildProperty('schemaAutoPackage')) {
            $this->package = $schema;
            $this->packageOverridden = true;
        }

        if ($schema && !$this->namespace && $this->getBuildProperty('generator.schema.autoNamespace')) {
            $this->namespace = $schema;
        }
    }
}
