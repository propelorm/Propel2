<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Exception;
use Propel\Generator\Util\QuickBuilder;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/1033.
 *
 * @group database
 */
class Issue1033Test extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!class_exists('\Base\Issue1033Book')) {
            $schema = <<<EOF
<database>
    <table name="Issue1033Book">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" description="Book Id"/>
        <column name="title" type="VARCHAR" required="true" description="Book Title" primaryString="true"/>
    </table>
</database>
EOF;
            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            $builder->buildClasses(null, true);
        }
    }

    /**
     * @return void
     */
    public function testSerialize()
    {
        try {
            $o = new Issue1033Book();
            $unserializedBook = unserialize(serialize($o));
            $noExceptionThrown = true;
        } catch (Exception $e) {
            $noExceptionThrown = false;
        }

        $this->assertTrue($noExceptionThrown);
    }
}
