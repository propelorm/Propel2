<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use gossi\codegen\generator\CodeGenerator;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\PhpModel\ClassDefinition;

/**
 * Abstract class for all builders.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
abstract class AbstractBuilder extends DataModelBuilder
{
    use ComponentTrait;

    /**
     * @var ClassDefinition
     */
    protected $definition;

    /**
     * In this method the actual builder will define the class definition in $this->definition.
     *
     * @return false|null return false if this class should not be generated.
     */
    abstract protected function buildClass();

    protected function getBuilder()
    {
        return $this;
    }

    /**
     * Returns the full class name with namespace. Overwrite this method if you need
     * to have a different class name.
     *
     * @param string $injectNamespace will be inject in the namespace between namespace and className
     * @param string $classPrefix     will be inject in the class name between namespace and className
     *
     * @return string
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        $fullClassName = $this->getEntity()->getFullClassName();
        $namespace = explode('\\', $fullClassName);
        $className = array_pop($namespace);

        if ($injectNamespace) {
            $namespace[] = trim($injectNamespace, '\\');
        }

        if ($classPrefix) {
            $className = $classPrefix . $className;
        }

        if ($namespace) {
            return trim(implode('\\', $namespace) . '\\' . $className, '\\');
        } else {
            return $fullClassName;
        }
    }

    /**
     * Builds the PHP source for current class and returns it as a string.
     *
     * This is the main entry point and defines a basic structure that classes should follow.
     * In most cases this method will not need to be overridden by subclasses.  This method
     * does assume that the output language is PHP code, so it will need to be overridden if
     * this is not the case.
     *
     * @return null|string The resulting PHP sourcecode.
     */
    public function build()
    {
        $this->validateModel();
        $this->definition = new ClassDefinition($this->getFullClassName());

        if (false === $this->buildClass()) {
            return null;
        }

        $generator = new CodeGenerator();

        $code = "<?php\n\n" . $generator->generateCode($this->getDefinition());

        return $code;
    }

    /**
     * @return ClassDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param ClassDefinition $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        if ($this->getEntity()->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($identifier);
        }

        return $identifier;
    }

    /**
     * Gets the full path to the file for the current class.
     *
     * @return string
     */
    public function getClassFilePath()
    {
        return strtr($this->getFullClassName(), '\\', '/') . '.php';
    }

    /**
     * Whether to add the generic mutator methods (setByName(), setByPosition(), fromArray()).
     * This is based on the build property propel.addGenericMutators, and also whether the
     * entity is read-only or an alias.
     */
    protected function isAddGenericMutators()
    {
        $entity = $this->getEntity();

        return
            !$entity->isAlias() &&
            $this->getBuildProperty('generator.objectModel.addGenericMutators') &&
            !$entity->isReadOnly();
    }

    /**
     * Whether to add the generic accessor methods (getByName(), getByPosition(), toArray()).
     * This is based on the build property propel.addGenericAccessors, and also whether the
     * entity is an alias.
     */
    protected function isAddGenericAccessors()
    {
        $entity = $this->getEntity();

        return
            !$entity->isAlias() &&
            $this->getBuildProperty('generator.objectModel.addGenericAccessors');
    }

    /**
     * Returns the className without namespace that is being built by the current class.
     *
     * @return string
     */
    public function getClassName()
    {
        $fullClassName = $this->getFullClassName();
        $namespaces = explode('\\', $fullClassName);

        return array_pop($namespaces);
    }

    /**
     * Validates the current entity to make sure that it won't
     * result in generated code that will not parse.
     *
     * This method may emit warnings for code which may cause problems
     * and will throw exceptions for errors that will definitely cause
     * problems.
     */
    protected function validateModel()
    {
        // Validation is currently only implemented in the subclasses.
    }

    /**
     * Checks whether any registered behavior on that entity has a modifier for a hook
     */
    public function applyBehaviorModifier()
    {
        $className = explode('\\', get_called_class());
        $className = array_pop($className);
        $modifierGetter = 'get' . $className . 'Modifier';

        $hookName = lcfirst($className) . 'Modification';

        foreach ($this->getEntity()->getBehaviors() as $behavior) {
            $modifier = $behavior->$modifierGetter();
            if (method_exists($modifier, $hookName)) {
                $modifier->$hookName($this);
            }
        }
    }

    /**
     * @param string $hookName
     *
     * @return string
     */
    public function applyBehaviorHooks($hookName)
    {
        $body = '';
        foreach ($this->getEntity()->getBehaviors() as $behavior) {
            if (method_exists($behavior, $hookName)) {
                $code = $behavior->$hookName($this);
                if ($code) {
                    $body .= "//behavior hook {$behavior->getName()}#{$behavior->getId()}\n";
                    $body .= $code;
                }
            }
        }

        return $body;
    }

//    /**
//     * Checks whether any registered behavior content creator on that entity exists a contentName
//     *
//     * @param string $contentName The name of the content as called from one of this class methods, e.g.
//     *                            "parentClassName"
//     * @param string $modifier    The name of the modifier object providing the method in the behavior
//     */
//    public function getBehaviorContentBase($contentName, $modifier)
//    {
//        $modifierGetter = 'get' . ucfirst($modifier);
//        foreach ($this->getEntity()->getBehaviors() as $behavior) {
//            $modifier = $behavior->$modifierGetter();
//            if (method_exists($modifier, $contentName)) {
//                return $modifier->$contentName($this);
//            }
//        }
//    }
}