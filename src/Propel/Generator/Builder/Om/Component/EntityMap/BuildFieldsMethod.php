<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Adds buildFields method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildFieldsMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = "";

        $this->getDefinition()->declareUse('Propel\Runtime\Map\RelationMap');

        foreach ($this->getEntity()->getFields() as $field) {
            $fieldName = $field->getName();
            $columnName = $field->getColumnName();
            if (!$columnName) {
                $columnName = $this->getPlatform()->getName($field);
            }

            $implementationDetail = $field->isImplementationDetail() ? 'true' : 'false';
            if (!$field->getSize()) {
                $size = "null";
            } else {
                $size = $field->getSize();
            }
            $default = $field->getDefaultValueString();
            if ($field->isPrimaryKey()) {
                if ($field->isRelation()) {
                    foreach ($field->getRelations() as $fk) {
                        $body .= "
\$this->addForeignPrimaryKey(
    '$fieldName',
    '" . $field->getType() . "',
    '" . $fk->getForeignEntityName() . "',
    '" . $fk->getMappedForeignField($field->getName()) . "',
    " . ($field->isNotNull() ? 'true' : 'false') . ",
    " . $size . ",
    $default
);";
                    }
                } else {
                    $body .= "
\$this->addPrimaryKey(
    '$fieldName',
    '" . $field->getType() . "',
    " . var_export($field->isNotNull(), true) . ",
    ". $size . ",
    $default,
    $implementationDetail
);";
                }
            } else {
                if ($field->isRelation()) {
                    foreach ($field->getRelations() as $fk) {
                        $body .= "
\$this->addForeignKey(
    '$fieldName',
    '" . $field->getType() . "',
    '" . $fk->getForeignEntityName() . "',
    '" . $fk->getMappedForeignField($field->getName()) . "',
    " . ($field->isNotNull() ? 'true' : 'false') . ",
    " . $size . ",
    $default
);";
                    }
                } else {
                    $body .= "
\$this->addField(
    '$fieldName',
    '" . $field->getType() . "',
    " . var_export($field->isNotNull(), true) . ",
    " . $size . ",
    $default,
    $implementationDetail
);";
                }
            } // if col-is prim key
            if ($field->isEnumType()) {
                $body .= "
\$this->getField('$fieldName')->setValueSet(" . var_export($field->getValueSet(), true) . ");";
            }
            if ($field->isPrimaryString()) {
                $body .= "
\$this->getField('$fieldName')->setPrimaryString(true);";
            }
            if ($field->isAutoIncrement()) {
                $body .= "
\$this->getField('$fieldName')->setAutoIncrement(true);";
            }
            if ($field->isLazyLoad()) {
                $body .= "
\$this->getField('$fieldName')->setLazyLoad(true);";
            }

            $body .= "
\$this->getField('$fieldName')->setColumnName('$columnName');";
        } // foreach

        $this->addMethod('buildFields')
            ->setBody($body);
    }
}