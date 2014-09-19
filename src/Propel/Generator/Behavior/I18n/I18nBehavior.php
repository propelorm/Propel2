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
 * Allows translation of text columns through transparent one-to-many
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
        'i18n_columns'      => '',
        'i18n_pk_column'    => null,
        'locale_column'     => 'locale',
        'locale_length'     => 5,
        'default_locale'    => null,
        'locale_alias'      => '',
    );

    protected $tableModificationOrder = 70;

    protected $objectBuilderModifier;

    protected $queryBuilderModifier;

    protected $i18nTable;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
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

    public function getI18nTable()
    {
        return $this->i18nTable;
    }

    public function getI18nForeignKey()
    {
        foreach ($this->i18nTable->getForeignKeys() as $fk) {
            if ($fk->getForeignTableName() == $this->table->getName()) {
                return $fk;
            }
        }
    }

    public function getLocaleColumn()
    {
        return $this->getI18nTable()->getColumn($this->getLocaleColumnName());
    }

    public function getI18nColumns()
    {
        $columns = array();
        $i18nTable = $this->getI18nTable();
        if ($columnNames = $this->getI18nColumnNamesFromConfig()) {
            // Strategy 1: use the i18n_columns parameter
            foreach ($columnNames as $columnName) {
                $columns []= $i18nTable->getColumn($columnName);
            }
        } else {
            // strategy 2: use the columns of the i18n table
            // warning: does not work when database behaviors add columns to all tables
            // (such as timestampable behavior)
            foreach ($i18nTable->getColumns() as $column) {
                if (!$column->isPrimaryKey()) {
                    $columns []= $column;
                }
            }
        }

        return $columns;
    }

    public function replaceTokens($string)
    {
        $table = $this->getTable();

        return strtr($string, array(
            '%TABLE%'   => $table->getOriginCommonName(),
            '%PHPNAME%' => $table->getPhpName(),
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

    public function modifyTable()
    {
        $this->addI18nTable();
        $this->relateI18nTableToMainTable();
        $this->addLocaleColumnToI18n();
        $this->moveI18nColumns();
    }

    protected function addI18nTable()
    {
        $table         = $this->getTable();
        $database      = $table->getDatabase();
        $i18nTableName = $this->getI18nTableName();

        if ($database->hasTable($i18nTableName)) {
            $this->i18nTable = $database->getTable($i18nTableName);
        } else {
            $this->i18nTable = $database->addTable(array(
                'name'      => $i18nTableName,
                'phpName'   => $this->getI18nTablePhpName(),
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

    protected function relateI18nTableToMainTable()
    {
        $table     = $this->getTable();
        $i18nTable = $this->i18nTable;
        $pks       = $this->getTable()->getPrimaryKey();

        if (count($pks) > 1) {
            throw new EngineException('The i18n behavior does not support tables with composite primary keys');
        }

        $column = $pks[0];
        $i18nColumn = clone $column;

        if ($this->getParameter('i18n_pk_column')) {
            // custom i18n table pk name
            $i18nColumn->setName($this->getParameter('i18n_pk_column'));
        } else if (in_array($table->getName(), $i18nTable->getForeignTableNames())) {
            // custom i18n table pk name not set, but some fk already exists
            return;
        }

        if (!$i18nTable->hasColumn($i18nColumn->getName())) {
            $i18nColumn->setAutoIncrement(false);
            $i18nTable->addColumn($i18nColumn);
        }

        $fk = new ForeignKey();
        $fk->setForeignTableCommonName($table->getCommonName());
        $fk->setForeignSchemaName($table->getSchema());
        $fk->setDefaultJoin('LEFT JOIN');
        $fk->setOnDelete(ForeignKey::CASCADE);
        $fk->setOnUpdate(ForeignKey::NONE);
        $fk->addReference($i18nColumn->getName(), $column->getName());

        $i18nTable->addForeignKey($fk);
    }

    protected function addLocaleColumnToI18n()
    {
        $localeColumnName = $this->getLocaleColumnName();

        if (! $this->i18nTable->hasColumn($localeColumnName)) {
            $this->i18nTable->addColumn(array(
                'name'       => $localeColumnName,
                'type'       => PropelTypes::VARCHAR,
                'size'       => $this->getParameter('locale_length') ? (int) $this->getParameter('locale_length') : 5,
                'default'    => $this->getDefaultLocale(),
                'primaryKey' => 'true',
            ));
        }
    }

    /**
     * Moves i18n columns from the main table to the i18n table
     */
    protected function moveI18nColumns()
    {
        $table     = $this->getTable();
        $i18nTable = $this->i18nTable;

        $i18nValidateParams = array();
        foreach ($this->getI18nColumnNamesFromConfig() as $columnName) {
            if (!$i18nTable->hasColumn($columnName)) {
                if (!$table->hasColumn($columnName)) {
                    throw new EngineException(sprintf('No column named %s found in table %s', $columnName, $table->getName()));
                }

                $column = $table->getColumn($columnName);
                $i18nTable->addColumn(clone $column);

                // validate behavior: move rules associated to the column
                if ($table->hasBehavior('validate')) {
                    $validateBehavior = $table->getBehavior('validate');
                    $params = $validateBehavior->getParametersFromColumnName($columnName);
                    $i18nValidateParams = array_merge($i18nValidateParams, $params);
                    $validateBehavior->removeParametersFromColumnName($columnName);
                }
                // FIXME: also move FKs, and indices on this column
            }

            if ($table->hasColumn($columnName)) {
                $table->removeColumn($columnName);
            }
        }

        // validate behavior
        if (count($i18nValidateParams) > 0) {
            $i18nVbehavior = new ValidateBehavior();
            $i18nVbehavior->setName('validate');
            $i18nVbehavior->setParameters($i18nValidateParams);
            $i18nTable->addBehavior($i18nVbehavior);

            // current table must have almost 1 validation rule
            $validate = $table->getBehavior('validate');
            $validate->addRuleOnPk();
        }
    }

    protected function getI18nTableName()
    {
        return $this->replaceTokens($this->getParameter('i18n_table'));
    }

    protected function getI18nTablePhpName()
    {
        return $this->replaceTokens($this->getParameter('i18n_phpname'));
    }

    protected function getLocaleColumnName()
    {
        return $this->replaceTokens($this->getParameter('locale_column'));
    }

    protected function getI18nColumnNamesFromConfig()
    {
        $columnNames = explode(',', $this->getParameter('i18n_columns'));
        foreach ($columnNames as $key => $columnName) {
            if ($columnName = trim($columnName)) {
                $columnNames[$key] = $columnName;
            } else {
                unset($columnNames[$key]);
            }
        }

        return $columnNames;
    }
}
