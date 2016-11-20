<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Model\Relation;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Generates the PHP5 entity map class for user object model (OM).
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class EntityMapBuilder extends AbstractBuilder
{
    /**
     * @param string $injectNamespace
     *
     * @return string
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        $injectNamespace = 'Map';

        if ($this->getGeneratorConfig() &&
            $customNameSpace = $this->getBuildProperty('generator.objectModel.namespaceMap')) {

            $injectNamespace = $customNameSpace;
        }

        return parent::getFullClassName($injectNamespace) . 'EntityMap';
    }

    public function buildClass()
    {
        $this->getDefinition()->declareUses(
            '\Propel\Runtime\EntityMap'
        );
        $this->getDefinition()->setParentClassName('\Propel\Runtime\Map\EntityMap');

        $this->applyComponent('EntityMap\\Constants');
        $this->applyComponent('EntityMap\\ColConstants');
        $this->applyComponent('EntityMap\\FieldStaticProperties');
        $this->applyComponent('EntityMap\\GetRepositoryClassMethod');
        $this->applyComponent('EntityMap\\InitializeMethod');
        $this->applyComponent('EntityMap\\BuildRelationsMethod');
        $this->applyComponent('EntityMap\\BuildFieldsMethod');
        $this->applyComponent('EntityMap\\BuildSqlBulkInsertPartMethod');
        $this->applyComponent('EntityMap\\BuildSqlPrimaryConditionMethod');
        $this->applyComponent('EntityMap\\GetAutoIncrementFieldNamesMethod');
        $this->applyComponent('EntityMap\\GetPrimaryKeyMethod');
        $this->applyComponent('EntityMap\\PopulateAutoIncrementFieldsMethod');
        $this->applyComponent('EntityMap\\PopulateDependencyGraphMethod');
        $this->applyComponent('EntityMap\\PersistDependenciesMethod');
        $this->applyComponent('EntityMap\\GetPropReaderMethod');
        $this->applyComponent('EntityMap\\GetPropWriterMethod');
        $this->applyComponent('EntityMap\\GetPropIssetMethod');
        $this->applyComponent('EntityMap\\GetPropUnsetterMethod');
        $this->applyComponent('EntityMap\\GetBehaviorsMethod');
        $this->applyComponent('EntityMap\\PopulateObjectMethod');
        $this->applyComponent('EntityMap\\GetSnapshotMethod');
        $this->applyComponent('EntityMap\\IsValidRowMethod');
        $this->applyComponent('EntityMap\\AddSelectFieldsMethod');
        $this->applyComponent('EntityMap\\BuildChangeSetMethod');
        $this->applyComponent('EntityMap\\BuildPkeyCriteriaMethod');

        $this->applyComponent('EntityMap\\GenericAccessorMethods');
        $this->applyComponent('EntityMap\\GenericMutatorMethods');

        $this->applyComponent('EntityMap\\CopyIntoMethod');
    }
}
