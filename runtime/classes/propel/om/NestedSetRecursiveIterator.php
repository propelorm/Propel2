<?php

/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

/**
 * Pre-order node iterator for Node objects.
 *
 * @author     Heltem <heltem@o2php.com>
 * @version    $Revision$
 * @package    propel.om
 */
class NestedSetRecursiveIterator implements RecursiveIterator
{
	protected $topNode = null;

	protected $curNode = null;

	public function __construct($node) {
		$this->topNode = $node;
		$this->curNode = $node;
	}

	public function rewind() {
		$this->curNode = $this->topNode;
	}

	public function valid() {
		return ($this->curNode !== null);
	}

	public function current() {
		return $this->curNode;
	}

	public function key() {
		$key = array();
		foreach ($this->curNode->getPath() as $node) {
			$key[] = $node->getPrimaryKey();
		}
		return implode('.', $key);
	}

	public function next() {
		$nextNode = null;

		if ($this->valid()) {
			while (null === $nextNode) {
				if (null === $this->curNode) {
					break;
				}

				if ($this->curNode->hasNextSibling()) {
					$nextNode = $this->curNode->retrieveNextSibling();
				} else {
					break;
				}
			}
			$this->curNode = $nextNode;
		}
		return $this->curNode;
	}

	public function hasChildren() {
		return $this->curNode->hasChildren();
	}

	public function getChildren() {
		return new NestedSetRecursiveIterator($this->curNode->retrieveFirstChild());
	}
}
