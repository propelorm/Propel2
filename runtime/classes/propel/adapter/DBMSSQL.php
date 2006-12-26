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
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @version $Revision$
 * @package propel.adapter
 */
class DBMSSQL extends DBSybase {
	// no difference currently

	/**
	 * @see DBAdapter::applyLimit()
	 */
	public function applyLimit(&$sql, $offset, $limit)
	{
 		throw new PropelException("LIMIT/OFFSET support in MSSQL not yet implemented...");

 		/*
 		 Here's one solution:

		CREATE PROCEDURE [owner].[LimitSelect]
		@query CHAR (256), -- SQL query, it'd better be a SELECT!
		@offset INT, -- start result set from offset
		@limit INT -- limit the result set of the query
		AS
		-- Execute call to declare a global cursor (node_cursor) for the query passed to the SP
		EXEC ('DECLARE node_cursor CURSOR GLOBAL SCROLL READ_ONLY FOR ' + @query)

		-- open the global cursor declared above
		OPEN node_cursor

		-- tweak the starting values of limit and offset for use in the loop
		SET @offset = @offset + 1
		SET @limit = @limit

		-- advanced the cursor to the offset in the result set
		FETCH ABSOLUTE @offset FROM node_cursor

		-- counter i
		DECLARE @i INTEGER

		SET @i = 0

		-- loop until limit reached by counter i
		WHILE (@i < @limit)
		BEGIN
		-- fetch the next row in the result set and advance counter i
		FETCH NEXT FROM node_cursor
		SET @i = @i + 1
		END

		-- clean finish
		CLOSE node_cursor
		DEALLOCATE node_cursor
		*/
	}
}
