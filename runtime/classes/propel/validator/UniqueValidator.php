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
 * A validator for unique column names.
 *
 * <code>
 *   <column name="username" type="VARCHAR" size="25" required="true" />
 *
 *   <validator column="username">
 *     <rule name="unique" message="Username already exists !" />
 *   </validator>
 * </code>
 *
 * @author     Michael Aichler <aichler@mediacluster.de>
 * @version    $Revision$
 * @package    propel.validator
 */
class UniqueValidator implements BasicValidator
{

	/**
	 * @see        BasicValidator::isValid()
	 */
	public function isValid (ValidatorMap $map, $str)
	{
		$column = $map->getColumn();

		$c = new Criteria();
		$c->add($column->getFullyQualifiedName(), $str, Criteria::EQUAL);

		$isValid = false;

		$table = $column->getTable()->getPhpName();

		$clazz = $table . 'Peer';
		$count = call_user_func(array($clazz, 'doCount'), $c);

		$isValid = ($count === 0);

		return $isValid;
	}
}
