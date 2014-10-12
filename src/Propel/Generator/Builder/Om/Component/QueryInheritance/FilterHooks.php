<?php


namespace Propel\Generator\Builder\Om\Component\QueryInheritance;


use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\QueryInheritanceBuilder;

class FilterHooks extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = $this->getClassKeyCondition();

        $this->addMethod('preDelete')
            ->setBody($body);

        $this->addMethod('preSelect')
            ->setBody($body);

        $valuesParam = new PhpParameter('values');
        $valuesParam->setPassedByReference(true);

        $this->addMethod('preUpdate')
            ->addParameter($valuesParam)
            ->addSimpleParameter('forceIndividualSaves', 'boolean', false)
            ->setBody($body);
    }

    protected function getClassKeyCondition()
    {
        /** @var QueryInheritanceBuilder $builder */
        $builder = $this->getBuilder();
        $child = $builder->getChild();
        $col = $child->getField();

        return "\$this->addUsingAlias(" . $col->getFQConstantName() . ", "
        . $this->getEntityMapClassName() . "::CLASSKEY_" . strtoupper($child->getKey()) . ");";
    }
}