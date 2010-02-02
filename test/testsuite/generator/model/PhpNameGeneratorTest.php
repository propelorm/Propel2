<?php

/*
 *  $Id: PhpNameGeneratorTest.php 1463 2010-01-18 21:07:29Z francois $
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

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'model/PhpNameGenerator.php';


/**
 * Tests for PhpNamleGenerator
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision$
 * @package    generator.model
 */
class PhpNameGeneratorTest extends PHPUnit_Framework_TestCase
{
	public static function testPhpnameMethodDataProvider()
	{
		return array(
			array('foo', 'Foo'),
			array('Foo', 'Foo'),
			array('FOO', 'FOO'),
			array('123', '123'),
			array('foo_bar', 'FooBar'),
			array('bar_1', 'Bar1'),
			array('bar_0', 'Bar0'),
			array('my_CLASS_name', 'MyCLASSName'),
		);
	}
	
	/**
	 * @dataProvider testPhpnameMethodDataProvider
	 */
	public function testPhpnameMethod($input, $output)
	{
		$generator = new TestablePhpNameGenerator();
		$this->assertEquals($output, $generator->phpnameMethod($input));
	}

}

class TestablePhpNameGenerator extends PhpNameGenerator
{
	public function phpnameMethod($schemaName)
	{
		return parent::phpnameMethod($schemaName);
	}
}