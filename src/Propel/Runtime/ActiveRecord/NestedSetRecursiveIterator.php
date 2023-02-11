<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveRecord;

use RecursiveIterator;

/**
 * Pre-order node iterator for Node objects.
 *
 * @author Heltem <heltem@o2php.com>
 *
 * @implements \RecursiveIterator<(int|string), mixed>
 */
class NestedSetRecursiveIterator implements RecursiveIterator
{
    /**
     * @var object
     */
    protected $topNode;

    /**
     * @var object
     */
    protected $curNode;

    /**
     * @param object $node
     */
    public function __construct(object $node)
    {
        $this->topNode = $node;
        $this->curNode = $node;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->curNode = $this->topNode;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->curNode !== null;
    }

    /**
     * @psalm-suppress ReservedWord
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->curNode;
    }

    /**
     * @psalm-suppress ReservedWord
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function key(): string
    {
        $method = method_exists($this->curNode, 'getPath') ? 'getPath' : 'getAncestors';
        $key = [];
        foreach ($this->curNode->$method() as $node) {
            $key[] = $node->getPrimaryKey();
        }

        return implode('.', $key);
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $nextNode = null;
        $method = method_exists($this->curNode, 'retrieveNextSibling') ? 'retrieveNextSibling' : 'getNextSibling';
        if ($this->valid()) {
            while ($nextNode === null) {
                if ($this->curNode === null) {
                    break;
                }

                if ($this->curNode->hasNextSibling()) {
                    $nextNode = $this->curNode->$method();
                } else {
                    break;
                }
            }
            $this->curNode = $nextNode;
        }
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->curNode->hasChildren();
    }

    /**
     * @return \Propel\Runtime\ActiveRecord\NestedSetRecursiveIterator|\RecursiveIterator<int|string, mixed>|null
     */
    public function getChildren(): ?RecursiveIterator
    {
        $method = method_exists($this->curNode, 'retrieveFirstChild') ? 'retrieveFirstChild' : 'getFirstChild';

        return new self($this->curNode->$method());
    }
}
