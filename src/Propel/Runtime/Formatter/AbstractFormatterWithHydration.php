<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Formatter;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ArrayCollection;
use ReflectionClass;

abstract class AbstractFormatterWithHydration extends AbstractFormatter
{
    /**
     * @var array<mixed>
     */
    protected $alreadyHydratedObjects = [];

    /**
     * @var array
     */
    protected $emptyVariable = [];

    /**
     * @param \Propel\Runtime\ActiveRecord\ActiveRecordInterface|null $record
     *
     * @return array The original record turned into an array
     */
    public function formatRecord(?ActiveRecordInterface $record = null): array
    {
        return $record ? $record->toArray() : [];
    }

    /**
     * @return string|null
     */
    public function getCollectionClassName(): ?string
    {
        return ArrayCollection::class;
    }

    /**
     * Hydrates a series of objects from a result row
     * The first object to hydrate is the model of the Criteria
     * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
     *
     * @param array $row associative array indexed by column number,
     *                   as returned by DataFetcher::fetch()
     *
     * @return array
     */
    protected function &hydratePropelObjectCollection(array $row): array
    {
        $col = 0;

        // hydrate main object or take it from registry
        $mainObjectIsNew = false;
        $this->checkInit();
        /** @var \Propel\Runtime\Map\TableMap $tableMap */
        $tableMap = $this->tableMap;
        $indexType = $this->getDataFetcher()->getIndexType();
        $mainKey = $tableMap::getPrimaryKeyHashFromRow($row, 0, $indexType);
        // we hydrate the main object even in case of a one-to-many relationship
        // in order to get the $col variable increased anyway
        $obj = $this->getSingleObjectFromRow($row, (string)$this->class, $col);

        if (!isset($this->alreadyHydratedObjects[$this->class][$mainKey])) {
            $this->alreadyHydratedObjects[$this->class][$mainKey] = $obj->toArray();
            $mainObjectIsNew = true;
        }

        $hydrationChain = [];

        // related objects added using with()
        foreach ($this->getWith() as $relAlias => $modelWith) {
            // determine class to use
            if ($modelWith->isSingleTableInheritance()) {
                /** @var class-string<object>|object $class */
                $class = $modelWith->getTableMap()::getOMClass($row, $col, false);
                $reflectionClass = new ReflectionClass($class);
                $class = $reflectionClass->getName();
                if ($reflectionClass->isAbstract()) {
                    $tableMapClass = "Map\\{$class}TableMap";
                    $col += $tableMapClass::NUM_COLUMNS;

                    continue;
                }
            } else {
                $class = $modelWith->getModelName();
            }

            // hydrate related object or take it from registry
            $key = $modelWith->getTableMap()::getPrimaryKeyHashFromRow($row, $col, $indexType);
            // we hydrate the main object even in case of a one-to-many relationship
            // in order to get the $col variable increased anyway
            $secondaryObject = $this->getSingleObjectFromRow($row, $class, $col);
            if (!isset($this->alreadyHydratedObjects[$relAlias][$key])) {
                if ($secondaryObject->isPrimaryKeyNull()) {
                    $this->alreadyHydratedObjects[$relAlias][$key] = [];
                } else {
                    $this->alreadyHydratedObjects[$relAlias][$key] = $secondaryObject->toArray();
                }
            }

            if ($modelWith->isPrimary()) {
                $arrayToAugment = &$this->alreadyHydratedObjects[$this->class][$mainKey];
            } else {
                $arrayToAugment = &$hydrationChain[$modelWith->getLeftPhpName()];
            }

            if ($modelWith->isAdd()) {
                if (
                    !isset($arrayToAugment[$modelWith->getRelationName()]) ||
                    !in_array(
                        $this->alreadyHydratedObjects[$relAlias][$key],
                        $arrayToAugment[$modelWith->getRelationName()],
                        true,
                    )
                ) {
                    $arrayToAugment[$modelWith->getRelationName()][] = &$this->alreadyHydratedObjects[$relAlias][$key];
                }
            } else {
                $arrayToAugment[$modelWith->getRelationName()] = &$this->alreadyHydratedObjects[$relAlias][$key];
            }

            $hydrationChain[$modelWith->getRightPhpName()] = &$this->alreadyHydratedObjects[$relAlias][$key];
        }

        // columns added using withColumn()
        foreach ($this->getAsColumns() as $alias => $clause) {
            $this->alreadyHydratedObjects[$this->class][$mainKey][$alias] = $row[$col];
            $col++;
        }

        if ($mainObjectIsNew) {
            return $this->alreadyHydratedObjects[$this->class][$mainKey];
        }

        // we still need to return a reference to something to avoid a warning
        return $this->emptyVariable;
    }
}
