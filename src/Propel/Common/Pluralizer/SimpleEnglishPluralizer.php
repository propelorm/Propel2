<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Pluralizer;

/**
 * The Propel 1.6 default English pluralizer class
 * for compatibility only.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class SimpleEnglishPluralizer implements PluralizerInterface
{
    /**
     * Generate a plural name based on the passed in root.
     *
     * @param string $root The root that needs to be pluralized (e.g. Author)
     *
     * @return string The plural form of $root (e.g. Authors).
     */
    public function getPluralForm(string $root): string
    {
        return $root . 's';
    }
}
