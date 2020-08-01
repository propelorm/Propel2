<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * An interface for representing mapping model objects.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
interface MappingModelInterface
{
    public const DEFAULT_STRING_FORMAT = 'YAML';
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PROTECTED = 'protected';

    /**
     * Loads a model definition from an array.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function loadMapping(array $attributes);
}
