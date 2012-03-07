<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Manager;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Om\ClassTools;
use Propel\Generator\Model\Om\OMBuilder;

use \DOMDocument;

/**
 * @author Hans Lellelid <hans@xmpl.org>
 * @author William Durand <william.durand1@gmail.com>
 */
class ConfigManager extends AbstractManager
{
    /**
     * @var string
     */
    protected $outputFile;

    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;
    }

    public function getOutputFile()
    {
        return $this->outputFile;
    }

    public function getXmlConfig()
    {
        return $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . 'runtime-conf.xml';
    }

    public function build()
    {
        $this->log('Loading XML configuration file...');

        // Create a PHP array from the runtime-conf.xml file
        $xmlDom = new DOMDocument();
        $xmlDom->load($this->getXmlConfig());
        $xml = simplexml_load_string($xmlDom->saveXML());
        $phpconf = self::simpleXmlToArray($xml);

        $this->log(sprintf('Loaded "%s" successfully', $this->getXmlConfig()));

        /* For some reason the array generated from runtime-conf.xml has separate
         * 'log' section and 'propel' sections. To maintain backward compatibility
         * we need to put 'log' back into the 'propel' section.
         */
        if (isset($phpconf['log'])) {
            $phpconf['propel']['log'] = $phpconf['log'];
            unset($phpconf['log']);
        }

        if (isset($phpconf['profiler'])) {
            $phpconf['propel']['profiler'] = $phpconf['profiler'];
            unset($phpconf['profiler']);
        }

        if (isset($phpconf['propel'])) {
            $phpconf = $phpconf['propel'];
        }

        // Write resulting PHP data to output file
        $output = "<?php\n";
        $output .= "// from XML runtime conf file " . $this->getXmlConfig() . "\n";
        $output .= "\$conf = ";
        $output .= var_export($phpconf, true);
        $output .= ";\n";
        $output .= "return \$conf;";

        $mustWriteRuntimeConf = true;
        if (file_exists($this->getOutputFile())) {
            $currentRuntimeConf = file_get_contents($this->getOutputFile());

            if ($currentRuntimeConf == $output) {
                $this->log(sprintf('No change in PHP runtime conf file "%s"', $this->getOutputFile()));
                $mustWriteRuntimeConf = false;
            } else {
                $this->log(sprintf('Updating PHP runtime conf file "%s"', $this->getOutputFile()));
            }
        } else {
            $this->log(sprintf('Creating PHP runtime conf file "%s"', $this->getOutputFile()));
        }

        if ($mustWriteRuntimeConf && !file_put_contents($this->getOutputFile(), $output)) {
            throw new BuildException("Error writing output file: " . $this->getOutputFile());
        }
    }

    protected function logClassMap($classMap)
    {
        foreach ($classMap as $className => $classPath) {
            $this->log(sprintf('  %-15s => %s', $className, $classPath));
        }
    }
    /**
     * Recursive function that converts an SimpleXML object into an array.
     * @author     Christophe VG (based on code form php.net manual comment)
     *
     * @param      object SimpleXML object.
     * @return     array Array representation of SimpleXML object.
     */
    protected static function simpleXmlToArray($xml)
    {
        $ar = array();
        foreach ($xml->children() as $k => $v) {
            // recurse the child
            $child = self::simpleXmlToArray( $v );

            //print "Recursed down and found: " . var_export($child, true) . "\n";

            // if it's not an array, then it was empty, thus a value/string
            if (count($child) == 0) {
                $child = self::getConvertedXmlValue($v);
            }

            // add the childs attributes as if they where children
            foreach ($v->attributes() as $ak => $av) {
                if ($ak == 'id') {
                    // special exception: if there is a key named 'id'
                    // then we will name the current key after that id
                    $k = self::getConvertedXmlValue($av);
                } else {
                    // otherwise, just add the attribute like a child element
                    $child[$ak] = self::getConvertedXmlValue($av);
                }
            }

            // if the $k is already in our children list, we need to transform
            // it into an array, else we add it as a value
            if (!in_array( $k, array_keys($ar))) {
                $ar[$k] = $child;
            } else {
                // (This only applies to nested nodes that do not have an @id attribute)

                // if the $ar[$k] element is not already an array, then we need to make it one.
                // this is a bit of a hack, but here we check to also make sure that if it is an
                // array, that it has numeric keys.  this distinguishes it from simply having other
                // nested element data.
                if (!is_array($ar[$k]) || !isset($ar[$k][0])) {
                    $ar[$k] = array($ar[$k]);
                }

                $ar[$k][] = $child;
            }
        }

        return $ar;
    }

    /**
     * Process XML value, handling boolean, if appropriate.
     * @param      object The simplexml value object.
     * @return     mixed
     */
    private static function getConvertedXmlValue($value)
    {
        $value = (string) $value; // convert from simplexml to string
        // handle booleans specially
        $lwr = strtolower($value);
        if ($lwr === "false") {
            $value = false;
        } elseif ($lwr === "true") {
            $value = true;
        }

        return $value;
    }
}
