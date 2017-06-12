<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Issues;

use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/1345.
 * When trying to add an instance without primary key, it should NOT be stored.
 * Otherwise for any instance without primary key the latest added one will be returned by its empty key.
 */
class Issue1345Test extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        BookTableMap::clearInstancePool();
    }

    public function testAddingToInstancePoolWithoutPrimaryKey()
    {
        BookTableMap::addInstanceToPool(new \stdClass(), '');

        $this->assertNull(BookTableMap::getInstanceFromPool(''));
    }
}
