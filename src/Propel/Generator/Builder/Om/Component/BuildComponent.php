<?php

namespace Propel\Generator\Builder\Om\Component;

use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\PhpModel\ClassDefinition;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Platform\PlatformInterface;

abstract class BuildComponent
{
    /**
     * @var AbstractBuilder
     */
    protected $builder;

    /**
     * @var ClassDefinition
     */
    protected $definition;

    /**
     * @var Behavior
     */
    protected $behavior;

    /**
     * @param AbstractBuilder $builder
     */
    function __construct(AbstractBuilder $builder, Behavior $behavior = null)
    {
        $this->builder = $builder;
        $this->behavior = $behavior;
        $this->definition = $builder->getDefinition();
    }

    /**
     * @return Behavior|null
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * @return ClassDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return AbstractBuilder
     */
    protected function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return \Propel\Generator\Model\Entity
     */
    protected function getEntity()
    {
        return $this->builder->getEntity();
    }


    /**
     * @return PlatformInterface
     */
    protected function getPlatform()
    {
        return $this->builder->getPlatform();
    }

    /**
     * @param string $name
     * @param mixed  $defaultValue
     * @param string $visibility
     *
     * @return PhpProperty
     */
    protected function addProperty($name, $defaultValue = null, $visibility = 'protected')
    {
        $property = new PhpProperty($name);
        $property->setDefaultValue($defaultValue);
        $property->setVisibility($visibility);
        $this->getDefinition()->setProperty($property);

        return $property;
    }

    /**
     * @param string $name
     * @param string $visibility
     *
     * @return PhpMethod
     */
    protected function addMethod($name, $visibility = 'public')
    {
        $method = new PhpMethod($name);
        $method->setVisibility($visibility);
        $this->getDefinition()->setMethod($method);

        return $method;
    }

    protected function addConstructorBody($bodyPart)
    {
        $this->getDefinition()->addConstructorBody($bodyPart);
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function quoteIdentifier($identifier)
    {
        return $this->getBuilder()->quoteIdentifier($identifier);
    }

}