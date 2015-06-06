<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\ForeignKey;
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
    const DEFAULT_LOCALE = 'en_US';

    // default parameters value
    protected $parameters = array(
        'i18n_table'        => '%TABLE%_i18n',
        'i18n_phpname'      => '%PHPNAME%I18n',
        'i18n_fields'      => '',
        'i18n_pk_field'    => null,
        'locale_field'     => 'locale',
        'locale_length'     => 5,
        'default_locale'    => null,
        'locale_alias'      => '',
    );

    protected $tableModificationOrder = 70;

    protected $objectBuilderModifier;

    protected $queryBuilderModifier;

    protected $i18nEntity;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getEntities() as $table) {
            if ($table->hasBehavior('i18n') && !$table->getBehavior('i18n')->getParameter('default_locale')) {
                $table->getBehavior('i18n')->addParameter(array(
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

    public function getI18nForeignKey()
    {
        foreach ($this->i18nEntity->getForeignKeys() as $fk) {
            if ($fk->getForeignEntityName() == $this->table->getName()) {
                return $fk;
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
        $table = $this->getEntity();

        return strtr($string, array(
            '%TABLE%'   => $table->getOriginCommonName(),
            '%PHPNAME%' => $table->getName(),
        ));
    }

    public function getObjectBuilderModifier()
    {
        if (null === $this->objectBuilderModifier) {
            $this->objectBuilderModifier = new I18nBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    public function getQueryBuilderModifier()
    {
        if (null === $this->queryBuilderModifier) {
            $this->queryBuilderModifier = new I18nBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }

    public function staticAttributes($builder)
    {
        return $this->renderTemplate('staticAttributes', array(
            'defaultLocale' => $this->getDefaultLocale(),
        ));
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
        $table         = $this->getEntity();
        $database      = $table->getDatabase();
        $i18nEntityName = $this->getI18nEntityName();

        if ($database->hasEntity($i18nEntityName)) {
            $this->i18nEntity = $database->getEntity($i18nEntityName);
        } else {
            $this->i18nEntity = $database->addEntity(array(
                'name'      => $i18nEntityName,
                'phpName'   => $this->getI18nEntityPhpName(),
                'package'   => $table->getPackage(),
                'schema'    => $table->getSchema(),
                'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
                'skipSql'   => $table->isSkipSql(),
                'identifierQuoting' => $table->getIdentifierQuoting()
            ));

            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
        }
    }

    protected function relateI18nEntityToMainEntity()
    {
        $table     = $this->getEntity();
        $i18nEntity = $this->i18nEntity;
        $pks       = $this->getEntity()->getPrimaryKey();

        if (count($pks) > 1) {
            throw new EngineException('The i18n behavior does not support entities with composite primary keys');
        }

        $field = $pks[0];
        $i18nField = clone $field;

        if ($this->getParameter('i18n_pk_field')) {
            // custom i18n table pk name
            $i18nField->setName($this->getParameter('i18n_pk_field'));
        } else if (in_array($table->getName(), $i18nEntity->getForeignEntityNames())) {
            // custom i18n table pk name not set, but some fk already exists
            return;
        }

        if (!$i18nEntity->hasField($i18nField->getName())) {
            $i18nField->setAutoIncrement(false);
            $i18nEntity->addField($i18nField);
        }

        $fk = new ForeignKey();
        $fk->setForeignEntityCommonName($table->getCommonName());
        $fk->setForeignSchemaName($table->getSchema());
        $fk->setDefaultJoin('LEFT JOIN');
        $fk->setOnDelete(ForeignKey::CASCADE);
        $fk->setOnUpdate(ForeignKey::NONE);
        $fk->addReference($i18nField->getName(), $field->getName());

        $i18nEntity->addForeignKey($fk);
    }

    protected function addLocaleFieldToI18n()
    {
        $localeFieldName = $this->getLocaleFieldName();

        if (! $this->i18nEntity->hasField($localeFieldName)) {
            $this->i18nEntity->addField(array(
                'name'       => $localeFieldName,
                'type'       => PropelTypes::VARCHAR,
                'size'       => $this->getParameter('locale_length') ? (int) $this->getParameter('locale_length') : 5,
                'default'    => $this->getDefaultLocale(),
                'primaryKey' => 'true',
            ));
        }
    }

    /**
     * Moves i18n fields from the main table to the i18n table
     */
    protected function moveI18nFields()
    {
        $table     = $this->getEntity();
        $i18nEntity = $this->i18nEntity;

        $i18nValidateParams = array();
        foreach ($this->getI18nFieldNamesFromConfig() as $fieldName) {
            if (!$i18nEntity->hasField($fieldName)) {
                if (!$table->hasField($fieldName)) {
                    throw new EngineException(sprintf('No field named %s found in table %s', $fieldName, $table->getName()));
                }

                $field = $table->getField($fieldName);
                $i18nEntity->addField(clone $field);

                // validate behavior: move rules associated to the field
                if ($table->hasBehavior('validate')) {
                    $validateBehavior = $table->getBehavior('validate');
                    $params = $validateBehavior->getParametersFromFieldName($fieldName);
                    $i18nValidateParams = array_merge($i18nValidateParams, $params);
                    $validateBehavior->removeParametersFromFieldName($fieldName);
                }
                // FIXME: also move FKs, and indices on this field
            }

            if ($table->hasField($fieldName)) {
                $table->removeField($fieldName);
            }
        }

        // validate behavior
        if (count($i18nValidateParams) > 0) {
            $i18nVbehavior = new ValidateBehavior();
            $i18nVbehavior->setName('validate');
            $i18nVbehavior->setParameters($i18nValidateParams);
            $i18nEntity->addBehavior($i18nVbehavior);

            // current table must have almost 1 validation rule
            $validate = $table->getBehavior('validate');
            $validate->addRuleOnPk();
        }
    }

    protected function getI18nEntityName()
    {
        return $this->replaceTokens($this->getParameter('i18n_table'));
    }

    protected function getI18nEntityPhpName()
    {
        return $this->replaceTokens($this->getParameter('i18n_phpname'));
    }

    protected function getLocaleFieldName()
    {
        return $this->replaceTokens($this->getParameter('locale_field'));
    }

    protected function getI18nFieldNamesFromConfig()
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
