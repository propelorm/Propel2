<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Sluggable;

use Propel\Tests\Bookstore\Behavior\Table13;
use Propel\Tests\Bookstore\Behavior\Table14;

class TestableTable13 extends Table13
{
    public function createSlug(): string
    {
        return parent::createSlug();
    }

    public function createRawSlug(): string
    {
        return parent::createRawSlug();
    }

    public static function cleanupSlugPart(string $slug, string $separator = '-'): string
    {
        return parent::cleanupSlugPart($slug, $separator);
    }

    public function makeSlugUnique(string $slug, string $separator = '-', bool $alreadyExists = false): ?string
    {
        return parent::makeSlugUnique($slug, $separator, $alreadyExists);
    }
}

class TestableTable14 extends Table14
{
    public function createSlug(): string
    {
        return parent::createSlug();
    }

    public function createRawSlug(): string
    {
        return parent::createRawSlug();
    }

    public static function limitSlugSize(string $slug, int $incrementReservedSpace = 3): string
    {
        return parent::limitSlugSize($slug, $incrementReservedSpace);
    }
}
