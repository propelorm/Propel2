<?php
namespace Propel\Generator\Builder\Om\Component;


use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\PhpModel\ClassDefinition;
use Propel\Generator\Model\Entity;

/**
 * This trait provides some useful getters for php class names from various builders.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
trait NamingTrait
{

    /**
     * @return AbstractBuilder
     */
    abstract protected function getBuilder();

    /**
     * @var array
     */
    protected $usedRepositories = [];

    /**
     * @param Entity $entity
     * @param string $varName
     * @param int    $spaces
     *
     * @return string
     */
    protected function getRepositoryAssignment(Entity $entity = null, $varName = '$repository', $spaces = 0)
    {
        if (null === $entity) {
            $entity = $this->getBuilder()->getEntity();
        }

        $spaces = is_string($spaces) ? $spaces : str_repeat(' ', $spaces);
        $info = $this->declareRepository($entity);

        $script = "/** @var $varName {$info['shortClassName']} */\n";
        $script .= $spaces . "$varName = {$this->getRepositoryGetter($entity)};";

        return $script;
    }

    /**
     * @param PhpParameter[] $params
     * @param string         $glue
     *
     * @return string
     */
    protected function parameterToString(array $params, $glue = ', ')
    {
        $names = [];
        /** @var PhpParameter $param */
        foreach ($params as $param) {
            $names[] = '$' . $param->getName();
        }

        return implode($glue, $names);
    }

    /**
     * @param Entity $entity
     *
     * @return string without $
     */
    protected function getRepositoryVarName(Entity $entity)
    {
        $info = $this->declareRepository($entity);

        return $info['varName'];
    }

    /**
     * @param Entity $entity
     *
     * @return string
     */
    protected function getRepositoryGetter(Entity $entity)
    {
        $builder = $this->getBuilder();

        $info = $this->declareRepository($entity);
        $entityClassName = $info['entityClassName'];

        if ($builder->getEntity()->isActiveRecord() && (($builder instanceof ActiveRecordTraitbuilder) || ($builder instanceof ObjectBuilder))) {
                $getConfiguration = "\$this->getPropelConfiguration()";
        } else {
            $getConfiguration = "\\Propel\\Runtime\\Configuration::getCurrentConfiguration()";
        }

        return $getConfiguration . "->getRepository('$entityClassName')";
    }

    /**
     * @param string $fullClassName
     *
     * @return string
     */
    protected function extractNamespace($fullClassName)
    {
        $namespace = explode('\\', trim($fullClassName, '\\'));
        array_pop($namespace);

        return implode('\\', $namespace);
    }

    /**
     * @param string $fullClassName
     *
     * @return string
     */
    protected function extractClassName($fullClassName)
    {
        $namespace = explode('\\', trim($fullClassName, '\\'));

        return array_pop($namespace);
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    protected function declareRepository(Entity $entity)
    {
        $builder = $this->getBuilder()->getNewStubRepositoryBuilder($entity);
        $objectBuilder = $this->getBuilder()->getNewObjectBuilder($entity);
        $fullClassName = $builder->getFullClassName();
        $className = $builder->getClassName();

        $namespace = $this->extractNamespace($fullClassName);
        $thisNamespace = $this->extractNamespace($this->getBuilder()->getFullClassName());

        if ($namespace === $thisNamespace) {
            $shortClassName = $className;
        } else {
            $shortClassName = $this->getBuilder()->getDefinition()->declareUse($fullClassName);
        }

        if (!isset($this->usedRepositories[$fullClassName])) {
            $this->usedRepositories[$fullClassName] = [
                'varName' => lcfirst($shortClassName),
                'shortClassName' => $shortClassName,
                'className' => $fullClassName,
                'entityClassName' => $objectBuilder->getFullClassName(),
                'entity' => $entity,
                'builder' => $builder,
                'objectBuilder' => $objectBuilder,
            ];
        }

        return $this->usedRepositories[$fullClassName];
    }

    /**
     * This declares the class use and returns the correct name to use (short class name, Alias, or FQCN)
     *
     * @param  AbstractBuilder $builder
     * @param  boolean         $fqcn true to return the $fqcn class name
     *
     * @return string ClassName, Alias or FQCN
     */
    public function getClassNameFromBuilder(AbstractBuilder $builder, $fqcn = false)
    {
        if ($fqcn) {
            return $builder->getFullClassName();
        }

        return $this->getBuilder()->getDefinition()->declareUse($builder->getFullClassName());
    }

    /**
     * This declares the class use and returns the correct name to use
     *
     * @param Entity $entity
     * @param bool   $fqcn
     *
     * @return string
     */
    public function getClassNameFromEntity(Entity $entity, $fqcn = false)
    {
        $fullClassName = $entity->getFullClassName();

        return $fqcn ? $fullClassName : $this->extractClassName($fullClassName);
    }

    /**
     * Shortcut method to return the [stub] query class name for current entity.
     * This is the class name that is used whenever object or entityMap classes want
     * to invoke methods of the query classes.
     *
     * @param  boolean $fqcn
     *
     * @return string  (e.g. 'MyQuery')
     */
    public function getQueryClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getStubQueryBuilder(), $fqcn);
    }

    /**
     * @param Entity $entity
     * @param bool   $fqcn
     *
     * @return string
     */
    public function getQueryClassNameForEntity(Entity $entity, $fqcn = false)
    {
        return $this->getClassNameFromBuilder(
            $this->getBuilder()->getNewStubQueryBuilder($entity),
            $fqcn
        );
    }

    /**
     * Returns the object class name for current entity.
     * This is the class name that is used whenever object or entitymap classes want
     * to invoke methods of the object classes.
     *
     * @param  boolean $fqcn
     *
     * @return string  (e.g. 'MyEntity' or 'ChildMyEntity')
     */
    public function getObjectClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getObjectBuilder(), $fqcn);
    }

    /**
     * Returns the proxy class name for current entity.
     *
     * @param  boolean $fqcn
     *
     * @return string  (e.g. 'MyEntityProxy')
     */
    public function getProxyClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getProxyBuilder(), $fqcn);
    }

    /**
     * Returns the ActiveRecordTrait name.
     *
     * @param bool $fqcn
     *
     * @return string
     */
    public function getActiveRecordTraitName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getActiveRecordTraitBuilder(), $fqcn);
    }

    /**
     * Returns the entityMap class name for current entity.
     *
     * This is the class name that is used whenever object or entityMap classes want
     * to invoke methods of the object classes.
     *
     * @param  boolean $fqcn
     *
     * @return string (e.g. 'My')
     */
    public function getEntityMapClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getEntityMapBuilder(), $fqcn);
    }

    /**
     * @return string
     */
    public function getRepositoryClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getStubRepositoryBuilder(), $fqcn);
    }

    /**
     * @return string
     */
    public function getRepositoryClassNameForEntity(Entity $entity, $fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getNewStubRepositoryBuilder($entity), $fqcn);
    }
}
