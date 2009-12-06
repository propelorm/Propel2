<?php

/*
 *  $Id: PropelCriteria.php 1351 2009-12-04 22:05:01Z francois $
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
 * Factory for model queries
 * 
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.query
 */
class PropelQuery
{
	public static function from($queryClassAndAlias)
	{
		list($class, $alias) = ModelCriteria::getClassAndAlias($queryClassAndAlias);
		$queryClass = $class . 'Query';
		if (!class_exists($queryClass)) {
			throw new PropelException('Cannot find a query class for ' . $class);
		}
		$query = new $queryClass();
		if ($alias !== null) {
			$query->setModelAlias($alias);
		}
		return $query;
	}
}
