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
 * This is used to connect to a MSSQL database.  For now, this class
 * simply extends the adaptor for Sybase.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @version    $Revision$
 * @package    propel.adapter
 */
class DBMSSQL extends DBSybase {
	// no difference currently

	/**
	 * @see        DBAdapter::applyLimit()
	 */
	public function applyLimit(&$sql, $offset, $limit)
	{
		throw new PropelException("applyLimit() not yet implemented for MSSQL");
		
		/*
		 * TODO - rewrite the incoming SQL to make it look like the SQL below.
		 * See http://propel.phpdb.org/trac/ticket/453 for the original article link.
		 * 
		 * SELECT * FROM (
		 * 	SELECT TOP x * FROM (
		 * 		SELECT TOP y fields 
		 * 		FROM table
		 * 		WHERE conditions
		 * 		ORDER BY table.field ASC) as foo
		 * ORDER BY field DESC) as bar
		 * ORDER BY field ASC
		 */
 	}

	public function random($seed=NULL)
	{
		return 'NEWID()';
	}

}
