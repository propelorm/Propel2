<?php

namespace Propel\Generator\Builder\Om\Component;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\PhpModel\ClassDefinition;
use Propel\Generator\Builder\PhpModel\MethodDefinition;
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
    public function __construct(AbstractBuilder $builder, Behavior $behavior = null)
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
        if (is_array($defaultValue)) {
            $defaultValue = PhpConstant::create('[]', null, true);
        }

        $property->setValue($defaultValue);

        $property->setVisibility($visibility);
        $this->getDefinition()->setProperty($property);

        return $property;
    }

    /**
     * @param string $name
     * @param string $visibility
     *
     * @return MethodDefinition
     */
    protected function addMethod($name, $visibility = 'public')
    {
        $method = new MethodDefinition($name);
        $method->setVisibility($visibility);
        $this->getDefinition()->setMethod($method);

        return $method;
    }

    /**
     * Adds a "use $fullClassName" and returns the class name you can use. It ads automatically "use x as y" when necessary.
     *
     * @param string $fullClassName
     * @return string
     */
    public function useClass($fullClassName)
    {
        if ($this->getDefinition()->getQualifiedName() === $fullClassName) {
            return $this->getDefinition()->getName();
        }

        if ($this->getDefinition()->hasUseStatement($fullClassName)) {
            //this full class is already registered, so return its name/alias.
            return $this->getDefinition()->getUseAlias($fullClassName);
        }

        if ($this->classNameInUse($fullClassName)) {
            //name already in use, so use full qualified name and dont place a "use $fullClassName".
            return '\\' . $fullClassName;
        }

        return $this->getDefinition()->declareUse($fullClassName);
    }

    /**
     * If the className (without namespace) of $fullClassName is already in "use" directly or as alias.
     *
     * @param string $fullClassName
     *
     * @return boolean
     */
    public function classNameInUse($fullClassName)
    {
        $className = basename(str_replace('\\', '/', $fullClassName));

        if ($className === $this->getDefinition()->getName()) {
            //when the request fullClassName is current definition we return true,
            //because its not possible to use a same class name in the current namespace.
            return true;
        }

        $statements = $this->getDefinition()->getUseStatements();
        return isset($statements[$className]);
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