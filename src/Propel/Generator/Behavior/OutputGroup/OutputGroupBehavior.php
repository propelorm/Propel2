<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\OutputGroup;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\MappingModel;
use Propel\Generator\Model\Table;

class OutputGroupBehavior extends Behavior
{
    /**
     * @var string
     */
    public const PARAMETER_OBJECT_COLLECTION_CLASS = 'object_collection_class';

    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        self::PARAMETER_OBJECT_COLLECTION_CLASS => null,
    ];

    /**
     * @var \Propel\Generator\Behavior\OutputGroup\OgObjectModifier|null
     */
    protected $objectModifier;

    /**
     * @var \Propel\Generator\Behavior\OutputGroup\OgTableMapModifier|null
     */
    protected $tableModifier;

    /**
     * @var \Propel\Generator\Behavior\OutputGroup\OgQueryModifier|null
     */
    protected $queryModifier;

    /**
     * @see \Propel\Generator\Model\Behavior::getObjectBuilderModifier()
     *
     * @return $this|\Propel\Generator\Behavior\OutputGroup\OgObjectModifier
     */
    public function getObjectBuilderModifier()
    {
        if ($this->objectModifier === null) {
            $this->objectModifier = new OgObjectModifier($this);
        }

        return $this->objectModifier;
    }

    /**
     * @see \Propel\Generator\Model\Behavior::getTableMapBuilderModifier()
     *
     * @return $this|\Propel\Generator\Behavior\OutputGroup\OgTableMapModifier
     */
    public function getTableMapBuilderModifier()
    {
        if ($this->tableModifier === null) {
            $this->tableModifier = new OgTableMapModifier($this);
        }

        return $this->tableModifier;
    }

    /**
     * @see \Propel\Generator\Model\Behavior::getQueryBuilderModifier()
     *
     * @return $this|\Propel\Generator\Behavior\OutputGroup\OgQueryModifier
     */
    public function getQueryBuilderModifier()
    {
        if ($this->queryModifier === null) {
            $this->queryModifier = new OgQueryModifier($this);
        }

        return $this->queryModifier;
    }

    /**
     * @see \Propel\Generator\Model\Behavior::getTable()
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table): void
    {
        parent::setTable($table);
    }

    /**
     * @return class-string
     */
    public function getObjectCollectionClass(): string
    {
        return $this->getParameter(self::PARAMETER_OBJECT_COLLECTION_CLASS)
            ?: ObjectCollectionWithOutputGroups::class;
    }

    /**
     * @see Propel\Generator\Model\Behavior\Behavior::renderTemplate()
     *
     * @param string $filename
     * @param array $vars
     *
     * @return string
     */
    public function renderLocalTemplate(string $filename, array $vars = []): string
    {
        $templatePath = $this->getDirname() . '/templates/';

        return $this->renderTemplate($filename, $vars, $templatePath);
    }

    /**
     * @param \Propel\Generator\Model\Column $column
     *
     * @return array<string>
     */
    public function getColumnOutputGroupNames(Column $column): array
    {
        return $this->parseOutputGroupAttribute($column, 'outputGroup');
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return array<string>
     */
    public function getForeignKeyLocalOutputGroupNames(ForeignKey $fk): array
    {
        return $this->parseOutputGroupAttribute($fk, 'outputGroup');
    }

    /**
     * @param \Propel\Generator\Model\ForeignKey $fk
     *
     * @return array<string>
     */
    public function getForeignKeyRefOutputGroupNames(ForeignKey $fk): array
    {
        return $this->parseOutputGroupAttribute($fk, 'refOutputGroup');
    }

    /**
     * @param \Propel\Generator\Model\MappingModel $model
     * @param string $attributeName
     *
     * @return array<string>
     */
    protected function parseOutputGroupAttribute(MappingModel $model, string $attributeName): array
    {
        return $this->getDefaultValueForSet($model->getAttribute($attributeName, '')) ?? [];
    }
}
