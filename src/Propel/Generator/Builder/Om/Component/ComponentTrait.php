<?php


namespace Propel\Generator\Builder\Om\Component;

use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Behavior;

trait ComponentTrait
{
    /**
     * @param string          $className fqcn or relative to current '__NAMESPACE__\\Component\\' of $this unless prefixed with \\.
     * @param AbstractBuilder $builder
     * @param Behavior        $behavior
     *
     * @return mixed
     */
    protected function applyComponent($className, AbstractBuilder $builder = null, Behavior $behavior = null)
    {
        if ('\\' !== $className[0]) {
            $reflection = new \ReflectionClass($this);
            $namespace = $reflection->getNamespaceName();
            $className = $namespace . '\\Component\\' . $className;
        }

        if (null === $builder && method_exists($this, 'getBuilder')) {
            $builder = $this->getBuilder();
        }

        if (null == $behavior && $this instanceof Behavior) {
            $behavior = $this;
        }

        /** @var BuildComponent $instance */
        $instance = new $className($builder, $behavior);

        $args = func_get_args();
        array_shift($args); //shift $className away
        array_shift($args); //shift $builder away
        array_shift($args); //shift $behavior away

        return call_user_func_array([$instance, 'process'], $args);
    }
}