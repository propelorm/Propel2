<?php

namespace Propel\Tests\Common;

final class BoxedString
{
    /** @var string */
    private $s;

    public function __construct(string $s)
    {
        $this->s = $s;
    }

    public function __toString()
    {
        return $this->s;
    }
}
