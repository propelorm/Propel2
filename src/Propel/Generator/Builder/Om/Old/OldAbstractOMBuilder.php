<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Exception\LogicException;
use Propel\Generator\Exception\RuntimeException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\CrossForeignKeys;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Entity;

/**
 * Baseclass for OM-building classes.
 *
 * OM-building classes are those that build a PHP (or other) class to service
 * a single entity.  This includes Entity classes, Map classes,
 * Node classes, Nested Set classes, etc.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class OldAbstractOMBuilder extends DataModelBuilder
{
    /**
     * Declared fully qualified classnames, to build the 'namespace' statements
     * according to this entity's namespace.
     *
     * @var array
     */
    protected $declaredClasses = array();

    /**
     * Mapping between fully qualified classnames and their short classname or alias
     *
     * @var array
     */
    protected $declaredShortClassesOrAlias = array();

    /**
     * List of classes that can be use without alias when model don't have namespace
     *
     * @var array
     */
    protected $whiteListOfDeclaredClasses = array('PDO', 'Exception', 'DateTime');

    protected $usedRepositories = [];

    /**
     * Builds the PHP source for current class and returns it as a string.
     *
     * This is the main entry point and defines a basic structure that classes should follow.
     * In most cases this method will not need to be overridden by subclasses.  This method
     * does assume that the output language is PHP code, so it will need to be overridden if
     * this is not the case.
     *
     * @return string The resulting PHP sourcecode.
     */
    public function build()
    {
        $this->validateModel();
        $this->declareClass($this->getFullyQualifiedClassName());

        $script = '';
        $this->addClassOpen($script);
        $this->addClassBody($script);
        $this->addClassClose($script);

        $ignoredNamespace = ltrim($this->getNamespace(), '\\');

        if ($useStatements = $this->getUseStatements($ignoredNamespace ?: 'namespace')) {
            $script = $useStatements . $script;
        }

        if ($namespaceStatement = $this->getNamespaceStatement()) {
            $script = $namespaceStatement . $script;
        }

        $script =  "<?php

" . $script;

        return $this->clean($script);
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

    protected function getRepositoryVarName(Entity $entity)
    {
        $info = $this->declareRepository($entity);
        return $info['varName'];
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function declareRepository(Entity $entity)
    {
        $builder = $this->getNewStubRepositoryBuilder($entity);
        $objectBuilder = $this->getNewStubObjectBuilder($entity);
        $className = $builder->getClassName();

        $trimmedClass = trim($className, '\\');;
        if (!in_array($trimmedClass, $this->whiteListOfDeclaredClasses)) {
            $this->whiteListOfDeclaredClasses[] = $trimmedClass;
        }
        $shortClassName = $this->declareClass($className);

        if (!isset($this->usedRepositories[$className])) {
            $this->usedRepositories[$className] = [
                'varName' => lcfirst($shortClassName),
                'shortClassName' => $shortClassName,
                'className' => $className,
                'entityClassName' => $objectBuilder->getClassName(),
                'entity' => $entity,
                'builder' => $builder,
                'objectBuilder' => $objectBuilder,


            ];
        }

        return $this->usedRepositories[$className];
    }

    /**
     * @param Entity $entity
     * @return string
     */
    protected function getRepositoryGetter(Entity $entity)
    {
        $info = $this->declareRepository($entity);
        $varName = $info['varName'];
        $entityClassName = $info['entityClassName'];

        return "(\$this->$varName ?: Propel::getServiceContainer()->getRepository('$entityClassName'))";
    }

    /**
     * @param Entity $entity
     * @param string $varName
     * @return string
     */
    protected function getRepositoryAssignment(Entity $entity, $varName = '$repository', $spaces = 4)
    {
        $spaces = is_string($spaces) ? $spaces : str_repeat(' ', $spaces);
        $info = $this->declareRepository($entity);

        $script  = "/** @var $varName {$info['shortClassName']} */\n";
        $script .= $spaces . "$varName = {$this->getRepositoryGetter($entity)};";

        return $script;
    }

    /**
     * Creates a $obj = new Book(); code snippet. Can be used by frameworks, for instance, to
     * extend this behavior, e.g. initialize the object after creating the instance or so.
     *
     * @return string Some code
     */
    public function buildObjectInstanceCreationCode($objName, $clsName)
    {
        return "$objName = new $clsName();";
    }

    /**
     * Returns the qualified (prefixed) classname that is being built by the current class.
     * This method must be implemented by child classes.
     *
     * @return string
     */
    abstract public function getUnprefixedClassName();

    /**
     * Returns the unqualified classname (e.g. Book)
     *
     * @return string
     */
    public function getUnqualifiedClassName()
    {
        return $this->getUnprefixedClassName();
    }

    /**
     * Returns the qualified classname (e.g. Model\Book)
     *
     * @return string
     */
    public function getQualifiedClassName()
    {
        if ($namespace = $this->getNamespace()) {
            return $namespace . '\\' . $this->getUnqualifiedClassName();
        }

        return $this->getUnqualifiedClassName();
    }

    /**
     * Returns the fully qualified classname (e.g. \Model\Book)
     *
     * @return string
     */
    public function getFullyQualifiedClassName()
    {
        return '\\' . $this->getQualifiedClassName();
    }
    /**
     * Returns FQCN alias of getFullyQualifiedClassName
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->getFullyQualifiedClassName();
    }

    /**
     * Gets the dot-path representation of current class being built.
     *
     * @return string
     */
    public function getClasspath()
    {
        if ($this->getPackage()) {
            return $this->getPackage() . '.' . $this->getUnqualifiedClassName();
        }

        return $this->getUnqualifiedClassName();
    }

    /**
     * Gets the full path to the file for the current class.
     *
     * @return string
     */
    public function getClassFilePath()
    {
        return ClassTools::createFilePath($this->getPackagePath(), $this->getUnqualifiedClassName());
    }

    /**
     * Gets package name for this entity.
     * This is overridden by child classes that have different packages.
     * @return string
     */
    public function getPackage()
    {
        $pkg = ($this->getEntity()->getPackage() ? $this->getEntity()->getPackage() : $this->getDatabase()->getPackage());
        if (!$pkg) {
            $pkg = $this->getBuildProperty('generator.targetPackage');
        }

        return $pkg;
    }

    /**
     * Returns filesystem path for current package.
     * @return string
     */
    public function getPackagePath()
    {
        $pkg = $this->getPackage();

        if (false !== strpos($pkg, '/')) {
            $pkg = preg_replace('#\.(map|om)$#', '/\1', $pkg);
            $pkg = preg_replace('#\.(Map|Om)$#', '/\1', $pkg);

            return $pkg;
        }

        return strtr($pkg, '.', '/');
    }

    /**
     * Returns the user-defined namespace for this entity,
     * or the database namespace otherwise.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->getEntity()->getNamespace();
    }

    /**
     * This declares the class use and returns the correct name to use (short classname, Alias, or FQCN)
     *
     * @param  AbstractOMBuilder $builder
     * @param  boolean           $fqcn    true to return the $fqcn classname
     * @return string            ClassName, Alias or FQCN
     */
    public function getClassNameFromBuilder($builder, $fqcn = false)
    {
        if ($fqcn) {
            return $builder->getFullyQualifiedClassName();
        }

        $namespace = $builder->getNamespace();
        $class = $builder->getUnqualifiedClassName();

        if (isset($this->declaredClasses[$namespace])
            && isset($this->declaredClasses[$namespace][$class])) {
            return $this->declaredClasses[$namespace][$class];
        }

        return $this->declareClassNamespace($class, $namespace, true);
    }

    /**
     * This declares the class use and returns the correct name to use
     *
     * @param Entity $entity
     * @param bool $fqcn
     * @return string
     */
    public function getClassNameFromEntity(Entity $entity, $fqcn = false)
    {
        $namespace = $entity->getNamespace();
        $class = $entity->getName();

        return $this->declareClassNamespace($class, $namespace, true);
    }

    /**
     * Declare a class to be use and return it's name or it's alias
     *
     * @param  string         $class     the class name
     * @param  string         $namespace the namespace
     * @param  string|boolean $alias     the alias wanted, if set to True, it automatically adds an alias when needed
     * @return string         the class name or it's alias
     */
    public function declareClassNamespace($class, $namespace = '', $alias = false)
    {
        $namespace = trim($namespace, '\\');

        // check if the class is already declared
        if (isset($this->declaredClasses[$namespace])
            && isset($this->declaredClasses[$namespace][$class])) {
            return $this->declaredClasses[$namespace][$class];
        }

        $forcedAlias = $this->needAliasForClassName($class, $namespace);

        if (false === $alias || true === $alias || null === $alias) {
            $aliasWanted = $class;
            $alias = $alias || $forcedAlias;
        } else {
            $aliasWanted = $alias;
            $forcedAlias = false;
        }

        if (!$forcedAlias && !isset($this->declaredShortClassesOrAlias[$aliasWanted])) {
            if (!isset($this->declaredClasses[$namespace])) {
                $this->declaredClasses[$namespace] = array();
            }

            $this->declaredClasses[$namespace][$class] = $aliasWanted;
            $this->declaredShortClassesOrAlias[$aliasWanted] = $namespace . '\\' . $class;

            return $aliasWanted;
        }

        // we have a duplicate class and asked for an automatic Alias
        if (false !== $alias) {
            if ('\\Base' == substr($namespace, -5) || 'Base' == $namespace) {
                return $this->declareClassNamespace($class, $namespace, 'Base' . $class);
            }

            if ('Child' == substr($alias, 0, 5)) {
                //we already requested Child.$class and its in use too,
                //so use the fqcn
                return ($namespace ? '\\' . $namespace : '') .  '\\' . $class;
            } else {
                $autoAliasName = 'Child' . $class;
            }

            return $this->declareClassNamespace($class, $namespace, $autoAliasName);
        }

        throw new LogicException(sprintf(
            'The class %s duplicates the class %s and can\'t be used without alias',
            $namespace . '\\' . $class,
            $this->declaredShortClassesOrAlias[$aliasWanted]
        ));
    }

    /**
     * check if the current $class need an alias or if the class could be used with a shortname without conflict
     *
     * @param string $class
     * @param string $namespace
     * @return boolean
     */
    protected function needAliasForClassName($class, $namespace)
    {
        if ($namespace == $this->getNamespace()) {
            return false;
        }

        if (str_replace('\\Base', '', $namespace) == str_replace('\\Base', '', $this->getNamespace())) {
            return true;
        }

        if (empty($namespace) && 'Base' === $this->getNamespace()) {
            if (str_replace(array('Query'), '', $class) == str_replace(array('Query'), '', $this->getUnqualifiedClassName())) {
                return true;
            }

            if ((false !== strpos($class, 'Query'))) {
                return true;
            }

            // force alias for model without namespace
            if (false === array_search($class, $this->whiteListOfDeclaredClasses, true)) {
                return true;
            }
        }

        if ('Base' === $namespace && '' === $this->getNamespace()) {
            // force alias for model without namespace
            if (false === array_search($class, $this->whiteListOfDeclaredClasses, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Declare a use statement for a $class with a $namespace and an $aliasPrefix
     * This return the short ClassName or an alias
     *
     * @param  string $class       the class
     * @param  string $namespace   the namespace
     * @param  mixed  $aliasPrefix optionally an alias or True to force an automatic alias prefix (Base or Child)
     * @return string the short ClassName or an alias
     */
    public function declareClassNamespacePrefix($class, $namespace = '', $aliasPrefix = false)
    {
        if (false !== $aliasPrefix && true !== $aliasPrefix) {
            $alias = $aliasPrefix . $class;
        } else {
            $alias = $aliasPrefix;
        }

        return $this->declareClassNamespace($class, $namespace, $alias);
    }

    /**
     * Declare a Fully qualified classname with an $aliasPrefix.
     * This returns the short ClassName to use or an alias.
     *
     * @param  string $fullyQualifiedClassName the fully qualified classname
     * @param  mixed  $aliasPrefix             optionally an alias or True to force an automatic alias prefix (Base or Child)
     * @return string the short ClassName or an alias
     */
    public function declareClass($fullyQualifiedClassName, $aliasPrefix = false)
    {
        $fullyQualifiedClassName = trim($fullyQualifiedClassName, '\\');
        if (($pos = strrpos($fullyQualifiedClassName, '\\')) !== false) {
            return $this->declareClassNamespacePrefix(substr($fullyQualifiedClassName, $pos + 1), substr($fullyQualifiedClassName, 0, $pos), $aliasPrefix);
        }
        // root namespace
        return $this->declareClassNamespacePrefix($fullyQualifiedClassName, '', $aliasPrefix);
    }

    /**
     * @param  self           $builder
     * @param  boolean|string $aliasPrefix the prefix for the Alias or True for auto generation of the Alias
     * @return string
     */
    public function declareClassFromBuilder(self $builder, $aliasPrefix = false)
    {
        return $this->declareClassNamespacePrefix($builder->getUnqualifiedClassName(), $builder->getNamespace(), $aliasPrefix);
    }

    public function declareClasses()
    {
        $args = func_get_args();
        foreach ($args as $class) {
            $this->declareClass($class);
        }
    }

    /**
     * Get the list of declared classes for a given $namespace or all declared classes
     *
     * @param  string $namespace the namespace or null
     * @return array  list of declared classes
     */
    public function getDeclaredClasses($namespace = null)
    {
        if (null !== $namespace && isset($this->declaredClasses[$namespace])) {
            return $this->declaredClasses[$namespace];
        }

        return $this->declaredClasses;
    }

    /**
     * return the string for the class namespace
     *
     * @return string
     */
    public function getNamespaceStatement()
    {
        $namespace = $this->getNamespace();
        if (!empty($namespace)) {
            return sprintf("namespace %s;

", $namespace);
        }
    }

    /**
     * Return all the use statement of the class
     *
     * @param  string $ignoredNamespace the ignored namespace
     * @return string
     */
    public function getUseStatements($ignoredNamespace = null)
    {
        $script = '';
        $declaredClasses = $this->declaredClasses;
        unset($declaredClasses[$ignoredNamespace]);
        ksort($declaredClasses);
        foreach ($declaredClasses as $namespace => $classes) {
            asort($classes);
            foreach ($classes as $class => $alias) {
                // Don't use our own class
                if ($class == $this->getUnqualifiedClassName() && $namespace == $this->getNamespace()) {
                    continue;
                }
                if ($class == $alias) {
                    $script .= sprintf("use %s\\%s;
", $namespace, $class);
                } else {
                    $script .= sprintf("use %s\\%s as %s;
", $namespace, $class, $alias);
                }
            }
        }

        return $script;
    }

    /**
     * Constructs variable name for fkey-related objects.
     * @param  ForeignKey $fk
     * @return string
     */
    public function getFKVarName(ForeignKey $fk)
    {
        return lcfirst($this->getFKPhpNameAffix($fk, false));
    }

    /**
     * Constructs variable name for objects which referencing current entity by specified foreign key.
     * @param  ForeignKey $fk
     * @return string
     */
    public function getRefFKCollVarName(ForeignKey $fk)
    {
        return lcfirst($this->getRefFKPhpNameAffix($fk, true));
    }

    /**
     * Constructs variable name for single object which references current entity by specified foreign key
     * which is ALSO a primary key (hence one-to-one relationship).
     * @param  ForeignKey $fk
     * @return string
     */
    public function getPKRefFKVarName(ForeignKey $fk)
    {
        return lcfirst($this->getRefFKPhpNameAffix($fk, false));
    }

    /**
     * Shortcut method to return the [stub] query classname for current entity.
     * This is the classname that is used whenever object or entitymap classes want
     * to invoke methods of the query classes.
     * @param  boolean $fqcn
     * @return string  (e.g. 'Myquery')
     */
    public function getQueryClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getStubQueryBuilder(), $fqcn);
    }

    /**
     * Returns the object classname for current entity.
     * This is the classname that is used whenever object or entitymap classes want
     * to invoke methods of the object classes.
     * @param  boolean $fqcn
     * @return string  (e.g. 'MyEntity' or 'ChildMyEntity')
     */
    public function getObjectClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getStubObjectBuilder(), $fqcn);
    }

    /**
     * Returns always the final unqualified object class name. This is only useful for documentation/phpdoc,
     * not in the actual code.
     *
     * @return string
     */
    public function getObjectName()
    {
        return $this->getStubObjectBuilder()->getUnqualifiedClassName();
    }

    /**
     * Returns the entityMap classname for current entity.
     * This is the classname that is used whenever object or entitymap classes want
     * to invoke methods of the object classes.
     * @param  boolean $fqcn
     * @return string  (e.g. 'My')
     */
    public function getEntityMapClassName($fqcn = false)
    {
        return $this->getClassNameFromBuilder($this->getEntityMapBuilder(), $fqcn);
    }

    /**
     * Get the column constant name (e.g. EntityMapName::COLUMN_NAME).
     *
     * @param Column $col       The column we need a name for.
     * @param string $classname The EntityMap classname to use.
     *
     * @return string If $classname is provided, then will return $classname::COLUMN_NAME; if not, then the EntityMapName is looked up for current entity to yield $currEntityEntityMap::COLUMN_NAME.
     */
    public function getColumnConstant($col, $classname = null)
    {
        if (null === $col) {
            throw new InvalidArgumentException('No columns were specified.');
        }

        if (null === $classname) {
            return $this->getBuildProperty('generator.objectModel.classPrefix') . $col->getFQConstantName();
        }

        // was it overridden in schema.xml ?
        if ($col->getEntityMapName()) {
            $const = strtoupper($col->getEntityMapName());
        } else {
            $const = strtoupper($col->getName());
        }

        return $classname.'::'.Column::CONSTANT_PREFIX.$const;
    }

    /**
     * Convenience method to get the default Join Type for a relation.
     * If the key is required, an INNER JOIN will be returned, else a LEFT JOIN will be suggested,
     * unless the schema is provided with the DefaultJoin attribute, which overrules the default Join Type
     *
     * @param  ForeignKey $fk
     * @return string
     */
    protected function getJoinType(ForeignKey $fk)
    {
        if ($defaultJoin = $fk->getDefaultJoin()) {
            return "'" . $defaultJoin . "'";
        }

        if ($fk->isLocalColumnsRequired()) {
            return 'Criteria::INNER_JOIN';
        }

        return 'Criteria::LEFT_JOIN';
    }

    /**
     * Gets the PHP method name affix to be used for fkeys for the current entity (not referrers to this entity).
     *
     * The difference between this method and the getRefFKPhpNameAffix() method is that in this method the
     * classname in the affix is the foreign entity classname.
     *
     * @param  ForeignKey $fk     The local FK that we need a name for.
     * @param  boolean    $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     * @return string
     */
    public function getFKPhpNameAffix(ForeignKey $fk, $plural = false)
    {
        if ($fk->getName()) {
            if ($plural) {
                return $this->getPluralizer()->getPluralForm($fk->getName());
            }

            return $fk->getName();
        }

        $className = $fk->getForeignEntity()->getName();
        if ($plural) {
            $className = $this->getPluralizer()->getPluralForm($className);
        }

        return $className . $this->getRelatedBySuffix($fk);
    }

    /**
     * @param  CrossForeignKeys $crossFKs
     * @param  bool             $plural
     * @return string
     */
    protected function getCrossFKsPhpNameAffix(CrossForeignKeys $crossFKs, $plural = true)
    {
        $names = [];

        if ($plural) {
            if ($pks = $crossFKs->getUnclassifiedPrimaryKeys()) {
                //we have a non fk as pk as well, so we need to make pluralisation on our own and can't
                //rely on getFKPhpNameAffix's pluralisation
                foreach ($crossFKs->getCrossForeignKeys() as $fk) {
                    $names[] = $this->getFKPhpNameAffix($fk, false);
                }
            } else {
                //we have only fks, so give us names with plural and return those
                $lastIdx = count($crossFKs->getCrossForeignKeys()) - 1;
                foreach ($crossFKs->getCrossForeignKeys() as $idx => $fk) {
                    $needPlural = $idx === $lastIdx; //only last fk should be plural
                    $names[] = $this->getFKPhpNameAffix($fk, $needPlural);
                }

                return implode($names);
            }
        } else {
            // no plural, so $plural=false
            foreach ($crossFKs->getCrossForeignKeys() as $fk) {
                $names[] = $this->getFKPhpNameAffix($fk, false);
            }
        }

        foreach ($crossFKs->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName();
        }

        $name = implode($names);

        return (true === $plural ? $this->getPluralizer()->getPluralForm($name) : $name);
    }

    /**
     * @param  CrossForeignKeys $crossFKs
     * @param  ForeignKey       $excludeFK
     * @return string
     */
    protected function getCrossRefFKGetterName(CrossForeignKeys $crossFKs, ForeignKey $excludeFK)
    {
        $names = [];

        $fks = $crossFKs->getCrossForeignKeys();

        foreach ($crossFKs->getMiddleEntity()->getForeignKeys() as $fk) {
            if ($fk !== $excludeFK && ($fk === $crossFKs->getIncomingForeignKey() || in_array($fk, $fks))) {
                $names[] = $this->getFKPhpNameAffix($fk, false);
            }
        }

        foreach ($crossFKs->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName();
        }

        $name = implode($names);

        return $this->getPluralizer()->getPluralForm($name);
    }

    /**
     * @param CrossForeignKeys $crossFKs
     * @return array
     */
    protected function getCrossFKInformation(CrossForeignKeys $crossFKs)
    {
        $names = [];
        $signatures = [];
        $shortSignature = [];
        $phpDoc = [];

        foreach ($crossFKs->getCrossForeignKeys() as $fk) {
            $crossObjectName  = '$' . lcfirst($this->getFKPhpNameAffix($fk));
            $crossObjectClassName  = $this->getNewObjectBuilder($fk->getForeignEntity())->getObjectClassName();

            $names[] = $crossObjectClassName;
            $signatures[] = "$crossObjectClassName $crossObjectName" . ($fk->isAtLeastOneLocalColumnRequired() ? '' : ' = null');
            $shortSignature[] = $crossObjectName;
            $phpDoc[] = "
     * @param $crossObjectClassName $crossObjectName The object to relate";
        }

        $names = implode(', ', $names). (1 < count($names) ? ' combination' : '');
        $phpDoc = implode($phpDoc);
        $signatures = implode(', ', $signatures);
        $shortSignature = implode(', ', $shortSignature);

        return [
            $names,
            $phpDoc,
            $signatures,
            $shortSignature
        ];
    }

    /**
     * @param  CrossForeignKeys $crossFKs
     * @param  array|ForeignKey $crossFK  will be the first variable defined
     * @return array
     */
    protected function getCrossFKAddMethodInformation(CrossForeignKeys $crossFKs, $crossFK = null)
    {
        if ($crossFK instanceof ForeignKey) {
            $crossObjectName = '$' . lcfirst($this->getFKPhpNameAffix($crossFK));
            $crossObjectClassName = $this->getClassNameFromEntity($crossFK->getForeignEntity());
            $signature[] = "$crossObjectClassName $crossObjectName" . ($crossFK->isAtLeastOneLocalColumnRequired() ? '' : ' = null');
            $shortSignature[] = $crossObjectName;
            $normalizedShortSignature[] = $crossObjectName;
            $phpDoc[] = "
     * @param $crossObjectClassName $crossObjectName";
        }

        $this->extractCrossInformation($crossFKs, $crossFK, $signature, $shortSignature, $normalizedShortSignature, $phpDoc);

        $signature = implode(', ', $signature);
        $shortSignature = implode(', ', $shortSignature);
        $normalizedShortSignature = implode(', ', $normalizedShortSignature);
        $phpDoc = implode(', ', $phpDoc);

        return [$signature, $shortSignature, $normalizedShortSignature, $phpDoc];
    }

    /**
     * Extracts some useful information from a CrossForeignKeys object.
     *
     * @param CrossForeignKeys $crossFKs
     * @param array|ForeignKey $crossFKToIgnore
     * @param array            $signature
     * @param array            $shortSignature
     * @param array            $normalizedShortSignature
     * @param array            $phpDoc
     */
    protected function extractCrossInformation(
        CrossForeignKeys $crossFKs,
        $crossFKToIgnore = null,
        &$signature,
        &$shortSignature,
        &$normalizedShortSignature,
        &$phpDoc
    ) {
        foreach ($crossFKs->getCrossForeignKeys() as $fk) {
            if (is_array($crossFKToIgnore) && in_array($fk, $crossFKToIgnore)) {
                continue;
            } else if ($fk === $crossFKToIgnore) {
                continue;
            }

            $phpType = $typeHint = $this->getClassNameFromEntity($fk->getForeignEntity());
            $name = '$' . lcfirst($this->getFKPhpNameAffix($fk));

            $normalizedShortSignature[] = $name;

            $signature[] = ($typeHint ? "$typeHint " : '') . $name;
            $shortSignature[] = $name;
            $phpDoc[] = "
     * @param $phpType $name";
        }

        foreach ($crossFKs->getUnclassifiedPrimaryKeys() as $primaryKey) {
            //we need to add all those $primaryKey s as additional parameter as they are needed
            //to create the entry in the middle-entity.
            $defaultValue = $primaryKey->getDefaultValueString();

            $phpType = $primaryKey->getPhpType();
            $typeHint = $primaryKey->isPhpArrayType() ? 'array' : '';
            $name = '$' . lcfirst($primaryKey->getName());

            $normalizedShortSignature[] = $name;
            $signature[] = ($typeHint ? "$typeHint " : '') . $name . ('null' !== $defaultValue ? " = $defaultValue" : '');
            $shortSignature[] = $name;
            $phpDoc[] = "
     * @param $phpType $name";
        }

    }


    /**
     * @param  CrossForeignKeys $crossFKs
     * @return string
     */
    protected function getCrossFKsVarName(CrossForeignKeys $crossFKs)
    {
        return 'coll' . $this->getCrossFKsPhpNameAffix($crossFKs);
    }

    /**
     * @param  ForeignKey $crossFK
     * @return string
     */
    protected function getCrossFKVarName(ForeignKey $crossFK)
    {
        return 'coll' . $this->getFKPhpNameAffix($crossFK, true);
    }

    /**
     * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
     *
     * The related by suffix is based on the local columns of the foreign key.  If there is more than
     * one column in a entity that points to the same foreign entity, then a 'RelatedByLocalColName' suffix
     * will be appended.
     *
     * @return string
     */
    protected static function getRelatedBySuffix(ForeignKey $fk)
    {
        $relCol = '';
        foreach ($fk->getLocalForeignMapping() as $localColumnName => $foreignColumnName) {
            $localEntity  = $fk->getEntity();
            $localColumn = $localEntity->getColumn($localColumnName);
            if (!$localColumn) {
                throw new RuntimeException(sprintf('Could not fetch column: %s in entity %s.', $localColumnName, $localEntity->getName()));
            }

            if (count($localEntity->getForeignKeysReferencingEntity($fk->getForeignEntityName())) > 1
             || count($fk->getForeignEntity()->getForeignKeysReferencingEntity($fk->getEntityName())) > 0
             || $fk->getForeignEntityName() == $fk->getEntityName()) {
                // self referential foreign key, or several foreign keys to the same entity, or cross-reference fkey
                $relCol .= $localColumn->getName();
            }
        }

        if (!empty($relCol)) {
            $relCol = 'RelatedBy' . $relCol;
        }

        return $relCol;
    }

    /**
     * Gets the PHP method name affix to be used for referencing foreign key methods and variable names (e.g. set????(), $coll???).
     *
     * The difference between this method and the getFKPhpNameAffix() method is that in this method the
     * classname in the affix is the classname of the local fkey entity.
     *
     * @param  ForeignKey $fk     The referrer FK that we need a name for.
     * @param  boolean    $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     * @return string
     */
    public function getRefFKPhpNameAffix(ForeignKey $fk, $plural = false)
    {
        $pluralizer = $this->getPluralizer();
        if ($fk->getRefPhpName()) {
            return $plural ? $pluralizer->getPluralForm($fk->getRefPhpName()) : $fk->getRefPhpName();
        }

        $className = $fk->getEntity()->getName();
        if ($plural) {
            $className = $pluralizer->getPluralForm($className);
        }

        return $className . $this->getRefRelatedBySuffix($fk);
    }

    protected static function getRefRelatedBySuffix(ForeignKey $fk)
    {
        $relCol = '';
        foreach ($fk->getLocalForeignMapping() as $localColumnName => $foreignColumnName) {
            $localEntity = $fk->getEntity();
            $localColumn = $localEntity->getColumn($localColumnName);
            if (!$localColumn) {
                throw new RuntimeException(sprintf('Could not fetch column: %s in entity %s.', $localColumnName, $localEntity->getName()));
            }
            $foreignKeysToForeignEntity = $localEntity->getForeignKeysReferencingEntity($fk->getForeignEntityName());
            if ($fk->getForeignEntityName() == $fk->getEntityName()) {
                // self referential foreign key
                $relCol .= $fk->getForeignEntity()->getColumn($foreignColumnName)->getName();
                if (count($foreignKeysToForeignEntity) > 1) {
                    // several self-referential foreign keys
                    $relCol .= array_search($fk, $foreignKeysToForeignEntity);
                }
            } elseif (count($foreignKeysToForeignEntity) > 1 || count($fk->getForeignEntity()->getForeignKeysReferencingEntity($fk->getEntityName())) > 0) {
                // several foreign keys to the same entity, or symmetrical foreign key in foreign entity
                $relCol .= $localColumn->getName();
            }
        }

        if (!empty($relCol)) {
            $relCol = 'RelatedBy' . $relCol;
        }

        return $relCol;
    }

    /**
     * Checks whether any registered behavior on that entity has a modifier for a hook
     * @param  string  $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param  string  $modifier The name of the modifier object providing the method in the behavior
     * @return boolean
     */
    public function hasBehaviorModifier($hookName, $modifier)
    {
        $modifierGetter = 'get' . $modifier;
        foreach ($this->getEntity()->getBehaviors() as $behavior) {
            if (method_exists($behavior->$modifierGetter(), $hookName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether any registered behavior on that entity has a modifier for a hook
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param string $modifier The name of the modifier object providing the method in the behavior
     * @param string &$script  The script will be modified in this method.
     */
    public function applyBehaviorModifierBase($hookName, $modifier, &$script, $tab = "        ")
    {
        $modifierGetter = 'get' . $modifier;
        foreach ($this->getEntity()->getBehaviors() as $behavior) {
            $modifier = $behavior->$modifierGetter();
            if (method_exists($modifier, $hookName)) {
                if (strpos($hookName, 'Filter') !== false) {
                    // filter hook: the script string will be modified by the behavior
                    $modifier->$hookName($script, $this);
                } else {
                    // regular hook: the behavior returns a string to append to the script string
                    if (!$addedScript = $modifier->$hookName($this)) {
                        continue;
                    }
                    $script .= "
" . $tab . '// ' . $behavior->getId() . " behavior
";
                    $script .= preg_replace('/^/m', $tab, $addedScript);
                }
            }
        }
    }

    /**
     * Checks whether any registered behavior content creator on that entity exists a contentName
     * @param string $contentName The name of the content as called from one of this class methods, e.g. "parentClassName"
     * @param string $modifier    The name of the modifier object providing the method in the behavior
     */
    public function getBehaviorContentBase($contentName, $modifier)
    {
        $modifierGetter = 'get' . $modifier;
        foreach ($this->getEntity()->getBehaviors() as $behavior) {
            $modifier = $behavior->$modifierGetter();
            if (method_exists($modifier, $contentName)) {
                return $modifier->$contentName($this);
            }
        }
    }

    /**
     * Use Propel simple templating system to render a PHP file using variables
     * passed as arguments. The template file name is relative to the behavior's
     * directory name.
     *
     * @param  string $filename
     * @param  array  $vars
     * @param  string $templateDir
     * @return string
     */
    public function renderTemplate($filename, $vars = array(), $templateDir = '/templates/')
    {
        $filePath = __DIR__ . $templateDir . $filename;
        if (!file_exists($filePath)) {
            // try with '.php' at the end
            $filePath = $filePath . '.php';
            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException(sprintf('Template "%s" not found in "%s" directory', $filename, __DIR__ . $templateDir));
            }
        }
        $template = new PropelTemplate();
        $template->setTemplateFile($filePath);
        $vars = array_merge($vars, array('behavior' => $this));

        return $template->render($vars);
    }

    /**
     * @return string
     */
    public function getEntityMapClass()
    {
        return $this->getStubObjectBuilder()->getUnqualifiedClassName() . 'EntityMap';
    }

    /**
     * Most of the code comes from the PHP-CS-Fixer project
     */
    private function clean($content)
    {
        // trailing whitespaces
        $content = preg_replace('/[ \t]*$/m', '', $content);

        // indentation
        $content = preg_replace_callback('/^([ \t]+)/m', function ($matches) use ($content) {
            return str_replace("\t", '    ', $matches[0]);
        }, $content);

        // line feed
        $content = str_replace("\r\n", "\n", $content);

        // Unused "use" statements
        preg_match_all('/^use (?P<class>[^\s;]+)(?:\s+as\s+(?P<alias>.*))?;/m', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (isset($match['alias'])) {
                $short = $match['alias'];
            } else {
                $parts = explode('\\', $match['class']);
                $short = array_pop($parts);
            }

            preg_match_all('/\b'.$short.'\b/i', str_replace($match[0]."\n", '', $content), $m);
            if (!count($m[0])) {
                $content = str_replace($match[0]."\n", '', $content);
            }
        }

        // end of line
        if (strlen($content) && "\n" != substr($content, -1)) {
            $content = $content."\n";
        }

        return $content;
    }
//
//    /**
//     * Opens class.
//     *
//     * @param string &$script
//     */
//    abstract protected function addClassOpen(&$script);
//
//    /**
//     * This method adds the contents of the generated class to the script.
//     *
//     * This method is abstract and should be overridden by the subclasses.
//     *
//     * Hint: Override this method in your subclass if you want to reorganize or
//     * drastically change the contents of the generated object class.
//     *
//     * @param string &$script The script will be modified in this method.
//     */
//    abstract protected function addClassBody(&$script);
//
//    /**
//     * Closes class.
//     *
//     * @param string &$script
//     */
//    abstract protected function addClassClose(&$script);
}
