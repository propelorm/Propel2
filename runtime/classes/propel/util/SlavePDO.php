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
 * PDO connection subclass that provides some enhanced functionality needed by Propel.
 *
 * This class was designed to use in a master/slave replication setup as slave (e.g.
 * read only) connection.
 *
 * The changes that this class makes to the underlying API are:
 * - logging
 * - blocking all writing access with a PropelException
 *
 * @author     Christian Abegg <abegg.ch@gmail.com>
 * @since      2007-01-12
 * @package    propel.util
 */
class SlavePDO extends PDO {

	/**
	 * Overrides PDO::beginTransaction() to prevent errors due to already-in-progress transaction.
	 */
	public function beginTransaction()
	{
		throw new PropelException("No Transactions allowed in SlavePDO");
	}

	/**
	 * Overrides PDO::commit() to only commit the transaction if we are in the outermost
	 * transaction nesting level.
	 */
	public function commit()
	{
		throw new PropelException("No Transactions allowed in SlavePDO");
	}

	/**
	 * Overrides PDO::rollback() to only rollback the transaction if we are in the outermost
	 * transaction nesting level.
	 */
	public function rollback()
	{
		throw new PropelException("No Transactions allowed in SlavePDO");
	}

	/**
	 * Overrides PDO::prepare() to add logging
	 * and switch read/write request
	 */
	public function prepare($sql, $driver_options = array())
	{
		Propel::log($sql, Propel::LOG_DEBUG);
		if ($this->isReadOnly($sql)) {
			return parent::prepare($sql, $driver_options);
		}
		else throw new PropelException("No read access in SlavePDO");
	}

	/**
	 * Overrides PDO::query() to split r/w queries
	 */
	public function query($sql, $fetch = null, $input3=null, $input4=null) {
		if ($this->isReadOnly($sql)) {
			return parent::query($sql, $fetch, $input4, $input4);
		}
		else throw new PropelException("No read access in SlavePDO");
	}

	/**
	 * Overrides PDO::exec() to split r/w queries
	 */
	public function exec($sql) {
		if ($this->isReadOnly($sql)) {
			return parent::exec($sql);
		}
		else throw new PropelException("No read access in SlavePDO");
	}

	/**
	 * Checks if a sql query is read only
	 *
	 * @return     boolean
	 */
	private function isReadOnly($sql) {
		return PropelPDO::isReadOnly($sql);
	}
}
