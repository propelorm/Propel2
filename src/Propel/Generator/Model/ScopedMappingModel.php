<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

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
    protected $package;

    /**
     * @var bool
     */
    protected $packageOverridden = false;

    /**
     * @var string|null
     */
    protected $namespace;

    /**
     * @var string|null
     */
    protected $schema;

    /**
     * Constructs a new scoped model object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether or not the package has been overriden.
     *
     * @return bool
     */
    public function isPackageOverriden()
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
    abstract protected function getBuildProperty($name);

    /**
     * @return void
     */
    protected function setupObject()
    {
        $this->setPackage($this->getAttribute('package', $this->package));
        $this->setSchema($this->getAttribute('schema', $this->schema));
        $this->setNamespace($this->getAttribute('namespace', $this->namespace));
    }

    /**
     * Returns the namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Sets the namespace.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace)
    {
        $namespace = rtrim(trim($namespace), '\\');

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
     * Returns whether or not the namespace is absolute.
     *
     * A namespace is absolute if it starts with a "\".
     *
     * @param string $namespace
     *
     * @return bool
     */
    public function isAbsoluteNamespace($namespace)
    {
        return strpos($namespace, '\\') === 0;
    }

    /**
     * Returns the package name.
     *
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Sets the package name.
     *
     * @param string $package
     *
     * @return void
     */
    public function setPackage($package)
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
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Sets the schema name.
     *
     * @param string $schema
     *
     * @return void
     */
    public function setSchema($schema)
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
