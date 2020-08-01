<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

/**
 * A <code>NameGeneratorInterface</code> implementation for PHP-esque names.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Bernd Goldschmidt <bgoldschmidt@rapidsoft.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class PhpNameGenerator implements NameGeneratorInterface
{
    /**
     * <code>inputs</code> should consist of two (three) elements, the
     * original name of the database element and the method for
     * generating the name.
     * The optional third element may contain a prefix that will be
     * script from name prior to generate the resulting name.
     * There are currently three methods:
     * <code>CONV_METHOD_NOCHANGE</code> - xml names are converted
     * directly to php names without modification.
     * <code>CONV_METHOD_UNDERSCORE</code> will capitalize the first
     * letter, remove underscores, and capitalize each letter before
     * an underscore. All other letters are lowercased. "phpname"
     * works the same as the <code>CONV_METHOD_PHPNAME</code> method
     * but will not lowercase any characters.
     *
     * @see NameGenerator
     *
     * @param string[] $inputs List expected to contain two (optional: three) parameters,
     *                          element 0 contains name to convert, element 1 contains method for conversion,
     *                          optional element 2 contains prefix to be striped from name
     *
     * @return string The generated name.
     */
    public function generateName($inputs)
    {
        $schemaName = $inputs[0];
        $method = $inputs[1];

        if (count($inputs) > 2) {
            $prefix = $inputs[2];
            if (!empty($prefix) && substr($schemaName, 0, strlen($prefix)) === $prefix) {
                $schemaName = substr($schemaName, strlen($prefix));
            }
        }

        $phpName = null;

        switch ($method) {
            case self::CONV_METHOD_CLEAN:
                $phpName = $this->cleanMethod($schemaName);

                break;
            case self::CONV_METHOD_PHPNAME:
                $phpName = $this->phpnameMethod($schemaName);

                break;
            case self::CONV_METHOD_NOCHANGE:
                $phpName = $this->nochangeMethod($schemaName);

                break;
            case self::CONV_METHOD_UNDERSCORE:
            default:
                $phpName = $this->underscoreMethod($schemaName);
        }

        return $phpName;
    }

    /**
     * Converts a database schema name to php object name by Camelization.
     * Removes <code>STD_SEPARATOR_CHAR</code>, capitalizes first letter
     * of name and each letter after the <code>STD_SEPERATOR</code>,
     * converts the rest of the letters to lowercase.
     *
     * This method should be named camelizeMethod() for clarity
     *
     * my_CLASS_name -> MyClassName
     *
     * @see NameGenerator
     * @see #underscoreMethod()
     *
     * @param string $schemaName name to be converted.
     *
     * @return string Converted name.
     */
    protected function underscoreMethod($schemaName)
    {
        $name = '';
        $tok = strtok($schemaName, self::STD_SEPARATOR_CHAR);
        while ($tok !== false) {
            $name .= ucfirst(strtolower($tok));
            $tok = strtok(self::STD_SEPARATOR_CHAR);
        }

        return $name;
    }

    /**
     * Converts a database schema name to php object name. Removes
     * any character that is not a letter or a number and capitalizes
     * first letter of the name, the first letter of each alphanumeric
     * block and converts the rest of the letters to lowercase.
     *
     * T$NAMA$RFO_max => TNamaRfoMax
     *
     * @see NameGenerator
     * @see #underscoreMethod()
     *
     * @param string $schemaName name to be converted.
     *
     * @return string Converted name.
     */
    protected function cleanMethod($schemaName)
    {
        $name = '';
        $regexp = '/([a-z0-9]+)/i';
        $matches = [];
        if (!preg_match_all($regexp, $schemaName, $matches)) {
            return $schemaName;
        }

        foreach ($matches[1] as $tok) {
            $name .= ucfirst(strtolower($tok));
        }

        return $name;
    }

    /**
     * Converts a database schema name to php object name. Operates
     * same as underscoreMethod but does not convert anything to
     * lowercase.
     *
     * my_CLASS_name -> MyCLASSName
     *
     * @see NameGenerator
     * @see #underscoreMethod(String)
     *
     * @param string $schemaName name to be converted.
     *
     * @return string Converted name.
     */
    protected function phpnameMethod($schemaName)
    {
        $name = '';
        $tok = strtok($schemaName, self::STD_SEPARATOR_CHAR);
        while ($tok !== false) {
            $name .= ucfirst($tok);
            $tok = strtok(self::STD_SEPARATOR_CHAR);
        }

        return $name;
    }

    /**
     * Converts a database schema name to PHP object name. In this
     * case no conversion is made.
     *
     * @param string $name name to be converted.
     *
     * @return string
     */
    protected function nochangeMethod($name)
    {
        return $name;
    }
}
