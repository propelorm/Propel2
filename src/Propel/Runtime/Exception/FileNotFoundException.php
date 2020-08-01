<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Exception;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class FileNotFoundException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param string $path The path to the file that was not found
     */
    public function __construct($path)
    {
        parent::__construct(sprintf('The file "%s" does not exist', $path));
    }
}
