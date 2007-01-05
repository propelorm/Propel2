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
 * MapBuilders are classes that construct a model of a database at runtime.
 *
 * MapBuilders support a single database, so this class essentially serves as
 * a wrapper around the DatabaseMap class.  This interface can be used for any
 * class that needs to construct a runtime database model; by default in Propel
 * the MapBuilder.tpl generates a class for your datamodel that implements this
 * interface and re-creates your database using the DatabaseMap, TableMap,
 * ColumnMap, and ValidatorMap classes.
 *
 * GENERAL NOTE
 * ------------
 * The propel.map classes are abstract building-block classes for modeling
 * the database at runtime.  These classes are similar (a lite version) to the
 * propel.engine.database.model classes, which are build-time modeling classes.
 * These classes in themselves do not do any database metadata lookups, but instead
 * are used by the MapBuilder classes that were generated for your datamodel. The
 * MapBuilder that was created for your datamodel build a representation of your
 * database by creating instances of the DatabaseMap, TableMap, ColumnMap, etc.
 * classes. See propel/templates/om/php5/MapBuilder.tpl and the classes generated
 * by that template for your datamodel to further understand how these are put
 * together.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Hans Lellelid <hans@xmpl.org> (Torque)
 * @version    $Revision$
 * @package    propel.map
 */
interface MapBuilder {

	/**
	 * Build up the database mapping.
	 * @return     void
	 * @throws     Exception Couldn't build mapping.
	 */
	function doBuild();

	/**
	 * Tells us if the database mapping is built so that we can avoid
	 * re-building it repeatedly.
	 *
	 * @return     boolean Whether the DatabaseMap is built.
	 */
	function isBuilt();

	/**
	 * Gets the database mapping this map builder built.
	 *
	 * @return     DatabaseMap A DatabaseMap.
	 */
	function getDatabaseMap();
}
