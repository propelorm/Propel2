<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Om;

use Iterator;

/**
 * Pre-order node iterator for Node objects.
 *
 * @author     Dave Lawson <dlawson@masterytech.com>
 */
class PreOrderNodeIterator implements Iterator
{
    private $topNode = null;

    private $curNode = null;

    private $querydb = false;

    private $con = null;

    public function __construct($node, $opts)
    {
        $this->topNode = $node;
        $this->curNode = $node;

        if (isset($opts['con'])) {
            $this->con = $opts['con'];
        }

        if (isset($opts['querydb'])) {
            $this->querydb = $opts['querydb'];
        }
    }

    public function rewind()
    {
        $this->curNode = $this->topNode;
    }

    public function valid()
    {
        return ($this->curNode !== null);
    }

    public function current()
    {
        return $this->curNode;
    }

    public function key()
    {
        return $this->curNode->getNodePath();
    }

    public function next()
    {
        if ($this->valid())
        {
            $nextNode = $this->curNode->getFirstChildNode($this->querydb, $this->con);

            while (null === $nextNode)
            {
                if (null === $this->curNode || $this->curNode->equals($this->topNode)) {
                    break;
                }

                $nextNode = $this->curNode->getSiblingNode(false, $this->querydb, $this->con);

                if (null === $nextNode) {
                    $this->curNode = $this->curNode->getParentNode($this->querydb, $this->con);
                }
            }

            $this->curNode = $nextNode;
        }

        return $this->curNode;
    }

}
