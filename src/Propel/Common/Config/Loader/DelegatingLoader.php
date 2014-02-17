<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use Symfony\Component\Config\Loader\DelegatingLoader as BaseDelegatingLoader;

/**
 * Class DelegatingLoader
 *
 * @author Cristiano Cinotti
 */
class DelegatingLoader extends BaseDelegatingLoader
{
    public function __construct()
    {
        parent::__construct(new LoaderResolver());
    }
}
