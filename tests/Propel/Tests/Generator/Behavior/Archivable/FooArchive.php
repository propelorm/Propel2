<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Archivable;

class FooArchive
{
    /**
     * @var mixed
     */
    public $id;

    /**
     * @var mixed
     */
    public $title;

    /**
     * @var mixed
     */
    public $age;

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public function setAge($value)
    {
        $this->age = $value;
    }

    /**
     * @return $this
     */
    public function save()
    {
        return $this;
    }
}
