<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * An interface for representing mapping model objects.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
interface MappingModelInterface
{
    const DEFAULT_STRING_FORMAT = 'YAML';
    const VISIBILITY_PUBLIC     = 'public';
    const VISIBILITY_PRIVATE    = 'private';
    const VISIBILITY_PROTECTED  = 'protected';

    /**
     * Loads a model definition from an array.
     *
     * @param array $attributes
     */
    public function loadMapping(array $attributes);

    /**
     * Return the name of a model object to be used in PHP code.
     * It's usually in camelCase format.
     *
     * @return string
     */
    public function getName();

    /**
     * Return the name of a model object to be used in SQL strings.
     * It's usually in underscore format (e.g. my_awesome_field), but it can be defined by the user,
     * via `sqlName` schema attribute.
     *
     * @return string
     */
    public function getSqlName();
}
