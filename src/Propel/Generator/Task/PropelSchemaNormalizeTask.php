<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'phing/tasks/ext/pdo/PDOTask.php';

/**
 * This class generates an XML schema of an existing database from
 * the database metadata.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.generator.task
 */
class PropelSchemaNormalizeTask extends PDOTask
{
    protected $xmlSchema;

    private $sortPlan = array(
        // entity             => attrForSort
        'behavior'            => 'name',
        'column'              => 'name',
        'database'            => 'name',
        'foreign-key'         => 'name',
        'id-method-parameter' => 'name',
        'index'               => 'name',
        'index-column'        => 'name',
        'reference'           => array('local', 'foreign'),
        'table'               => 'name',
        'unique'              => 'name',
        'unique-column'       => 'name',
        'validator'           => 'columnname',
        'vendor-info'         => 'type',
        );

	/**
	 * Sets the output name for the XML file.
	 *
	 * @param      PhingFile $v
	 */
	public function setSchemaFile(PhingFile $v)
	{
		$this->xmlSchema = $v;
	}

    function xmlRecurse($node, $currentPath = '') {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                if ($childNode instanceof DomElement)
                {
                    $this->xmlRecurse($childNode, "{$currentPath}/{$node->tagName}");
                }
            }

            $this->sortXmlByEntityAndPrimaryAttribute($node);
        }
    }

    public function sortXmlByEntityAndPrimaryAttribute($domNode)
    {
        $nodesToSort = array();
        // prune all children while building a list of nodes to sort
        $nodeIndex = 0;
        while ($domNode->hasChildNodes()) {
            $possiblySortableDomNode = $domNode->removeChild($domNode->childNodes->item(0));

            if ($possiblySortableDomNode instanceof DomText && $possiblySortableDomNode->isElementContentWhitespace()) continue;

            // figure out sortKey for element
            if ($possiblySortableDomNode instanceof DomElement)
            {
                if (!isset($this->sortPlan[$possiblySortableDomNode->tagName]))
                {
                    $this->log("WARNING: no sort plan on file for entity {$possiblySortableDomNode->tagName}");
                }

                $sortKeys = array();
                $sortKeys[] = $possiblySortableDomNode->tagName;

                if (isset($this->sortPlan[$possiblySortableDomNode->tagName]))
                {
                    $moreSortKeys = is_array($this->sortPlan[$possiblySortableDomNode->tagName]) ?
                        $this->sortPlan[$possiblySortableDomNode->tagName]
                        :
                        array($this->sortPlan[$possiblySortableDomNode->tagName])
                        ;
                    foreach ($moreSortKeys as $additionalSortKey) {
                        $sortKeys[] = $possiblySortableDomNode->getAttribute($additionalSortKey);
                    }
                }
                else
                {
                    $sortKeys[] = $nodeIndex;
                }

                $sortKey = join('/', $sortKeys);
            }
            else
            {
                $sortKey = "0_{$nodeIndex}";
            }

            if (isset($nodesToSort[$sortKey])) throw new BuildException("ERROR: key collision for {$domNode->tagName} / {$sortKey}");
            $nodesToSort[$sortKey] = $possiblySortableDomNode;

            $nodeIndex++;
        }

        // ksort the array
        ksort($nodesToSort);

        // add back all children to domNode
        foreach ($nodesToSort as $k => $node) {
            $domNode->appendChild($node);
        }
    }

	/**
	 * @throws     BuildException
	 */
	public function main()
	{
        $props = $this->getProject()->getProperties();
        if (!isset($props['propel.normalizeXmlOrder']) or $props['propel.normalizeXmlOrder'] === false)
        {
            $this->log("Schema Normalization disabled.",Project::MSG_VERBOSE);
            return;
        }

		try {
            // load
            $xml = new DOMDocument();
            $xml->load($this->xmlSchema);
            $xml->normalizeDocument();
            if ($xml === false) throw new BuildException("Failed to open schema XML: {$this->xmlSchema}");

            $this->xmlRecurse($xml);

            // save
			$this->log("Writing XML to file: " . $this->xmlSchema->getPath());
            $xml->formatOutput = true; // pretty printing
            $out = new FileWriter($this->xmlSchema);
            $xmlstr = $xml->saveXML();
            $out->write($xmlstr);
            $out->close();
		} catch (Exception $e) {
			$this->log("There was an error building XML from metadata: " . $e->getMessage(), Project::MSG_ERR);
			return false;
		}

		$this->log("Schema normalization finished.");
	}
}
