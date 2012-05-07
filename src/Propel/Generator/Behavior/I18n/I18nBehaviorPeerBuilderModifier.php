<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Behavior\I18n;

/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the peer builder.
 *
 * @author     François Zaninotto
 */
class I18nBehaviorPeerBuilderModifier
{
    protected $behavior;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
    }

    public function staticConstants()
    {
        return "
/**
 * The default locale to use for translations
 * @var        string
 */
const DEFAULT_LOCALE = '{$this->behavior->getDefaultLocale()}';";
    }
}