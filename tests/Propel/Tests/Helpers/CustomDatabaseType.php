<?php

namespace Propel\Tests\Helpers;

class CustomDatabaseType implements \Propel\Runtime\CustomDataTypeInterface {

    public function __construct($data) {
        $this->data = $data;
    }

    public static function __toDatabase($instance) {
        // How the data will be bound as a PDO param
        return $instance->data;
    }

    public static function __fromDatabase($data) {
        return self::__construct($data);
    }

    public static function __getPdoType() {
        return \PDO::PARAM_STR;
    }
}
