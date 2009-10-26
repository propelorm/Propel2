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
 * PropelConfigurationIterator is used internally by PropelConfiguration to
 * build a flat array from nesting configuration arrays.
 *
 * @author     Veikko Mäkinen <veikko@veikko.fi>
 * @version    $Revision$
 * @package    propel
 */
class PropelConfigurationIterator extends RecursiveIteratorIterator
{
	/**
	 * Node is a parent node
	 */
	const NODE_PARENT = 0;

	/**
	 * Node is an actual configuration item
	 */
	const NODE_ITEM = 1;

	/**
	 * Namespace stack when recursively iterating the configuration tree
	 *
	 * @var        array
	 */
	protected $namespaceStack = array();

	/**
	 * Current node type. Possible values: null (undefined), self::NODE_PARENT or self::NODE_ITEM
	 *
	 * @var        int
	 */
	protected $nodeType = null;

	/**
	 * Get current namespace
	 *
	 * @return     string
	 */
	public function getNamespace()
	{
		return implode('.', $this->namespaceStack);
	}

	/**
	 * Get current node type.
	 *
	 * @see        http://www.php.net/RecursiveIteratorIterator
	 * @return     int
	 *             - null (undefined)
	 *             - self::NODE_PARENT
	 *             - self::NODE_ITEM
	 */
	public function getNodeType()
	{
		return $this->nodeType;
	}

	/**
	 * Get the current element
	 *
	 * @see        http://www.php.net/RecursiveIteratorIterator
	 * @return     mixed
	 */
	public function current()
	{
		$current = parent::current();
		if (is_array($current)) {
			$this->namespaceStack[] = $this->key();
			$this->nodeType = self::NODE_PARENT;
		}
		else {
			$this->nodeType = self::NODE_ITEM;
		}

		return $current;
	}

	/**
	 * Called after current child iterator is invalid and right before it gets destructed.
	 *
	 * @see        http://www.php.net/RecursiveIteratorIterator
	 */
	public function endChildren()
	{
		if ($this->namespaceStack) {
			array_pop($this->namespaceStack);
		}
	}

}

?>
