<?php

namespace Propel\Tests\Helpers;

class NoSchemaPlatform
{
    public function supportsSchemas()
    {
        return false;
    }
}
