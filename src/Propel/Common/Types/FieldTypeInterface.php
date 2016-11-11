<?php

namespace Propel\Common\Types;

use gossi\codegen\model\PhpMethod;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
interface FieldTypeInterface
{
    /**
     * This method is being used to convert the php object property value
     * to a copy of the value, which is being stored in the snapshot array. Against this snapshot
     * the whole building of change sets is based.
     *
     * Note:
     *
     * This should return always a compareable object. Returning always new objects is not compareable.
     * Propel uses this results for its change set building, means its result will be cached and when a user
     * wants to sync its object with the database, this method is called and the results is compared
     * (using: $changed = $old !== $new) to check whether the value has changed. If so, propel syncs it to the
     * database.
     *
     * In most cases a internal call to propertyToDatabase is enough, however,
     * if you deal with php objects and the database driver understands those objects, we can not use it to build
     * change sets.
     *
     * @param mixed    $value
     * @param FieldMap $fieldMap
     *
     * @return mixed
     */
    public function propertyToSnapshot($value, FieldMap $fieldMap);

    /**
     * Sometimes, it is necessary to convert your snapshot (propertyToSnapshot) back to a real php data type
     * (like int, object etc). For example for primary keys. They are stored as snapshots and restored using this
     * method and then being used in the database adapter.
     *
     * Especially, when propertyToSnapshot can not return always a new object, which
     * isn't compareable, it should return some kind of serialization Propel can use to detect changes
     * (using: $changed = $old !== $new)
     *
     * Calling databaseToProperty like AbstractType it does is often enough.
     *
     * @param mixed    $value
     * @param FieldMap $fieldMap
     */
    public function snapshotToProperty($value, FieldMap $fieldMap);

    /**
     * This method is being used to convert the php class property value
     * to a format the persister can understand. This is used as the actual value in change set.
     * (from Repository::buildChangeSet())
     *
     * @param mixed    $value
     * @param FieldMap $fieldMap
     *
     * @return mixed
     */
    public function propertyToDatabase($value, FieldMap $fieldMap);

    /**
     * This method is being used to convert data from PDO/database to the php data structure.
     * Results is set as the actual class property.
     *
     * Example:
     *   Database returns a timestamp as integer, we convert it here to a \DateTime.
     *   Database returns a number as string, we convert it to a number.
     *
     * Note:
     *
     *  This is also used for array -> object converting.
     *
     * @param          $value
     * @param FieldMap $fieldMap
     *
     * @return mixed
     */
    public function databaseToProperty($value, FieldMap $fieldMap);
}