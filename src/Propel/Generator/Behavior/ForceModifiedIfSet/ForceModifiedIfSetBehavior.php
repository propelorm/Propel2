<?php

namespace Propel\Generator\Behavior\ForceModifiedIfSet;

use Propel\Generator\Model\Behavior;

/**
 * @author Ansas Meyer
 */
class ForceModifiedIfSetBehavior extends Behavior
{
    /**
     * @return string the PHP code to be added to the builder
     */
    public function objectAttributes($builder)
    {
        return "protected bool \$forceModifiedIfSet = false;\n";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectMethods($builder)
    {
        return "
/**
 * @return \$this|" . $builder->getObjectClassName() . " The current object (for fluent API support)
 */
public function forceModifiedIfSet(bool \$force = true)
{
    \$this->forceModifiedIfSet = \$force;

    return \$this;
}

public function isModifiedIfSet(): bool
{
    return \$this->forceModifiedIfSet;
}
";
    }

    /**
     * @param string $script
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return void
     */
    public function objectFilter(&$script, $builder)
    {
        foreach ($builder->getTable()->getColumns() as $column) {
            $pattern = "/public function set{$column->getPhpName()}.*[\\r\\n]\\s*\\{/";
            $addition = "
        if (\$this->forceModifiedIfSet) {
            \$this->modifiedColumns[" . $builder->getColumnConstant($column) . "] = true;
        }
        ";
            $replacement = "\$0$addition";
            $script = preg_replace($pattern, $replacement, $script);
        }
    }
}
