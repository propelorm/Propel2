<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Base\Issue915Book as BaseIssue915Book;

class Issue915Book extends BaseIssue915Book
{
    private $color;

    /**
     * @return void
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    public function getColor()
    {
        return $this->color;
    }
}
