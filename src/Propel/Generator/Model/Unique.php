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
 * Information about unique columns of a table.  This class assumes
 * that in the underlying RDBMS, unique constraints and unique indices
 * are roughly equivalent.  For example, adding a unique constraint to
 * a column also creates an index on that column (this is known to be
 * true for MySQL and Oracle).
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class Unique extends Index
{
    /**
     * Returns whether or not this index is unique.
     *
     * Returns Boolean
     */
    public function isUnique()
    {
        return true;
    }

    /**
     * Creates a default name for this index.
     *
     */
    protected function createName()
    {
        // ASSUMPTION: This Index not yet added to the list.
        $inputs[] = $this->table->getDatabase();
        $inputs[] = $this->table->getCommonName();
        $inputs[] = 'U';
        $inputs[] = count($this->table->getUnices()) + 1;

        // @TODO replace the factory by a real object
        $this->name = NameFactory::generateName(NameFactory::CONSTRAINT_GENERATOR, $inputs);
    }

    public function appendXml(\DOMNode $node)
    {
        $doc = ($node instanceof \DOMDocument) ? $node : $node->ownerDocument;

        $uniqueNode = $node->appendChild($doc->createElement('unique'));
        $uniqueNode->setAttribute('name', $this->getName());

        foreach ($this->getColumns() as $colname) {
            $uniqueColNode = $uniqueNode->appendChild($doc->createElement('unique-column'));
            $uniqueColNode->setAttribute('name', $colname);
        }

        foreach ($this->vendorInfos as $vi) {
            $vi->appendXml($uniqueNode);
        }
    }
}
