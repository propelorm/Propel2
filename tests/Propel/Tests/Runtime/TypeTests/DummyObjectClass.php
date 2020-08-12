<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\TypeTests;

class DummyObjectClass
{
    public $propPublic;

    protected $propProtected;

    private $propPrivate;

    /**
     * @param mixed $propPrivate
     *
     * @return void
     */
    public function setPropPrivate($propPrivate)
    {
        $this->propPrivate = $propPrivate;
    }

    /**
     * @return mixed
     */
    public function getPropPrivate()
    {
        return $this->propPrivate;
    }

    /**
     * @param mixed $propProtected
     *
     * @return void
     */
    public function setPropProtected($propProtected)
    {
        $this->propProtected = $propProtected;
    }

    /**
     * @return mixed
     */
    public function getPropProtected()
    {
        return $this->propProtected;
    }

    /**
     * @param mixed $propPublic
     *
     * @return void
     */
    public function setPropPublic($propPublic)
    {
        $this->propPublic = $propPublic;
    }

    /**
     * @return mixed
     */
    public function getPropPublic()
    {
        return $this->propPublic;
    }
}
