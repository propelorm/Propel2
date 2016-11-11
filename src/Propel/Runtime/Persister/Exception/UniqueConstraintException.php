<?php

namespace Propel\Runtime\Persister\Exception;

use Propel\Runtime\Map\EntityMap;

class UniqueConstraintException extends PersisterException
{
    /**
     * {@inheritdoc}
     */
    public static function createForField(EntityMap $entityMap, $field, $previous = null)
    {
        $message = sprintf('Unique constraint failure for field %s in entity %s', $field, $entityMap->getFullClassName());
        return parent::create($entityMap, $message, $previous);
    }
}