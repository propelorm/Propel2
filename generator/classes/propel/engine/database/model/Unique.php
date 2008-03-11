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

include_once 'propel/engine/database/model/Index.php';

/**
 * Information about unique columns of a table.  This class assumes
 * that in the underlying RDBMS, unique constraints and unique indices
 * are roughly equivalent.  For example, adding a unique constraint to
 * a column also creates an index on that column (this is known to be
 * true for MySQL and Oracle).
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author     Daniel Rall <dlr@collab.net> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class Unique extends Index {

	/**
	 * Returns <code>true</code>.
	 */
	public function isUnique()
	{
		return true;
	}

	/**
	 * @see        XMLElement::appendXml(DOMNode)
	 */
	public function appendXml(DOMNode $node)
	{
		$doc = ($node instanceof DOMDocument) ? $node : $node->ownerDocument;

		$uniqueNode = $node->appendChild($doc->createElement('unique'));
		$uniqueNode->setAttribute('name', $this->getName());
		$columns = $this->getColumns();
		foreach ($this->getColumns() as $colname) {
			$uniqueColNode = $uniqueNode->appendChild($doc->createElement('unique-column'));
			$uniqueColNode->setAttribute('name', $colname);
		}

		foreach ($this->vendorInfos as $vi) {
			$vi->appendXml($uniqueNode);
		}
	}


}
