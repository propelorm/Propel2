<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\ConcreteInheritance;

use gossi\codegen\model\PhpMethod;
use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
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
    // default parameters value
    protected $parameters = array(
        'descendant_field' => 'descendantClass'
    );

    public function modifyEntity()
    {
        $table = $this->getEntity();
        if (!$table->hasField($this->getParameter('descendant_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('descendant_field'),
                'type' => 'VARCHAR',
                'size' => 100
            ));
        }
    }

    protected function getFieldGetter()
    {
        return 'get' . $this->getFieldForParameter('descendant_field')->getMethodName();
    }

    public function activeRecordTraitBuilderModification(ActiveRecordTraitBuilder $builder)
    {
        $hasChildObject = new PhpMethod('hasChildObject');
        $hasChildObject->setDescription('Whether or not this object is the parent of a child object');
        $hasChildObject->setBody("
return \$this->getRepository()->hasChildObject(\$this);
");
        $builder->getDefinition()->setMethod($hasChildObject);


        $getChildObject = new PhpMethod('getChildObject');
        $hasChildObject->setDescription('Get the child object of this object');
        $getChildObject->setBody("
return \$this->getRepository()->getChildObject(\$this);
");
        $builder->getDefinition()->setMethod($getChildObject);
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $body = "
return null !== \$this->getChildObject(\$entity);
";
        $hasChildObject = PhpMethod::create('hasChildObject')
            ->setDescription('Whether or not this object is the parent of a child object')
            ->addSimpleParameter('entity')
            ->setBody($body);
        $builder->getDefinition()->setMethod($hasChildObject);

        $body = "
if (!\$entity->{$this->getFieldGetter()}()) {
    return null;
}

\$childRepository = \$this->getConfiguration()->getRepository(\$entity->{$this->getFieldGetter()}());
\$childObject = \$childRepository
    ->createQuery()
    ->findPk(\$this->getEntityMap()->getPrimaryKey(\$entity));
return \$childObject;
";
        $getChildObject = PhpMethod::create('getChildObject')
            ->setDescription('Get the child object of this object')
            ->addSimpleParameter('entity')
            ->setBody($body);
        $builder->getDefinition()->setMethod($getChildObject);
    }
}
