<?php


namespace Propel\Generator\Builder\Om\Component\MultiExtendObject;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Inheritance;

/**
 * Adds the __construct method.
 *
 * @todo, remove that. we need to handle that directly through class names in the populator.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Constructor extends BuildComponent
{
    use NamingTrait;


    public function process()
    {
        /** @var Inheritance $child */
        $child = $this->getBuilder()->getChild();
        $col = $child->getField();
        $cfc = $col->getName();

        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));

        $const = "CLASSKEY_".strtoupper($child->getKey());

        $body = <<<EOF
parent::__construct();
\$this->set$cfc({$this->getEntityMapClassName()}::$const);
EOF;

        $this->addMethod('__construct')
            ->setDescription("Constructs a new {$child->getClassName()} class, setting the {$col->getName()} column to {$this->getEntityMapClassName()}::$const.")
            ->setBody($body);
    }
}