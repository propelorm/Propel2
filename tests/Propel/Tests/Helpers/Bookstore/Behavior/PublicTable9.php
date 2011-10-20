<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Tests\Bookstore\Behavior\Table9;

// we need this class to test protected methods
class PublicTable9 extends Table9
{
    public $hasParentNode = null;
    public $parentNode = null;
    public $hasPrevSibling = null;
    public $prevSibling = null;
    public $hasNextSibling = null;
    public $nextSibling = null;
}
