<?php

namespace Propel\Runtime\Persister\Exception;

use Propel\Runtime\Map\EntityMap;

class PersisterException extends \Exception
{
    /**
     * @var EntityMap
     */
    protected $entityMap;

    /**
     * @param EntityMap       $entityMap
     * @param string          $message
     * @param mixed           $code
     * @param \Exception|null $previous
     *
     * @return static
     */
    public static function create(EntityMap $entityMap, $message = null, $previous = null, $code = 0)
    {
        $exception = new static($message, $code, $previous);
        $exception->entityMap = $entityMap;

        return $exception;
    }

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->entityMap;
    }
}