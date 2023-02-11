<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\ConcreteInheritance;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Symmetrical behavior of the concrete_inheritance. When model A extends model B,
 * model A gets the concrete_inheritance behavior, and model B gets the
 * concrete_inheritance_parent
 *
 * @author François Zaninotto
 */
class ConcreteInheritanceParentBehavior extends Behavior
{
    /**
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    protected $builder;

    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        'descendant_column' => 'descendant_class',
    ];

    /**
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();
        if (!$table->hasColumn($this->getParameter('descendant_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('descendant_column'),
                'type' => 'VARCHAR',
                'size' => 100,
            ]);
        }
    }

    /**
     * @return string
     */
    protected function getColumnGetter(): string
    {
        return 'get' . $this->getColumnForParameter('descendant_column')->getPhpName();
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
    public function objectMethods(ObjectBuilder $builder): string
    {
        $this->builder = $builder;
        $this->builder->declareClasses('Propel\Runtime\ActiveQuery\PropelQuery');
        $script = '';
        $this->addHasChildObject($script);
        $this->addGetChildObject($script);

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addHasChildObject(string &$script): void
    {
        $script .= "
/**
 * Whether this object is the parent of a child object
 *
 * @return bool
 */
public function hasChildObject(): bool
{
    return \$this->" . $this->getColumnGetter() . "() !== null;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetChildObject(string &$script): void
    {
        $script .= "
/**
 * Get the child object of this object
 *
 * @return mixed
 */
public function getChildObject()
{
    if (!\$this->hasChildObject()) {
        return null;
    }
    \$childObjectClass = \$this->" . $this->getColumnGetter() . "();
    \$childObject = PropelQuery::from(\$childObjectClass)->findPk(\$this->getPrimaryKey());

    return \$childObject->hasChildObject() ? \$childObject->getChildObject() : \$childObject;
}
";
    }
}
