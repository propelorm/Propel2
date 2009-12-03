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
 * Abstract class for query formatter
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
abstract class PropelFormatter
{
	protected
	  $criteria,
	  $class,
	  $peer;
	
	public function setCriteria(ModelCriteria $criteria)
	{
		$this->criteria = $criteria;
	}
	
	public function getCriteria()
	{
		return $this->criteria;
	}
	
	abstract public function format(PDOStatement $stmt);

	abstract public function formatOne(PDOStatement $stmt);
	
	/**
	 * Check that a ModelCriteria was properly set
	 *
	 * @throws    PropelException if no Criteria was set, or if the Criteria set is not an instance of ModelCriteria
	 */
	protected function checkCriteria()
	{
		if (null === $this->criteria || !$this->criteria instanceof ModelCriteria) {
			throw new PropelException('A formatter needs a ModelCriteria. Use PropelFormatter::setCriteria() to set one');
		}
		$this->class = $this->getCriteria()->getModelName();
		$this->peer = $this->getCriteria()->getModelPeerName();		
	}
}
