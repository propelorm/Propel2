<?php

namespace Propel\Tests\Generator\Behavior\NestedSet\Fixtures;

use NestedSetTable9;

class PublicTable9 extends NestedSetTable9
{
    public $hasParentNode;

    public $parentNode;

    public $hasPrevSibling;

    public $prevSibling;

    public $hasNextSibling;

    public $nextSibling;
}
