<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Relation;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Behavior\Validate\ValidateBehavior;

/**
 * Allows translation of text fields through transparent one-to-many
 * relationship.
 *
 * @author Francois Zaninotto
 */
class I18nBehavior extends Behavior
{
    use ComponentTrait;

    const DEFAULT_LOCALE = 'en_US';

    // default parameters value
    protected $parameters = array(
        'i18n_entity'      => '%ENTITYNAME%I18n',
        'i18n_fields'      => '',
        'i18n_relation_field' => null,
        'locale_field'     => 'locale',
        'locale_length'     => 5,
        'default_locale'    => null,
        'locale_alias'      => '',
    );

    protected $tableModificationOrder = 70;

    /**
     * @var \Propel\Generator\Model\Entity
     */
    protected $i18nEntity;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getEntities() as $entity) {
            if ($entity->hasBehavior('i18n') && !$entity->getBehavior('i18n')->getParameter('default_locale')) {
                $entity->getBehavior('i18n')->addParameter(array(
                    'name'  => 'default_locale',
                    'value' => $this->getParameter('default_locale'),
                ));
            }
        }
    }

    public function getDefaultLocale()
    {
        if (!$defaultLocale = $this->getParameter('default_locale')) {
            $defaultLocale = self::DEFAULT_LOCALE;
        }

        return $defaultLocale;
    }

    public function getI18nEntity()
    {
        return $this->i18nEntity;
    }

    public function getI18nRelation()
    {
        foreach ($this->i18nEntity->getRelations() as $relation) {
            if ($relation->getForeignEntityName() == $this->entity->getName()) {
                return $relation;
            }
        }
    }

    public function getLocaleField()
    {
        return $this->getI18nEntity()->getField($this->getLocaleFieldName());
    }

    public function getI18nFields()
    {
        $fields = array();
        $i18nEntity = $this->getI18nEntity();
        if ($fieldNames = $this->getI18nFieldNamesFromConfig()) {
            // Strategy 1: use the i18n_fields parameter
            foreach ($fieldNames as $fieldName) {
                $fields []= $i18nEntity->getField($fieldName);
            }
        } else {
            // strategy 2: use the fields of the i18n table
            // warning: does not work when database behaviors add fields to all entities
            // (such as timestampable behavior)
            foreach ($i18nEntity->getFields() as $field) {
                if (!$field->isPrimaryKey()) {
                    $fields []= $field;
                }
            }
        }

        return $fields;
    }

    public function replaceTokens($string)
    {
        $entity = $this->getEntity();

        return strtr($string, array(
            '%ENTITYNAME%' => $entity->getName(),
        ));
    }

    public function PostDelete(RepositoryBuilder $repositoryBuilder)
    {
        if (!$repositoryBuilder->getDatabase()->getPlatform()->supportsNativeDeleteTrigger() &&
            !$repositoryBuilder->getGeneratorConfig()->get()['generator']['objectModel']['emulateForeignKeyConstraints']) {
            return $this->applyComponent('PostDelete', $repositoryBuilder, $this);
        }
    }

    public function objectBuilderModification(ObjectBuilder $builder)
    {
        $this->applyComponent('Attributes', $builder);
        $this->applyComponent('Setters', $builder);
        $this->applyComponent('Getters', $builder);
        $this->applyComponent('RemoveTranslation', $builder);
        $this->applyComponent('ModifyAdder', $builder);
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        $this->applyComponent('Query\Join', $builder);
        $this->applyComponent('Query\UseI18n', $builder);
    }

    public function entityMapBuilderModification(EntityMapBuilder $builder)
    {
        $this->applyComponent('EntityMap\PopulateObject', $builder);
    }

    public function modifyEntity()
    {
        $this->addI18nEntity();
        $this->relateI18nEntityToMainEntity();
        $this->addLocaleFieldToI18n();
        $this->moveI18nFields();
    }

    protected function addI18nEntity()
    {
        $entity         = $this->getEntity();
        $database       = $entity->getDatabase();
        $i18nEntityName = $this->getI18nEntityName();

        if ($database->hasEntity($i18nEntityName)) {
            $this->i18nEntity = $database->getEntity($i18nEntityName);
        } else {
            $this->i18nEntity = $database->addEntity(array(
                'name'      => $i18nEntityName,
                'package'   => $entity->getPackage(),
                'schema'    => $entity->getSchema(),
                'namespace' => $entity->getNamespace() ? '\\' . $entity->getNamespace() : null,
                'skipSql'   => $entity->isSkipSql(),
                'identifierQuoting' => $entity->getIdentifierQuoting()
            ));

            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
        }
    }

    protected function relateI18nEntityToMainEntity()
    {
        $entity     = $this->getEntity();
        $i18nEntity = $this->i18nEntity;
        $pks       = $this->getEntity()->getPrimaryKey();

        if (count($pks) > 1) {
            throw new EngineException('The i18n behavior does not support entities with composite primary keys');
        }

        $field = $pks[0];
        $i18nField = clone $field;

        if ($this->getParameter('i18n_relation_field')) {
            // custom i18n table pk name
            $i18nField->setName($this->getParameter('i18n_relation_field'));
        } else if (in_array($entity->getName(), $i18nEntity->getForeignEntityNames())) {
            // custom i18n table pk name not set, but some fk already exists
            return;
        }

        if (!$i18nEntity->hasField($i18nField->getName())) {
            $i18nField->setAutoIncrement(false);
            $i18nEntity->addField($i18nField);
        }

        $relation = new Relation();
        $relation->setForeignEntityName($entity->getName());
        $relation->setDefaultJoin('LEFT JOIN');
        $relation->setOnDelete(Relation::CASCADE);
        $relation->setOnUpdate(Relation::NONE);
        $relation->addReference($i18nField->getName(), $field->getName());

        $i18nEntity->addRelation($relation);

        $this->relation = $relation;
    }

    protected function addLocaleFieldToI18n()
    {
        $localeFieldName = $this->getLocaleFieldName();

        if (!$this->i18nEntity->hasField($localeFieldName)) {
            $this->i18nEntity->addField(array(
                'name'       => $localeFieldName,
                'type'       => PropelTypes::VARCHAR,
                'size'       => $this->getParameter('locale_length') ? (int) $this->getParameter('locale_length') : 5,
                'default'    => $this->getDefaultLocale(),
                'primaryKey' => true
            ));
        }
    }

    /**
     * Moves i18n fields from the main table to the i18n table
     */
    protected function moveI18nFields()
    {
        $entity     = $this->getEntity();
        $i18nEntity = $this->i18nEntity;

        $i18nValidateParams = array();
        foreach ($this->getI18nFieldNamesFromConfig() as $fieldName) {
            if (!$i18nEntity->hasField($fieldName)) {
                if (!$entity->hasField($fieldName)) {
                    throw new EngineException(sprintf('No field named %s found in table %s', $fieldName, $entity->getName()));
                }

                $field = $entity->getField($fieldName);
                $i18nEntity->addField(clone $field);
                // FIXME: also move FKs, and indices on this field
            }

            if ($entity->hasField($fieldName)) {
                $entity->removeField($fieldName);
            }
        }
    }

    protected function getI18nEntityName()
    {
        return $this->replaceTokens($this->getParameter('i18n_entity'));
    }

    protected function getLocaleFieldName()
    {
        return $this->replaceTokens($this->getParameter('locale_field'));
    }

    public function getI18nFieldNamesFromConfig()
    {
        $fieldNames = explode(',', $this->getParameter('i18n_fields'));
        foreach ($fieldNames as $key => $fieldName) {
            if ($fieldName = trim($fieldName)) {
                $fieldNames[$key] = $fieldName;
            } else {
                unset($fieldNames[$key]);
            }
        }

        return $fieldNames;
    }
}
