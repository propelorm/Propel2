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
 * @author Heltem <heltem@o2php.com>
 * @version $Revision$
 * @package propel.om
 */
class NestedSetPreOrderNodeIterator implements Iterator
{
	private $topNode = null;

	private $curNode = null;

	public function __construct($node) {
		$this->topNode = $node;
		$this->curNode = $node;
	}

	public function rewind() {
		$this->curNode = $this->topNode;
	}

	public function valid() {
		return ($this->curNode !== null && $this->curNode !== false);
	}

	public function current() {
		return $this->curNode;
	}

	public function key() {
		return $this->curNode->getPath();
	}

	public function next() {
		$nextNode = false;

		if ($this->valid()) {
			if($this->curNode->hasChildren()) {
				$nextNode = $this->curNode->retrieveFirstChild();
			}

			while(false === $nextNode) {
				if(null === $this->curNode) {
					break;
				}

				if($this->curNode->hasNextSibling()) {
					$nextNode = $this->curNode->retrieveNextSibling();
				} else if ($this->curNode->isEqualTo($this->topNode)) {
					break;
				} else {
					$this->curNode = $this->curNode->retrieveParent();
				}
			}
			$this->curNode = $nextNode;
		}
		return $this->curNode;
	}
}
