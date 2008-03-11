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
 * Interface for reverse engineering schema parsers.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.engine.database.reverse
 */
interface SchemaParser {

	/**
	 * Gets the database connection.
	 * @return     PDO
	 */
	public function getConnection();

	/**
	 * Sets the database connection.
	 *
	 * @param      PDO $dbh
	 */
	public function setConnection(PDO $dbh);

	/**
	 * Sets the GeneratorConfig to use in the parsing.
	 *
	 * @param      GeneratorConfig $config
	 */
	public function setGeneratorConfig(GeneratorConfig $config);

	/**
	 * Gets a specific propel (renamed) property from the build.
	 *
	 * @param      string $name
	 * @return     mixed
	 */
	public function getBuildProperty($name);

	/**
	 * Gets array of warning messages.
	 * @return     array string[]
	 */
	public function getWarnings();

	/**
	 * Parse the schema and populate passed-in Database model object.
	 *
	 * @param      Database $database
	 */
	public function parse(Database $database);
}
