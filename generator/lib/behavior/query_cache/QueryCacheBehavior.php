<?php

/*
 *	$Id: QueryCacheBehavior.php 1471 2010-01-20 14:31:12Z francois $
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
 * Speeds up queries on a model by caching the query
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.behavior.cacheable
 */
class QueryCacheBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'backend'     => 'apc',
		'lifetime'    => 3600,
	);
	
	public function queryAttributes($builder)
	{
		$script = "protected \$queryKey = '';
";
		switch ($this->getParameter('backend')) {
			case 'backend':
				$script .= "protected static \$cacheBackend = array();
			";
				break;
			case 'apc':
				break;
			case 'custom':
			default:
				$script .= "protected static \$cacheBackend;
			";
				break;
		}
		
		return $script;
	}
	
	public function queryMethods($builder)
	{
		$script = '';
		$this->addSetQueryKey($script);
		$this->addGetQueryKey($script);
		$this->addCacheContains($script);
		$this->addCacheFetch($script);
		$this->addCacheStore($script);
		$this->addGetSelectStatement($script);
		
		return $script;
	}

	protected function addSetQueryKey(&$script)
	{
		$script .= "
public function setQueryKey(\$key)
{
	\$this->queryKey = \$key;
	return \$this;
}
";
	}
	
	protected function addGetQueryKey(&$script)
	{
		$script .= "
public function getQueryKey()
{
	return \$this->queryKey;
}
";
	}
	
	protected function addCacheContains(&$script)
	{
		$script .= "
public function cacheContains(\$key)
{";
		switch ($this->getParameter('backend')) {
			case 'apc':
				$script .= "
	return apc_fetch(\$key);";
				break;
			case 'array':
				$script .= "
	return isset(self::\$cacheBackend[\$key]);";
				break;
			case 'custom':
			default:
				$script .= "
	throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');";
				break;

		}
		$script .= "
}
";
	}
	
	protected function addCacheStore(&$script)
	{
		$script .= "
public function cacheStore(\$key, \$value, \$lifetime = " .$this->getParameter('lifetime') . ")
{";
		switch ($this->getParameter('backend')) {
			case 'apc':
				$script .= "
	apc_store(\$key, \$value, \$lifetime);";
				break;
			case 'array':
				$script .= "
	self::\$cacheBackend[\$key] = \$value;";
				break;
			case 'custom':
			default:
				$script .= "
	throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');";
				break;
		}
		$script .= "
}
";
	}
	
	protected function addCacheFetch(&$script)
	{
		$script .= "
public function cacheFetch(\$key)
{";
		switch ($this->getParameter('backend')) {
			case 'apc':
				$script .= "
	return apc_fetch(\$key);";
				break;
			case 'array':
				$script .= "
	return isset(self::\$cacheBackend[\$key]) ? self::\$cacheBackend[\$key] : null;";
				break;
			case 'custom':
			default:
				$script .= "
	throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');";
				break;
		}
		$script .= "
}
";
	}
	
	protected function addGetSelectStatement(&$script)
	{
		$script .= "
protected function getSelectStatement(\$con = null)
{
	\$dbMap = Propel::getDatabaseMap(\$this->getDbName());
	\$db = Propel::getDB(\$this->getDbName());
  if (\$con === null) {
		\$con = Propel::getConnection(\$this->getDbName(), Propel::CONNECTION_READ);
	}
	
	// we may modify criteria, so copy it first
	\$criteria = clone \$this;

	if (!\$criteria->hasSelectClause()) {
		\$criteria->addSelfSelectColumns();
	}
	
	\$con->beginTransaction();
	try {
		\$criteria->basePreSelect(\$con);
		\$key = \$criteria->getQueryKey();
		if (\$key && \$criteria->cacheContains(\$key)) {
			\$params = \$criteria->getParams();
			\$sql = \$criteria->cacheFetch(\$key);
		} else {
			\$params = array();
			\$sql = BasePeer::createSelectSql(\$criteria, \$params);
			if (\$key) {
				\$criteria->cacheStore(\$key, \$sql);
			}
		}
		\$stmt = \$con->prepare(\$sql);
		BasePeer::populateStmtValues(\$stmt, \$params, \$dbMap, \$db);
		\$stmt->execute();
		\$con->commit();
	} catch (PropelException \$e) {
		\$con->rollback();
		throw \$e;
	}
	
	return \$stmt;
}
";
	}

}