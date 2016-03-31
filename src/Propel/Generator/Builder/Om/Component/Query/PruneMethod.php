<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Query;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds all filterBy methods for fields.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PruneMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();
        $objectName = '$' . $entity->getCamelCaseName();

        $body = "
if ($objectName) {";

        $pks = $entity->getPrimaryKey();
        if (count($pks) > 1) {
            $i = 0;
            $conditions = array();
            foreach ($pks as $col) {
                $condName = "'pruneCond" . $i . "'";
                $conditions[]= $condName;
                $body .= "
    \$this->addCond(". $condName . ", \$this->getAliasedColName({$col->getFQConstantName()}), " . $objectName . "->get" . ucfirst($col->getName()) . "(), Criteria::NOT_EQUAL);";
                $i++;
            }
            $conditionsString = implode(', ', $conditions);
            $body .= "
    \$this->combine(array(" . $conditionsString . "), Criteria::LOGICAL_OR);";
        } elseif ($entity->hasPrimaryKey()) {
            $col = $pks[0];
            $body .= "
    \$this->addUsingAlias({$col->getFQConstantName()}, " . $objectName . "->get" . ucfirst($col->getName()) . "(), Criteria::NOT_EQUAL);";
        } else {
            $this->getDefinition()->addUseStatement('Propel\\Runtime\\Exception\\LogicException');
            $body .= "
    throw new LogicException('$objectName object has no primary key (class {$this->getObjectClassName()})');
";
        }
        $body .= "
}

return \$this;
";
        $this->addMethod('prune')
            ->setDescription('Exclude object from result.')
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($entity->getCamelCaseName(), null, "Object to remove from the list of results", null)
            ->setBody($body)
        ;
    }
}
