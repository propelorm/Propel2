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

include_once 'propel/engine/EngineException.php';
include_once 'propel/engine/database/model/NameGenerator.php';
include_once 'propel/engine/database/model/PhpNameGenerator.php';
include_once 'propel/engine/database/model/ConstraintNameGenerator.php';

/**
 * A name generation factory.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @version    $Revision$
 * @package    propel.engine.database.model
 */
class NameFactory {

	/**
	 * The class name of the PHP name generator.
	 */
	const PHP_GENERATOR = 'PhpNameGenerator';

	/**
	 * The fully qualified class name of the constraint name generator.
	 */
	const CONSTRAINT_GENERATOR = 'ConstraintNameGenerator';

	/**
	 * The single instance of this class.
	 */
	private static $instance;

	/**
	 * The cache of <code>NameGenerator</code> algorithms in use for
	 * name generation, keyed by fully qualified class name.
	 */
	private static $algorithms = array();

	/**
	 * Factory method which retrieves an instance of the named generator.
	 *
	 * @param      name The fully qualified class name of the name
	 * generation algorithm to retrieve.
	 */
	protected static function getAlgorithm($name)
	{
		if (!isset(self::$algorithms[$name])) {
			self::$algorithms[$name] = new $name();
		}
		return self::$algorithms[$name];
	}

	/**
	 * Given a list of <code>String</code> objects, implements an
	 * algorithm which produces a name.
	 *
	 * @param      string $algorithmName The fully qualified class name of the {@link NameGenerator}
	 *             implementation to use to generate names.
	 * @param      array $inputs Inputs used to generate a name.
	 * @return     The generated name.
	 * @throws     EngineException
	 */
	public static function generateName($algorithmName, $inputs)
	{
		$algorithm = self::getAlgorithm($algorithmName);
		return $algorithm->generateName($inputs);
	}
}
