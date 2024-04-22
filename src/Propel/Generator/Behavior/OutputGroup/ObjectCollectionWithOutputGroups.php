<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\OutputGroup;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Map\TableMap;

class ObjectCollectionWithOutputGroups extends ObjectCollection
{
    /**
     * Turn collection objects into arrays with values specified by output group.
     *
     * @param array<string, string>|string $outputGroup Name of the output group used for all tables or
     *                                                  an array mapping model classes to output group name.
     *                                                  If a model class does not have a definition for the
     *                                                  given output group, the whole data is returned.
     * @param string|null $keyColumn (optional) Column name to use as index.
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME,
     *                                        TableMap::TYPE_CAMELNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME,
     *                                        TableMap::TYPE_NUM. Defaults to TableMap::TYPE_PHPNAME.
     * @param array $alreadyDumpedObjects Internally used on recursion, typically not set by user (List of
     *                                      objects to skip to avoid recursion).
     *
     * @return array
     */
    public function toOutputGroup(
        $outputGroup,
        ?string $keyColumn = null,
        string $keyType = TableMap::TYPE_PHPNAME,
        array $alreadyDumpedObjects = []
    ): array {
        $ret = [];
        $keyGetterMethod = 'get' . $keyColumn;

        /** @var \Propel\Generator\Behavior\OutputGroup\ObjectWithOutputGroupInterface $obj */
        foreach ($this->data as $key => $obj) {
            $key = (!$keyColumn) ? $key : $obj->$keyGetterMethod();
            $ret[$key] = $obj->toOutputGroup($outputGroup, $keyType, $alreadyDumpedObjects);
        }

        return $ret;
    }
}
