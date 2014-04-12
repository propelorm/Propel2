<?php

namespace Propel\Tests\Runtime\TypeTests;

class DummyObjectClass {

    public $propPublic;

    protected $propProtected;

    private $propPrivate;

    /**
     * @param mixed $propPrivate
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