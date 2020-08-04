<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Base\Issue1033Book as BaseIssue1033Book;

class Issue1033Book extends BaseIssue1033Book
{
    protected static $protectedStatic = true;
}
