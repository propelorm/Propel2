<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * A name generation factory.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class NameFactory
{
    /**
     * The class name of the PHP name generator.
     */
    public const PHP_GENERATOR = '\Propel\Generator\Model\PhpNameGenerator';

    /**
     * The fully qualified class name of the constraint name generator.
     */
    public const CONSTRAINT_GENERATOR = '\Propel\Generator\Model\ConstraintNameGenerator';

    /**
     * The cache of <code>NameGeneratorInterface</code> algorithms in use for
     * name generation, keyed by fully qualified class name.
     *
     * @var \Propel\Generator\Model\NameGeneratorInterface[]
     */
    private static $algorithms = [];

    /**
     * Factory method which retrieves an instance of the named generator.
     *
     * @param string $name The fully qualified class name of the name
     *                     generation algorithm to retrieve.
     *
     * @return \Propel\Generator\Model\NameGeneratorInterface
     */
    protected static function getAlgorithm($name)
    {
        if (!isset(self::$algorithms[$name])) {
            self::$algorithms[$name] = new $name();
        }

        return self::$algorithms[$name];
    }

    /**
     * Given a list of <code>String</code> objects, implements an
     * algorithm which produces a name.
     *
     * @param string $algorithmName The fully qualified class name of the {@link NameGeneratorInterface}
     *                                        implementation to use to generate names.
     * @param string[] $inputs Inputs used to generate a name.
     *
     * @return string The generated name.
     */
    public static function generateName($algorithmName, $inputs)
    {
        $algorithm = self::getAlgorithm($algorithmName);

        return $algorithm->generateName($inputs);
    }
}
