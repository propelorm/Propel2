<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n\Component\Query;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\RelationTrait;

class Join extends BuildComponent
{
    use RelationTrait;

    public function process()
    {
        $i18nRelationName = $this->getRefRelationVarName($this->getBehavior()->getI18nRelation());

        $this->addJoinI18n($i18nRelationName);
        $this->addJoinWithI18n($i18nRelationName);
    }

    private function addJoinI18n($i18nRelationName)
    {
        $field = $this->getBehavior()->getLocaleField();
        $entityName = $field->getEntity()->getName();
        $columnName = $field->getSqlName();

        $body = "
\$relationName = \$relationAlias ? \$relationAlias : '$i18nRelationName';

\$entityName = \$relationAlias ? \$relationAlias : '$entityName';

return \$this
    ->join{$i18nRelationName}(\$relationAlias, \$joinType)
    ->addJoinCondition(\$relationName, \$entityName . '.{$columnName} = ?', \$locale, null, {$field->getPDOType()});
";

        $this->addMethod('joinI18n')
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the join condition, e.g. 'fr_FR'", $this->getBehavior()->getDefaultLocale())
            ->addSimpleDescParameter('relationAlias', 'string', 'optional alias for the relation', null)
            ->addSimpleDescParameter('joinType', 'string', "Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.", 'LEFT JOIN')
            ->setType('$this|' . $this->getBuilder()->getClassName(), 'The current query, for fluid interface')
            ->setDescription('Adds a JOIN clause to the query using the i18n relation')
            ->setBody($body);
    }

    private function addJoinWithI18n($i18nRelationName)
    {
        $body = "
\$this
        ->joinI18n(\$locale, null, \$joinType)
        ->with('$i18nRelationName');
    \$this->with['$i18nRelationName']->setIsWithOneToMany(false);

    return \$this;
";

        $this->addMethod('joinWithI18n')
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the join condition, e.g. 'fr_FR'", $this->getBehavior()->getDefaultLocale())
            ->addSimpleDescParameter('joinType', 'string', "Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.", 'LEFT JOIN')
            ->setType('$this|' . $this->getBuilder()->getClassName(), 'The current query, for fluid interface')
            ->setDescription("
 Adds a JOIN clause to the query and hydrates the related I18n object.
 Shortcut for \$c->joinI18n(\$locale)->with()
 ")
            ->setBody($body);
    }
}
