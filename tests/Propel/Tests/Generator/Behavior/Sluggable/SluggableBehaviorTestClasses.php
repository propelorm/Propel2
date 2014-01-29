<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\Sluggable;

use Propel\Tests\Bookstore\Behavior\Table13;
use Propel\Tests\Bookstore\Behavior\Table14;

class TestableTable13 extends Table13
{
    public function createSlug()
    {
        return parent::createSlug();
    }

    public function createRawSlug()
    {
        return parent::createRawSlug();
    }

    public static function cleanupSlugPart($slug, $separator = '-')
    {
        return parent::cleanupSlugPart($slug, $separator);
    }

    public function makeSlugUnique($slug, $separator = '-', $increment = 0)
    {
        return parent::makeSlugUnique($slug, $separator, $increment);
    }
}

class TestableTable14 extends Table14
{
    public function createSlug()
    {
        return parent::createSlug();
    }

    public function createRawSlug()
    {
        return parent::createRawSlug();
    }

    public static function limitSlugSize($slug, $incrementReservedSpace = 3)
    {
        return parent::limitSlugSize($slug, $incrementReservedSpace);
    }
}
