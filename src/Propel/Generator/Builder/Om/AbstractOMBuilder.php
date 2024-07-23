<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Builder\Om;

use Propel\Common\Util\PathTrait;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Exception\LogicException;
use Propel\Generator\Exception\RuntimeException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\CrossForeignKeys;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\VendorInfo;

/**
 * Baseclass for OM-building classes.
 *
 * OM-building classes are those that build a PHP (or other) class to service
 * a single table. This includes Entity classes, Map classes,
 * Node classes, Nested Set classes, etc.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class AbstractOMBuilder extends DataModelBuilder
{
    use PathTrait;

    /**
     * Declared fully qualified classnames, to build the 'namespace' statements
     * according to this table's namespace.
     *
     * @var array<string, array<string, string>>
     */
    protected $declaredClasses = [];

    /**
     * Mapping between fully qualified classnames and their short classname or alias.
     *
     * @var array<string, string>
     */
    protected $declaredShortClassesOrAlias = [];

    /**
     * List of classes that can be use without alias when model don't have namespace.
     *
     * @var array<string>
     */
    protected $whiteListOfDeclaredClasses = ['PDO', 'Exception', 'DateTime'];

    /**
     * Builds the PHP source for current class and returns it as a string.
     *
     * This is the main entry point and defines a basic structure that classes should follow.
     * In most cases this method will not need to be overridden by subclasses. This method
     * does assume that the output language is PHP code, so it will need to be overridden if
     * this is not the case.
     *
     * @return string the resulting PHP sourcecode
     */
    public function build(): string
    {
        $this->validateModel();
        $this->declareClass($this->getFullyQualifiedClassName());

        $script = '';
        $this->addClassOpen($script);
        $this->addClassBody($script);
        $this->addClassClose($script);

        $ignoredNamespace = ltrim((string) $this->getNamespace(), '\\');

        $useStatements = $this->getUseStatements($ignoredNamespace ?: 'namespace');

        if ($useStatements) {
            $script = $useStatements . $script;
        }

        $namespaceStatement = $this->getNamespaceStatement();

        if ($namespaceStatement) {
            $script = $namespaceStatement . $script;
        }

        $script = '<?php

' . $script;

        return $this->clean($script);
    }

    /**
     * Validates the current table to make sure that it won't
     * result in generated code that will not parse.
     *
     * This method may emit warnings for code which may cause problems
     * and will throw exceptions for errors that will definitely cause
     * problems.
     */
    protected function validateModel(): void
    {
        // Validation is currently only implemented in the subclasses.
    }

    /**
     * Creates a $obj = new Book(); code snippet. Can be used by frameworks, for instance, to
     * extend this behavior, e.g. initialize the object after creating the instance or so.
     *
     * @return string Some code
     */
    public function buildObjectInstanceCreationCode(string $objName, string $clsName): string
    {
        return "{$objName} = new {$clsName}();";
    }

    /**
     * Returns the qualified (prefixed) classname that is being built by the current class.
     * This method must be implemented by child classes.
     */
    abstract public function getUnprefixedClassName(): string;

    /**
     * Returns the unqualified classname (e.g. Book).
     */
    public function getUnqualifiedClassName(): string
    {
        return $this->getUnprefixedClassName();
    }

    /**
     * Returns the qualified classname (e.g. Model\Book).
     */
    public function getQualifiedClassName(): string
    {
        $namespace = $this->getNamespace();

        if ($namespace) {
            return $namespace . '\\' . $this->getUnqualifiedClassName();
        }

        return $this->getUnqualifiedClassName();
    }

    /**
     * Returns the fully qualified classname (e.g. \Model\Book).
     */
    public function getFullyQualifiedClassName(): string
    {
        return '\\' . $this->getQualifiedClassName();
    }

    /**
     * Returns FQCN alias of getFullyQualifiedClassName.
     */
    public function getClassName(): string
    {
        return $this->getFullyQualifiedClassName();
    }

    /**
     * Gets the dot-path representation of current class being built.
     */
    public function getClasspath(): string
    {
        if ($this->getPackage()) {
            return $this->getPackage() . '.' . $this->getUnqualifiedClassName();
        }

        return $this->getUnqualifiedClassName();
    }

    /**
     * Gets the full path to the file for the current class.
     */
    public function getClassFilePath(): string
    {
        return ClassTools::createFilePath($this->getPackagePath(), $this->getUnqualifiedClassName());
    }

    /**
     * Gets package name for this table.
     * This is overridden by child classes that have different packages.
     */
    public function getPackage(): ?string
    {
        $pkg = ($this->getTable()->getPackage() ?: $this->getDatabaseOrFail()->getPackage());

        if (!$pkg) {
            $pkg = (string) $this->getBuildProperty('generator.targetPackage');
        }

        return $pkg;
    }

    /**
     * Returns filesystem path for current package.
     */
    public function getPackagePath(): string
    {
        $pkg = (string) $this->getPackage();

        if (false !== strpos($pkg, '/')) {
            $pkg = (string) preg_replace('#\.(map|om)$#', '/\1', $pkg);

            return (string) preg_replace('#\.(Map|Om)$#', '/\1', $pkg);
        }

        $path = $pkg;

        $path = str_replace('...', '$$/', $path);
        $path = strtr(ltrim($path, '.'), '.', '/');

        return str_replace('$$/', '../', $path);
    }

    /**
     * Returns the user-defined namespace for this table,
     * or the database namespace otherwise.
     *
     * @return null|string Currently returns null in some cases - should be fixed
     */
    public function getNamespace(): ?string
    {
        return $this->getTable()->getNamespace();
    }

    /**
     * Returns the user-defined namespace for this table,
     * or the database namespace otherwise.
     */
    public function getNamespaceOrFail(): string
    {
        return $this->getTable()->getNamespaceOrFail();
    }

    /**
     * This declares the class use and returns the correct name to use (short classname, Alias, or FQCN).
     *
     * @param bool $fqcn true to return the $fqcn classname
     *
     * @return string ClassName, Alias or FQCN
     */
    public function getClassNameFromBuilder(self $builder, bool $fqcn = false): string
    {
        if ($fqcn) {
            return $builder->getFullyQualifiedClassName();
        }

        $namespace = (string) $builder->getNamespace();
        $class     = $builder->getUnqualifiedClassName();

        if (
            isset($this->declaredClasses[$namespace], $this->declaredClasses[$namespace][$class])
        ) {
            return $this->declaredClasses[$namespace][$class];
        }

        return $this->declareClassNamespace($class, $namespace, true);
    }

    /**
     * This declares the class use and returns the correct name to use.
     */
    public function getClassNameFromTable(Table $table): string
    {
        $namespace = (string) $table->getNamespace();
        $class     = $table->getPhpName();

        return $this->declareClassNamespace($class, $namespace, true);
    }

    /**
     * Declare a class to be use and return its name or its alias.
     *
     * @param string           $class     the class name
     * @param string           $namespace the namespace
     * @param null|bool|string $alias     the alias wanted, if set to True, it automatically adds an alias when needed
     *
     * @return string The class name or its alias
     *
     * @throws LogicException
     */
    public function declareClassNamespace(string $class, string $namespace = '', $alias = false): string
    {
        $namespace = trim($namespace, '\\');

        // check if the class is already declared
        if (isset($this->declaredClasses[$namespace][$class])) {
            return $this->declaredClasses[$namespace][$class];
        }

        $forcedAlias = $this->needAliasForClassName($class, $namespace);

        if (false === $alias || true === $alias || null === $alias) {
            $aliasWanted = $class;
            $alias       = $alias || $forcedAlias;
        } else {
            $aliasWanted = $alias;
            $forcedAlias = false;
        }

        if (!$forcedAlias && !isset($this->declaredShortClassesOrAlias[$aliasWanted])) {
            $this->declaredClasses[$namespace][$class]       = $aliasWanted;
            $this->declaredShortClassesOrAlias[$aliasWanted] = $namespace . '\\' . $class;

            return $aliasWanted;
        }

        // we have a duplicate class and asked for an automatic Alias
        if (false !== $alias) {
            if ('\Base' === substr($namespace, -5) || 'Base' === $namespace) {
                return $this->declareClassNamespace($class, $namespace, 'Base' . $class);
            }

            if ('Child' === substr((string) $alias, 0, 5)) {
                // we already requested Child.$class and its in use too,
                // so use the fqcn
                return ($namespace ? '\\' . $namespace : '') . '\\' . $class;
            }
            $autoAliasName = 'Child' . $class;

            return $this->declareClassNamespace($class, $namespace, $autoAliasName);
        }

        throw new LogicException(sprintf(
            'The class %s duplicates the class %s and can\'t be used without alias',
            $namespace . '\\' . $class,
            $this->declaredShortClassesOrAlias[$aliasWanted],
        ));
    }

    /**
     * check if the current $class need an alias or if the class could be used with a shortname without conflict.
     */
    protected function needAliasForClassName(string $class, string $classNamespace): bool
    {
        // Should remove this check by not allowing nullable return values in getNamespace
        if (null === $this->getNamespace()) {
            return false;
        }

        $builderNamespace = trim($this->getNamespace(), '\\');

        if ($classNamespace == $builderNamespace) {
            return false;
        }

        if (str_replace('\Base', '', $classNamespace) == str_replace('\Base', '', $builderNamespace)) {
            return true;
        }

        if (!$classNamespace && 'Base' === $builderNamespace) {
            if (str_replace(['Query'], '', $class) == str_replace(['Query'], '', $this->getUnqualifiedClassName())) {
                return true;
            }

            if (false !== strpos($class, 'Query')) {
                return true;
            }

            // force alias for model without namespace
            if (!in_array($class, $this->whiteListOfDeclaredClasses, true)) {
                return true;
            }
        }

        if ('Base' === $classNamespace && '' === $builderNamespace) {
            // force alias for model without namespace
            if (!in_array($class, $this->whiteListOfDeclaredClasses, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Declare a use statement for a $class with a $namespace and an $aliasPrefix
     * This return the short ClassName or an alias.
     *
     * @param string $class       the class
     * @param string $namespace   the namespace
     * @param mixed  $aliasPrefix optionally an alias or True to force an automatic alias prefix (Base or Child)
     *
     * @return string the short ClassName or an alias
     */
    public function declareClassNamespacePrefix(string $class, string $namespace = '', $aliasPrefix = false): string
    {
        if (false !== $aliasPrefix && true !== $aliasPrefix) {
            $alias = $aliasPrefix . $class;
        } else {
            $alias = $aliasPrefix;
        }

        return $this->declareClassNamespace($class, $namespace, $alias);
    }

    /**
     * Declare a Fully qualified classname with an $aliasPrefix
     * This return the short ClassName to use or an alias.
     *
     * @param string $fullyQualifiedClassName the fully qualified classname
     * @param mixed  $aliasPrefix             optionally an alias or True to force an automatic alias prefix (Base or Child)
     *
     * @return string the short ClassName or an alias
     */
    public function declareClass(string $fullyQualifiedClassName, $aliasPrefix = false): string
    {
        $fullyQualifiedClassName = trim($fullyQualifiedClassName, '\\');
        $pos                     = strrpos($fullyQualifiedClassName, '\\');

        if (false !== $pos) {
            return $this->declareClassNamespacePrefix(substr($fullyQualifiedClassName, $pos + 1), substr($fullyQualifiedClassName, 0, $pos), $aliasPrefix);
        }

        // root namespace
        return $this->declareClassNamespacePrefix($fullyQualifiedClassName, '', $aliasPrefix);
    }

    /**
     * @param bool|string $aliasPrefix the prefix for the Alias or True for auto generation of the Alias
     */
    public function declareClassFromBuilder(self $builder, $aliasPrefix = false): string
    {
        return $this->declareClassNamespacePrefix(
            $builder->getUnqualifiedClassName(),
            (string) $builder->getNamespace(),
            $aliasPrefix,
        );
    }

    public function declareClasses(): void
    {
        $args = func_get_args();

        foreach ($args as $class) {
            $this->declareClass($class);
        }
    }

    /**
     * Get the list of declared classes for a given $namespace or all declared classes.
     *
     * @param null|string $namespace the namespace or null
     *
     * @return array list of declared classes
     */
    public function getDeclaredClasses(?string $namespace = null): array
    {
        if (null !== $namespace && isset($this->declaredClasses[$namespace])) {
            return $this->declaredClasses[$namespace];
        }

        return $this->declaredClasses;
    }

    /**
     * return the string for the class namespace.
     */
    public function getNamespaceStatement(): ?string
    {
        $namespace = $this->getNamespace();

        if ($namespace) {
            return sprintf('namespace %s;

', $namespace);
        }

        return null;
    }

    /**
     * Return all the use statement of the class.
     *
     * @param null|string $ignoredNamespace the ignored namespace
     */
    public function getUseStatements(?string $ignoredNamespace = null): string
    {
        $script          = '';
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
                    $script .= sprintf('use %s\\%s;
', $namespace, $class);
                } else {
                    $script .= sprintf('use %s\\%s as %s;
', $namespace, $class, $alias);
                }
            }
        }

        return $script;
    }

    /**
     * Shortcut method to return the [stub] query classname for current table.
     * This is the classname that is used whenever object or tablemap classes want
     * to invoke methods of the query classes.
     *
     * @return string (e.g. 'Myquery')
     */
    public function getQueryClassName(bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder($this->getStubQueryBuilder(), $fqcn);
    }

    /**
     * Returns the object classname for current table.
     * This is the classname that is used whenever object or tablemap classes want
     * to invoke methods of the object classes.
     *
     * @return string (e.g. 'MyTable' or 'ChildMyTable')
     */
    public function getObjectClassName(bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder($this->getStubObjectBuilder(), $fqcn);
    }

    /**
     * Returns always the final unqualified object class name. This is only useful for documentation/phpdoc,
     * not in the actual code.
     */
    public function getObjectName(): string
    {
        return $this->getStubObjectBuilder()->getUnqualifiedClassName();
    }

    /**
     * Returns the tableMap classname for current table.
     * This is the classname that is used whenever object or tablemap classes want
     * to invoke methods of the object classes.
     *
     * @return string (e.g. 'My')
     */
    public function getTableMapClassName(bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder($this->getTableMapBuilder(), $fqcn);
    }

    /**
     * Get the column constant name (e.g. TableMapName::COLUMN_NAME).
     *
     * @param Column      $col       the column we need a name for
     * @param null|string $classname the TableMap classname to use
     *
     * @return string if $classname is provided, then will return $classname::COLUMN_NAME; if not, then the TableMapName is looked up for current table to yield $currTableTableMap::COLUMN_NAME
     */
    public function getColumnConstant(Column $col, ?string $classname = null): string
    {
        if (null === $classname) {
            return $this->getBuildProperty('generator.objectModel.classPrefix') . $col->getFQConstantName();
        }

        // was it overridden in schema.xml ?
        if ($col->getTableMapName()) {
            $const = strtoupper($col->getTableMapName());
        } else {
            $const = strtoupper($col->getName());
        }

        return $classname . '::' . Column::CONSTANT_PREFIX . $const;
    }

    /**
     * Convenience method to get the default Join Type for a relation.
     * If the key is required, an INNER JOIN will be returned, else a LEFT JOIN will be suggested,
     * unless the schema is provided with the DefaultJoin attribute, which overrules the default Join Type.
     */
    protected function getJoinType(ForeignKey $fk): string
    {
        $defaultJoin = $fk->getDefaultJoin();

        if ($defaultJoin) {
            return "'" . $defaultJoin . "'";
        }

        if ($fk->isLocalColumnsRequired()) {
            return 'Criteria::INNER_JOIN';
        }

        return 'Criteria::LEFT_JOIN';
    }

    /**
     * Gets the PHP method name affix to be used for fkeys for the current table (not referrers to this table).
     *
     * The difference between this method and the getRefFKPhpNameAffix() method is that in this method the
     * classname in the affix is the foreign table classname.
     *
     * @param ForeignKey $fk     the local FK that we need a name for
     * @param bool       $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     */
    public function getFKPhpNameAffix(ForeignKey $fk, bool $plural = false): string
    {
        if (null !== $fk->getPhpName()) {
            if ($plural) {
                return $this->getPluralizer()->getPluralForm($fk->getPhpName());
            }

            return $fk->getPhpName();
        }

        $className = $fk->getForeignTableOrFail()->getPhpName();

        if ($plural) {
            $className = $this->getPluralizer()->getPluralForm($className);
        }

        return $className . $this->getRelatedBySuffix($fk);
    }

    protected function getCrossFKsPhpNameAffix(CrossForeignKeys $crossFKs, bool $plural = true): string
    {
        $baseName = $this->buildCombineCrossFKsPhpNameAffix($crossFKs, false);

        $existingTable   = $this->getDatabase()->getTableByPhpName($baseName);
        $isNameCollision = $existingTable && $this->getTable()->isConnectedWithTable($existingTable);

        return ($plural || $isNameCollision) ? $this->buildCombineCrossFKsPhpNameAffix($crossFKs, $plural) : $baseName;
    }

    protected function buildCombineCrossFKsPhpNameAffix(CrossForeignKeys $crossFKs, bool $plural = true, bool $withPrefix = false): string
    {
        $names = [];

        if ($withPrefix) {
            $names[] = 'Cross';
        }
        $fks                     = $crossFKs->getCrossForeignKeys();
        $lastCrossFk             = array_pop($fks);
        $unclassifiedPrimaryKeys = $crossFKs->getUnclassifiedPrimaryKeys();
        $lastIsPlural            = $plural && !$unclassifiedPrimaryKeys;

        foreach ($fks as $fk) {
            $names[] = $this->getFKPhpNameAffix($fk, false);
        }
        $names[] = $this->getFKPhpNameAffix($lastCrossFk, $lastIsPlural);

        if (!$unclassifiedPrimaryKeys) {
            return implode('', $names);
        }

        foreach ($unclassifiedPrimaryKeys as $pk) {
            $names[] = $pk->getPhpName();
        }

        $name = implode('', $names);

        return true === $plural ? $this->getPluralizer()->getPluralForm($name) : $name;
    }

    protected function getCrossRefFKGetterName(CrossForeignKeys $crossFKs, ForeignKey $excludeFK): string
    {
        $names = [];

        $fks = $crossFKs->getCrossForeignKeys();

        foreach ($crossFKs->getMiddleTable()->getForeignKeys() as $fk) {
            if ($fk !== $excludeFK && ($fk === $crossFKs->getIncomingForeignKey() || in_array($fk, $fks))) {
                $names[] = $this->getFKPhpNameAffix($fk, false);
            }
        }

        foreach ($crossFKs->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getPhpName();
        }

        $name = implode('', $names);

        return $this->getPluralizer()->getPluralForm($name);
    }

    protected function getCrossFKInformation(CrossForeignKeys $crossFKs): array
    {
        $names          = [];
        $signatures     = [];
        $shortSignature = [];
        $phpDoc         = [];

        foreach ($crossFKs->getCrossForeignKeys() as $fk) {
            $crossObjectName      = '$' . lcfirst($this->getFKPhpNameAffix($fk));
            $crossObjectClassName = $this->getNewObjectBuilder($fk->getForeignTableOrFail())->getObjectClassName();

            $names[]          = $crossObjectClassName;
            $signatures[]     = "{$crossObjectClassName} {$crossObjectName}" . ($fk->isAtLeastOneLocalColumnRequired() ? '' : ' = null');
            $shortSignature[] = $crossObjectName;
            $phpDoc[]         = "
     * @param {$crossObjectClassName} {$crossObjectName} The object to relate";
        }

        $names          = implode(', ', $names) . (1 < count($names) ? ' combination' : '');
        $phpDoc         = implode('', $phpDoc);
        $signatures     = implode(', ', $signatures);
        $shortSignature = implode(', ', $shortSignature);

        return [
            $names,
            $phpDoc,
            $signatures,
            $shortSignature,
        ];
    }

    /**
     * @param null|array|ForeignKey $crossFK will be the first variable defined
     *
     * @return array<string>
     */
    protected function getCrossFKAddMethodInformation(CrossForeignKeys $crossFKs, $crossFK = null): array
    {
        $signature = $shortSignature = $normalizedShortSignature = $phpDoc = [];

        if ($crossFK instanceof ForeignKey) {
            $crossObjectName            = '$' . lcfirst($this->getFKPhpNameAffix($crossFK));
            $crossObjectClassName       = $this->getClassNameFromTable($crossFK->getForeignTableOrFail());
            $signature[]                = "{$crossObjectClassName} {$crossObjectName}" . ($crossFK->isAtLeastOneLocalColumnRequired() ? '' : ' = null');
            $shortSignature[]           = $crossObjectName;
            $normalizedShortSignature[] = $crossObjectName;
            $phpDoc[]                   = "
     * @param {$crossObjectClassName} {$crossObjectName}";
        } elseif (null == $crossFK) {
            $crossFK = [];
        }

        $this->extractCrossInformation($crossFKs, $crossFK, $signature, $shortSignature, $normalizedShortSignature, $phpDoc);

        $signature                = implode(', ', $signature);
        $shortSignature           = implode(', ', $shortSignature);
        $normalizedShortSignature = implode(', ', $normalizedShortSignature);
        $phpDoc                   = implode(', ', $phpDoc);

        return [$signature, $shortSignature, $normalizedShortSignature, $phpDoc];
    }

    /**
     * Extracts some useful information from a CrossForeignKeys object.
     *
     * @param array|ForeignKey $crossFKToIgnore
     */
    protected function extractCrossInformation(
        CrossForeignKeys $crossFKs,
        $crossFKToIgnore,
        array &$signature,
        array &$shortSignature,
        array &$normalizedShortSignature,
        array &$phpDoc
    ): void {
        foreach ($crossFKs->getCrossForeignKeys() as $fk) {
            if (is_array($crossFKToIgnore) && in_array($fk, $crossFKToIgnore)) {
                continue;
            }

            if ($fk === $crossFKToIgnore) {
                continue;
            }

            $phpType = $typeHint = $this->getClassNameFromTable($fk->getForeignTableOrFail());
            $name    = '$' . lcfirst($this->getFKPhpNameAffix($fk));

            $normalizedShortSignature[] = $name;

            $signature[]      = ($typeHint ? "{$typeHint} " : '') . $name;
            $shortSignature[] = $name;
            $phpDoc[]         = "
     * @param {$phpType} {$name}";
        }

        foreach ($crossFKs->getUnclassifiedPrimaryKeys() as $primaryKey) {
            // we need to add all those $primaryKey s as additional parameter as they are needed
            // to create the entry in the middle-table.
            $defaultValue = $primaryKey->getDefaultValueString();

            $phpType  = $primaryKey->getPhpType();
            $typeHint = $primaryKey->isPhpArrayType() ? 'array' : '';
            $name     = '$' . lcfirst($primaryKey->getPhpName());

            $normalizedShortSignature[] = $name;
            $signature[]                = ($typeHint ? "{$typeHint} " : '') . $name . ('null' !== $defaultValue ? " = {$defaultValue}" : '');
            $shortSignature[]           = $name;
            $phpDoc[]                   = "
     * @param {$phpType} {$name}";
        }
    }

    protected function getCrossFKsVarName(CrossForeignKeys $crossFKs): string
    {
        return 'coll' . $this->getCrossFKsPhpNameAffix($crossFKs);
    }

    protected function getCrossFKVarName(ForeignKey $crossFK): string
    {
        return 'coll' . $this->getFKPhpNameAffix($crossFK, true);
    }

    /**
     * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
     *
     * The related by suffix is based on the local columns of the foreign key. If there is more than
     * one column in a table that points to the same foreign table, then a 'RelatedByLocalColName' suffix
     * will be appended.
     *
     * @throws RuntimeException
     */
    protected static function getRelatedBySuffix(ForeignKey $fk): string
    {
        $relCol = '';

        foreach ($fk->getMapping() as $mapping) {
            [$localColumn, $foreignValueOrColumn] = $mapping;
            $localTable                           = $fk->getTable();

            if (!$localColumn) {
                throw new RuntimeException(sprintf('Could not resolve column of foreign key `%s` on table `%s`', $fk->getName(), $localTable->getName()));
            }

            $tableName        = $fk->getTableName();
            $foreignTableName = (string) $fk->getForeignTableName();

            if (
                count($localTable->getForeignKeysReferencingTable($foreignTableName))              > 1
                || count($fk->getForeignTableOrFail()->getForeignKeysReferencingTable($tableName)) > 0
                || $foreignTableName === $tableName
            ) {
                // self referential foreign key, or several foreign keys to the same table, or cross-reference fkey
                $relCol .= $localColumn->getPhpName();
            }
        }

        if ($relCol) {
            $relCol = 'RelatedBy' . $relCol;
        }

        return $relCol;
    }

    /**
     * Gets the PHP method name affix to be used for referencing foreign key methods and variable names (e.g. set????(), $coll???).
     *
     * The difference between this method and the getFKPhpNameAffix() method is that in this method the
     * classname in the affix is the classname of the local fkey table.
     *
     * @param ForeignKey $fk     the referrer FK that we need a name for
     * @param bool       $plural Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     */
    public function getRefFKPhpNameAffix(ForeignKey $fk, bool $plural = false): ?string
    {
        $pluralizer = $this->getPluralizer();

        if ($fk->getRefPhpName()) {
            return $plural ? $pluralizer->getPluralForm($fk->getRefPhpName()) : $fk->getRefPhpName();
        }

        $className = $fk->getTable()->getPhpName();

        if ($plural) {
            $className = $pluralizer->getPluralForm($className);
        }

        return $className . $this->getRefRelatedBySuffix($fk);
    }

    /**
     * @throws RuntimeException
     */
    protected static function getRefRelatedBySuffix(ForeignKey $fk): string
    {
        $relCol = '';

        foreach ($fk->getMapping() as $mapping) {
            [$localColumn, $foreignValueOrColumn] = $mapping;
            $localTable                           = $fk->getTable();

            if (!$localColumn) {
                throw new RuntimeException(sprintf('Could not resolve column of foreign key `%s` on table `%s`', $fk->getName(), $localTable->getName()));
            }

            $tableName                 = $fk->getTableName();
            $foreignTableName          = (string) $fk->getForeignTableName();
            $foreignKeysToForeignTable = $localTable->getForeignKeysReferencingTable($foreignTableName);

            if ($foreignValueOrColumn instanceof Column && $foreignTableName === $tableName) {
                $foreignColumnName = $foreignValueOrColumn->getPhpName();
                // self referential foreign key
                $relCol .= $foreignColumnName;

                if (count($foreignKeysToForeignTable) > 1) {
                    // several self-referential foreign keys
                    $relCol .= array_search($fk, $foreignKeysToForeignTable);
                }
            } elseif (count($foreignKeysToForeignTable) > 1 || count($fk->getForeignTableOrFail()->getForeignKeysReferencingTable($tableName)) > 0) {
                // several foreign keys to the same table, or symmetrical foreign key in foreign table
                $relCol .= $localColumn->getPhpName();
            }
        }

        if ($relCol) {
            $relCol = 'RelatedBy' . $relCol;
        }

        return $relCol;
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook.
     *
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param string $modifier The name of the modifier object providing the method in the behavior
     */
    public function hasBehaviorModifier(string $hookName, string $modifier): bool
    {
        $modifierGetter = 'get' . $modifier;

        foreach ($this->getTable()->getBehaviors() as $behavior) {
            if (method_exists($behavior->{$modifierGetter}(), $hookName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook.
     *
     * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
     * @param string $modifier The name of the modifier object providing the method in the behavior
     * @param string $script   the script will be modified in this method
     */
    public function applyBehaviorModifierBase(string $hookName, string $modifier, string &$script, string $tab = '        '): void
    {
        $modifierGetter = 'get' . $modifier;

        foreach ($this->getTable()->getBehaviors() as $behavior) {
            $modifier = $behavior->{$modifierGetter}();

            if (method_exists($modifier, $hookName)) {
                if (false !== strpos($hookName, 'Filter')) {
                    // filter hook: the script string will be modified by the behavior
                    $modifier->{$hookName}($script, $this);
                } else {
                    // regular hook: the behavior returns a string to append to the script string
                    $addedScript = $modifier->{$hookName}($this);

                    if (!$addedScript) {
                        continue;
                    }
                    $script .= '
' . $tab . '// ' . $behavior->getId() . ' behavior
';
                    $script .= preg_replace('/^/m', $tab, $addedScript);
                }
            }
        }
    }

    /**
     * Checks whether any registered behavior content creator on that table exists a contentName.
     *
     * @param string $contentName The name of the content as called from one of this class methods, e.g. "parentClassName"
     * @param string $modifier    The name of the modifier object providing the method in the behavior
     */
    public function getBehaviorContentBase(string $contentName, string $modifier): ?string
    {
        $modifierGetter = 'get' . $modifier;

        foreach ($this->getTable()->getBehaviors() as $behavior) {
            $modifier = $behavior->{$modifierGetter}();

            if (method_exists($modifier, $contentName)) {
                return $modifier->{$contentName}($this);
            }
        }

        return null;
    }

    /**
     * Use Propel simple templating system to render a PHP file using variables
     * passed as arguments. The template file name is relative to the behavior's
     * directory name.
     *
     * @throws InvalidArgumentException
     */
    public function renderTemplate(string $filename, array $vars = [], ?string $templatePath = null): string
    {
        if (null === $templatePath) {
            $templatePath = $this->getTemplatePath(__DIR__);
        }

        $filePath = $templatePath . $filename;

        if (!file_exists($filePath)) {
            // try with '.php' at the end
            $filePath = $filePath . '.php';

            if (!file_exists($filePath)) {
                throw new InvalidArgumentException(sprintf('Template `%s` not found in `%s` directory', $filename, $templatePath));
            }
        }
        $template = new PropelTemplate();
        $template->setTemplateFile($filePath);
        $vars = array_merge($vars, ['behavior' => $this]);

        return $template->render($vars);
    }

    public function getTableMapClass(): string
    {
        return $this->getStubObjectBuilder()->getUnqualifiedClassName() . 'TableMap';
    }

    /**
     * Most of the code comes from the PHP-CS-Fixer project.
     */
    private function clean(string $content): string
    {
        // line feed
        $content = str_replace("\r\n", "\n", $content);

        // trailing whitespaces
        $content = (string) preg_replace('/[ \t]*$/m', '', $content);

        // indentation
        $content = (string) preg_replace_callback('/^([ \t]+)/m', function ($matches) {
            return str_replace("\t", '    ', $matches[0]);
        }, $content);

        // Unused "use" statements
        preg_match_all('/^use (?P<class>[^\s;]+)(?:\s+as\s+(?P<alias>.*))?;/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (isset($match['alias'])) {
                $short = $match['alias'];
            } else {
                $parts = explode('\\', $match['class']);
                $short = array_pop($parts);
            }

            preg_match_all('/\b' . $short . '\b/i', str_replace($match[0] . "\n", '', $content), $m);

            if (!count($m[0])) {
                $content = str_replace($match[0] . "\n", '', $content);
            }
        }

        // end of line
        if (strlen($content) && "\n" != substr($content, -1)) {
            $content = $content . "\n";
        }

        return $content;
    }

    /**
     * Opens class.
     */
    abstract protected function addClassOpen(string &$script): void;

    /**
     * This method adds the contents of the generated class to the script.
     *
     * This method is abstract and should be overridden by the subclasses.
     *
     * Hint: Override this method in your subclass if you want to reorganize or
     * drastically change the contents of the generated object class.
     *
     * @param string $script the script will be modified in this method
     */
    abstract protected function addClassBody(string &$script): void;

    /**
     * Closes class.
     */
    abstract protected function addClassClose(string &$script): void;

    /**
     * Returns the vendor info from the table for the configured platform.
     */
    protected function getVendorInfo(): VendorInfo
    {
        $dbVendorId = $this->getPlatform()->getDatabaseType();

        return $this->getTable()->getVendorInfoForType($dbVendorId);
    }

    /**
     * @psalm-return 'true'|'false'
     *
     * @see \Propel\Generator\Model\VendorInfo::getUuidSwapFlagLiteral()
     */
    protected function getUuidSwapFlagLiteral(): string
    {
        return $this->getVendorInfo()->getUuidSwapFlagLiteral();
    }
}
