<?php

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchive
{
    public $id, $title, $age;

    public function setId($value)
    {
        $this->id = $value;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function setAge($value)
    {
        $this->age = $value;
    }

    public function save()
    {
        return $this;
    }
}
