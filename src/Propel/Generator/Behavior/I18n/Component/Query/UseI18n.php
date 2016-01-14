<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n\Component\Query;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\NamingTool;


class UseI18n extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $behavior = $this->getBehavior();
        $i18nRelationName = $this->getRefRelationVarName($behavior->getI18nRelation());

        $body = "
return \$this
    ->joinI18n(\$locale, \$relationAlias, \$joinType)
    ->useQuery(\$relationAlias ? \$relationAlias : '$i18nRelationName', '{$this->getClassNameFromEntity($behavior->getI18nEntity())}');
";

        $this->addMethod('useI18nQuery')
            ->setDescription('Use the I18n relation query object')
            ->setDocblock('@see       useQuery()')
            ->addParameter(PhpParameter::create('locale')
                ->setType('string', "Locale to use for the join condition, e.g. 'fr_FR'")
                ->setDefaultValue($behavior->getDefaultLocale())
            )
            ->addParameter(PhpParameter::create('relationAlias')
                ->setType('string', 'optional alias for the relation')
                ->setDefaultValue(null)
            )
            ->addParameter(PhpParameter::create('joinType')
                ->setType('string')
                ->setDescription("Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.")
                ->setDefaultValue('LEFT JOIN')
            )
            ->setType($this->getQueryClassName(false), 'A secondary query class using the current class as primary query')
            ->setBody($body);
    }
}
