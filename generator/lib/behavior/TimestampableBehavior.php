<?php

/*
 *	$Id$
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
 * Gives a model class the ability to track creation and last modification dates
 * Uses two additional columns storing the creation and update date
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.behavior
 */
class TimestampableBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'create_column' => 'created_at',
		'update_column' => 'updated_at'
	);
	
	/**
	 * Add the create_column and update_columns to the current table
	 */
	public function modifyTable()
	{
		if(!$this->getTable()->containsColumn($this->getParameter('create_column'))) {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('create_column'),
				'type' => 'TIMESTAMP'
			));
		}
		if(!$this->getTable()->containsColumn($this->getParameter('update_column'))) {
			$this->getTable()->addColumn(array(
				'name' => $this->getParameter('update_column'),
				'type' => 'TIMESTAMP'
			));
		}
	}
	
	/**
	 * Get the setter of one of the columns of the behavior
	 * 
	 * @param     string $column One of the behavior colums, 'create_column' or 'update_column'
	 * @return    string The related setter, 'setCreatedOn' or 'setUpdatedOn'
	 */
	protected function getColumnSetter($column)
	{
		return 'set' . $this->getColumnForParameter($column)->getPhpName();
	}
	
	/**
	 * Add code in ObjectBuilder::preSave
	 *
	 * @return    string The code to put at the hook
	 */
	public function preSave()
	{
		return "if (!\$this->isColumnModified(" . $this->getColumnForParameter('update_column')->getConstantName() . ")) {
	\$this->" . $this->getColumnSetter('update_column') . "(time());
}";
	}
	
	/**
	 * Add code in ObjectBuilder::preInsert
	 *
	 * @return    string The code to put at the hook
	 */
	public function preInsert()
	{
		return "if (!\$this->isColumnModified(" . $this->getColumnForParameter('create_column')->getConstantName() . ")) {
	\$this->" . $this->getColumnSetter('create_column') . "(time());
}";
	}

	public function objectMethods($builder)
	{
		return "
/**
 * Mark the current object so that the update date doesn't get updated during next save
 *
 * @return     " . $builder->getStubObjectBuilder()->getClassname() . " The current object (for fluent API support)
 */
public function keepUpdateDateUnchanged()
{
	\$this->modifiedColumns[] = " . $this->getColumnForParameter('update_column')->getConstantName() . ";
	return \$this;
}
";
	}
	
	public function queryMethods($builder)
	{
		$queryClassName = $builder->getStubQueryBuilder()->getClassname();
		$updateColumnConstant = $this->getColumnForParameter('update_column')->getConstantName();
		$createColumnConstant = $this->getColumnForParameter('create_column')->getConstantName();
		return "
/**
 * Filter by the latest updated
 *
 * @param      int \$nbDays Maximum age of the latest update in days
 *
 * @return     $queryClassName The current query, for fuid interface
 */
public function recentlyUpdated(\$nbDays = 7)
{
	return \$this->addUsingAlias($updateColumnConstant, time() - \$nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
}

/**
 * Filter by the latest created
 *
 * @param      int \$nbDays Maximum age of in days
 *
 * @return     $queryClassName The current query, for fuid interface
 */
public function recentlyCreated(\$nbDays = 7)
{
	return \$this->addUsingAlias($createColumnConstant, time() - \$nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
}

/**
 * Order by update date desc
 *
 * @return     $queryClassName The current query, for fuid interface
 */
public function lastUpdatedFirst()
{
	return \$this->addDescendingOrderByColumn($updateColumnConstant);
}

/**
 * Order by update date asc
 *
 * @return     $queryClassName The current query, for fuid interface
 */
public function firstUpdatedFirst()
{
	return \$this->addAscendingOrderByColumn($updateColumnConstant);
}

/**
 * Order by create date desc
 *
 * @return     $queryClassName The current query, for fuid interface
 */
public function lastCreatedFirst()
{
	return \$this->addDescendingOrderByColumn($createColumnConstant);
}

/**
 * Order by create date asc
 *
 * @return     $queryClassName The current query, for fuid interface
 */
public function firstCreatedFirst()
{
	return \$this->addAscendingOrderByColumn($createColumnConstant);
}
";
	}
}