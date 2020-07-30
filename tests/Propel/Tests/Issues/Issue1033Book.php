<?php

namespace Propel\Tests\Issues;

use Base\Issue1033Book as BaseIssue1033Book;

class Issue1033Book extends BaseIssue1033Book
{
    protected static $protectedStatic = true;
}
