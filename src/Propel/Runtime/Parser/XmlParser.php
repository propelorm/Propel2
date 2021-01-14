<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Parser;

use DateTime;
use DateTimeInterface;
use DOMDocument;
use DOMNode;

/**
 * XML parser. Converts data between associative array and XML formats
 *
 * @author Francois Zaninotto
 */
class XmlParser extends AbstractParser
{
    /**
     * Converts data from an associative array to XML.
     *
     * @param array $array Source data to convert
     * @param string $rootKey Will be used for naming the root node
     * @param string|null $charset Character set of the input data. Defaults to UTF-8.
     *
     * @return string Converted data, as an XML string
     */
    public function fromArray($array, $rootKey = 'data', $charset = null)
    {
        $rootNode = $this->getRootNode($rootKey);
        $this->arrayToDOM($array, $rootNode, $charset);

        return $rootNode->ownerDocument->saveXML();
    }

    /**
     * @param array $array
     * @param string|null $rootKey
     * @param string|null $charset
     *
     * @return string
     */
    public function listFromArray($array, $rootKey = 'data', $charset = null)
    {
        $rootNode = $this->getRootNode($rootKey);
        $this->arrayToDOM($array, $rootNode, $charset);

        return (string)$rootNode->ownerDocument->saveXML();
    }

    /**
     * Create a DOMDocument and get the root DOMNode using a root element name
     *
     * @param string $rootElementName The Root Element Name
     *
     * @return \DOMElement The root DOMNode
     */
    protected function getRootNode($rootElementName)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $rootElement = $xml->createElement($rootElementName);
        $xml->appendChild($rootElement);

        return $rootElement;
    }

    /**
     * Alias for XmlParser::fromArray()
     *
     * @param array $array Source data to convert
     * @param string $rootElementName Name of the root element of the XML document
     * @param string|null $charset Character set of the input data. Defaults to UTF-8.
     *
     * @return string Converted data, as an XML string
     */
    public function toXML($array, $rootElementName = 'data', $charset = null)
    {
        return $this->fromArray($array, $rootElementName, $charset);
    }

    /**
     * Alias for XmlParser::listFromArray()
     *
     * @param array $array Source data to convert
     * @param string $rootElementName Name of the root element of the XML document
     * @param string|null $charset Character set of the input data. Defaults to UTF-8.
     *
     * @return string Converted data, as an XML string
     */
    public function listToXML($array, $rootElementName = 'data', $charset = null)
    {
        return $this->listFromArray($array, $rootElementName, $charset);
    }

    /**
     * @param array $array
     * @param \DOMElement $rootElement
     * @param string|null $charset
     *
     * @return \DOMElement
     */
    protected function arrayToDOM($array, $rootElement, $charset = null)
    {
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $key = $rootElement->nodeName;

                // Books => Book
                if (substr($key, -1, 1) === 's') {
                    $key = substr($key, 0, -1);
                }
            }

            $element = $rootElement->ownerDocument->createElement($key);
            if (is_array($value)) {
                if (!empty($value)) {
                    $element = $this->arrayToDOM($value, $element, $charset);
                }
            } elseif (is_string($value)) {
                $charset = $charset ? $charset : 'utf-8';
                if (function_exists('iconv') && strcasecmp($charset, 'utf-8') !== 0 && strcasecmp($charset, 'utf8') !== 0) {
                    $value = iconv($charset, 'UTF-8', $value);
                }
                $value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
                $child = $element->ownerDocument->createCDATASection($value);
                $element->appendChild($child);
            } elseif ($value instanceof DateTimeInterface) {
                $element->setAttribute('type', 'xsd:dateTime');
                $child = $element->ownerDocument->createTextNode($value->format(DateTime::ISO8601));
                $element->appendChild($child);
            } else {
                $child = $element->ownerDocument->createTextNode((string)$value);
                $element->appendChild($child);
            }
            $rootElement->appendChild($element);
        }

        return $rootElement;
    }

    /**
     * Converts data from XML to an associative array.
     *
     * @param string $data Source data to convert, as an XML string
     * @param string $rootKey
     *
     * @return array Converted data
     */
    public function toArray($data, $rootKey = 'data')
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($data);
        $element = $doc->documentElement;

        return $this->convertDOMElementToArray($element);
    }

    /**
     * Alias for XmlParser::toArray()
     *
     * @param string $data Source data to convert, as an XML string
     * @param string $rootKey
     *
     * @return array Converted data
     */
    public function fromXML($data, $rootKey = 'data')
    {
        return $this->toArray($data, $rootKey);
    }

    /**
     * @param \DOMNode $data
     *
     * @return array
     */
    protected function convertDOMElementToArray(DOMNode $data)
    {
        $array = [];
        $elementNames = [];
        /** @var \DOMElement $element */
        foreach ($data->childNodes as $element) {
            if ($element->nodeType == XML_TEXT_NODE) {
                continue;
            }
            $name = $element->nodeName;
            if (isset($elementNames[$name])) {
                if (isset($array[$name])) {
                    // change the first 'book' to 0
                    $array[$elementNames[$name]] = $array[$name];
                    unset($array[$name]);
                }
                $elementNames[$name] += 1;
                $index = $elementNames[$name];
            } else {
                $elementNames[$name] = 0;
                $index = $name;
            }

            if ($element->hasChildNodes() && !$this->hasOnlyTextNodes($element)) {
                $array[$index] = $this->convertDOMElementToArray($element);
            } elseif ($element->hasChildNodes() && $element->firstChild->nodeType == XML_CDATA_SECTION_NODE) {
                $array[$index] = htmlspecialchars_decode($element->firstChild->textContent);
            } elseif (!$element->hasChildNodes()) {
                $array[$index] = null;
            } elseif ($element->hasAttribute('type') && $element->getAttribute('type') === 'xsd:dateTime') {
                $array[$index] = new DateTime($element->textContent);
            } else {
                $array[$index] = $element->textContent;
            }
        }

        return $array;
    }

    /**
     * @param \DOMNode $node
     *
     * @return bool
     */
    protected function hasOnlyTextNodes(DOMNode $node)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType != XML_CDATA_SECTION_NODE && $childNode->nodeType != XML_TEXT_NODE) {
                return false;
            }
        }

        return true;
    }
}
