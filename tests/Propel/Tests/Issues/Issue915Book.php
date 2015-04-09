<?php

namespace Propel\Tests\Issues;


class Issue915Book extends \Base\Issue915Book
{
    private $color;

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function getColor()
    {
        return $this->color;
    }
}