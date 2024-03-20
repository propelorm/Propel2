<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\SchemaException;

/**
 * Object to hold vendor specific information.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class VendorInfo extends MappingModel
{
    /**
     * @var string|null
     */
    private $type;

    /**
     * @var array
     */
    private $parameters;

    /**
     * Creates a new VendorInfo instance.
     *
     * @param string|null $type RDBMS type (optional)
     * @param array $parameters An associative array of vendor's parameters (optional)
     */
    public function __construct(?string $type = null, array $parameters = [])
    {
        $this->parameters = [];

        if ($type !== null) {
            $this->setType($type);
        }

        if ($parameters) {
            $this->setParameters($parameters);
        }
    }

    /**
     * Sets the RDBMS type for this vendor specific information.
     *
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns the RDBMS type for this vendor specific information.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Sets a parameter value.
     *
     * @param string $name The parameter name
     * @param mixed $value The parameter value
     *
     * @return void
     */
    public function setParameter(string $name, $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Returns a parameter value.
     *
     * @param string $name The parameter name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Returns whether a parameter exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Sets an associative array of parameters for vendor specific information.
     *
     * @param array $parameters Parameter data.
     *
     * @return void
     */
    public function setParameters(array $parameters = []): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns an associative array of parameters for
     * vendor specific information.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns whether this vendor info is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->parameters;
    }

    /**
     * Returns a new VendorInfo object that combines two VendorInfo objects.
     *
     * @param \Propel\Generator\Model\VendorInfo $info
     *
     * @return self
     */
    public function getMergedVendorInfo(VendorInfo $info): self
    {
        $params = array_merge($this->parameters, $info->getParameters());

        $newInfo = new self($this->type);
        $newInfo->setParameters($params);

        return $newInfo;
    }

    /**
     * @return void
     */
    protected function setupObject(): void
    {
        $this->type = $this->getAttribute('type');
    }

    /**
     * Returns the value for the uuid swap flag as set in the vendor information
     * block in schema.xml as a literal ('true' or 'false').
     *
     * @psalm-return 'true'|'false'
     *
     * @see \Propel\Runtime\Util\UuidConverter::uuidToBin()
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return string
     */
    public function getUuidSwapFlagLiteral(): string
    {
        $uuidSwapFlag = $this->getParameter('UuidSwapFlag') ?? 'true';
        if (!in_array($uuidSwapFlag, ['true', 'false'], true)) {
            throw new SchemaException('Value for `/database/vendor/parameter[name="UuidSwapFlag"]` must be `true` or `false`, but it is `' . $uuidSwapFlag . '`');
        }

        return $uuidSwapFlag;
    }
}
