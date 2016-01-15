<?php

namespace Propel\Tests\Helpers;

class CustomDatabaseType implements \Propel\Runtime\CustomDataTypeInterface {

    public function __construct($data) {
        $this->data = $data;
    }

    public static function __serializeToDatabase($instance) {
        // How the data will be bound as a PDO param
        return $instance->data;
    }

    public static function __deserializeFromDatabase($data) {
        return new CustomDatabaseType($data);
    }

    public static function __getPdoType() {
        return \PDO::PARAM_STR;
    }

    public static function __serializeFilterBy($data = null, $comparison = null) {
        return CustomDatabaseType::__serializeToDatabase($data);
    }
}
