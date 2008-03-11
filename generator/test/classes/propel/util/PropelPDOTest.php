<?php
/*
 *  $Id: PropelDateTimeTest.php 784 2007-11-08 10:15:50Z heltem $
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

require_once 'bookstore/BookstoreTestBase.php';
require_once 'propel/util/PropelPDO.php';

/**
 * Test for PropelPDO subclass.
 *
 * @package    propel.util
 */
class PropelPDOTest extends BookstoreTestBase
{

	public function testSetAttribute()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$this->assertFalse($con->getAttribute(PropelPDO::PROPEL_ATTR_CACHE_PREPARES));
		$con->setAttribute(PropelPDO::PROPEL_ATTR_CACHE_PREPARES, true);
		$this->assertTrue($con->getAttribute(PropelPDO::PROPEL_ATTR_CACHE_PREPARES));

		$con->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
		$this->assertEquals(PDO::CASE_LOWER, $con->getAttribute(PDO::ATTR_CASE));
	}

}
