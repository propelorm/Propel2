<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchive
{
    public $id, $title, $age;

    /**
     * @return void
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    /**
     * @return void
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * @return void
     */
    public function setAge($value)
    {
        $this->age = $value;
    }

    public function save()
    {
        return $this;
    }
}
