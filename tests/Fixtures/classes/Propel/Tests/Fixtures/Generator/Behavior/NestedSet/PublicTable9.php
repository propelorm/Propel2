<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Fixtures\Generator\Behavior\NestedSet;

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
