<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Adds initialize method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class InitializeMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $entity = $this->getEntity();
        $platform = $this->getPlatform();

        $body = "
        parent::initialize();

        \$this->setName('" . $entity->getName() . "');
        \$this->setDatabaseName('" . $entity->getDatabase()->getName() . "');
        \$this->setFullClassName('" . $entity->getFullClassName() . "');
        \$this->setTableName('" . $entity->getTableName() . "');
        \$this->setAllowPkInsert(". ($entity->isAllowPkInsert() ? 'true' : 'false') . ");
        \$this->setIdentifierQuoting(" . ($entity->isIdentifierQuotingEnabled() ? 'true' : 'false') . ");
        \$this->setAutoIncrement(" . ($entity->hasAutoIncrement() ? 'true' : 'false') . ");
        \$this->setReloadOnInsert(" . ($entity->isReloadOnInsert() ? 'true' : 'false') . ");
        \$this->setReloadOnUpdate(" . ($entity->isReloadOnUpdate() ? 'true' : 'false') . ");
        ";

        if ($entity->getIdMethod() == "native") {
            $body .= "
        \$this->setUseIdGenerator(true);";
        } else {
            $body .= "
        \$this->setUseIdGenerator(false);";
        }

        if ($entity->getIdMethodParameters()) {
            $params = $entity->getIdMethodParameters();
            $imp = $params[0];
            $body .= "
        \$this->setPrimaryKeyMethodInfo('" . $imp->getValue() . "');";
        } elseif ($entity->getIdMethod() == IdMethod::NATIVE && ($platform->getNativeIdMethod(
                ) == PlatformInterface::SEQUENCE || $platform->getNativeIdMethod() == PlatformInterface::SERIAL)
        ) {
            $body .= "
        \$this->setPrimaryKeyMethodInfo('" . $platform->getSequenceName($entity) . "');";
        }

        if ($this->getEntity()->getChildrenField()) {
            $body .= "
        \$this->setSingleEntityInheritance(true);";
        }

        if ($this->getEntity()->getIsCrossRef()) {
            $body .= "
        \$this->setIsCrossRef(true);";
        }

        $body .= "
        \$this->buildFields();
        ";

        $this->addMethod('initialize')
            ->setBody($body);
    }
}