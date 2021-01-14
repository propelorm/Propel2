<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\NameFactory;
use Propel\Generator\Model\NameGeneratorInterface;
use Propel\Generator\Model\Schema;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Tests\Helpers\BaseTestCase;

/**
 * <p>Unit tests for class <code>NameFactory</code> and known
 * <code>NameGeneratorInterface</code> implementations.</p>
 *
 * <p>To add more tests, add entries to the <code>ALGORITHMS</code>,
 * <code>INPUTS</code>, and <code>OUTPUTS</code> arrays, and code to
 * the <code>makeInputs()</code> method.</p>
 *
 * <p>This test assumes that it's being run using the MySQL database
 * adapter, <code>DBMM</code>. MySQL has a column length limit of 64
 * characters.</p>
 *
 * @author <a href="mailto:dlr@collab.net">Daniel Rall</a>
 * @version $Id$
 */
class NameFactoryTest extends BaseTestCase
{
    public const DATABASE_TYPE = 'mysql';

    /**
     * @var array
     * The list of known name generation algorithms, specified as the
     * fully qualified class names to <code>NameGeneratorInterface</code>
     * implementations.
     */
    private static $ALGORITHMS = [NameFactory::CONSTRAINT_GENERATOR, NameFactory::PHP_GENERATOR];

    /**
     * @var array
     * Two dimensional arrays of inputs for each algorithm.
     */
    private $inputs = [];

    /**
     * @var array
     * Given the known inputs, the expected name outputs.
     */
    private $outputs = [];

    /**
     * Used as an input.
     */
    private $database;

    /**
     * Creates a string of the specified length consisting entirely of
     * the character <code>A</code>. Useful for simulating table
     * names, etc.
     *
     * @param int $len the number of characters to include in the string
     *
     * @return string A string of length <code>len</code> with every character an 'A'
     */
    private static function makeString($len)
    {
        $buf = '';
        for ($i = 0; $i < $len; $i++) {
            $buf .= 'A';
        }

        return $buf;
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->inputs = [
            [
                [self::makeString(61), 'I', 1],
                [self::makeString(61), 'I', 2],
                [self::makeString(65), 'I', 3],
                [self::makeString(4), 'FK', 1],
                [self::makeString(5), 'FK', 2],
            ],
            [
                ['MY_USER', NameGeneratorInterface::CONV_METHOD_UNDERSCORE],
                ['MY_USER', NameGeneratorInterface::CONV_METHOD_PHPNAME],
                ['MY_USER', NameGeneratorInterface::CONV_METHOD_NOCHANGE],
            ],
        ];

        $this->outputs = [
            [
                self::makeString(60) . '_I_1',
                self::makeString(60) . '_I_2',
                self::makeString(60) . '_I_3',
                self::makeString(4) . '_FK_1',
                self::makeString(5) . '_FK_2',
            ],
            ['MyUser', 'MYUSER', 'MY_USER'],
        ];
        $schema = new Schema(new MysqlPlatform());
        $this->database = new Database();
        $schema->addDatabase($this->database);
    }

    /**
     * @return void
     */
    public function testNames()
    {
        $algosCount = count(self::$ALGORITHMS);
        for ($algoIndex = 0; $algoIndex < $algosCount; $algoIndex++) {
            $algo = self::$ALGORITHMS[$algoIndex];
            $algoInputs = $this->inputs[$algoIndex];
            $nbAlgoInputs = count($algoInputs);
            for ($i = 0; $i < $nbAlgoInputs; $i++) {
                $inputs = $this->makeInputs($algo, $algoInputs[$i]);
                $generated = NameFactory::generateName($algo, $inputs);
                $expected = $this->outputs[$algoIndex][$i];
                $this->assertEquals($expected, $generated, 'Algorithm ' . $algo . ' failed to generate an unique name');
            }
        }
    }

    /**
     * Creates the list of arguments to pass to the specified type of
     * <code>NameGeneratorInterface</code> implementation.
     *
     * @param string $algo The class name of the <code>NameGeneratorInterface</code> to
     * create an argument list for.
     * @param array $inputs The (possibly partial) list inputs from which to
     * generate the final list.
     *
     * @return array The list of arguments to pass to the <code>NameGeneratorInterface</code>
     */
    private function makeInputs($algo, array $inputs)
    {
        if (NameFactory::CONSTRAINT_GENERATOR === $algo) {
            array_unshift($inputs, $this->database);
        }

        return $inputs;
    }
}
