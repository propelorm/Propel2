<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Issues;

use Nature;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\TestCase;
use Recherche;
use Category;
use RechercheNature;

/**
 * This test proves the bug described in https://github.com/propelorm/Propel2/issues/941.
 *
 * @group database
 */
class Issue941Test extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('\Nature')) {
            $schema = '
            <database>
                <table name="recherche" phpName="Recherche">
                    <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
                </table>

                <table name="category" phpName="Category">
                    <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
                </table>

                <table name="recherche_nature" phpName="RechercheNature" isCrossRef="true">
                    <column name="recherche_id" type="integer" primaryKey="true"/>
                    <column name="nature_id" type="integer" primaryKey="true"/>
                    <column name="category_id" type="integer"/>

                    <foreign-key foreignTable="recherche" onDelete="cascade">
                        <reference local="recherche_id" foreign="id"/>
                    </foreign-key>
                    <foreign-key foreignTable="nature">
                        <reference local="nature_id" foreign="id"/>
                    </foreign-key>

                    <foreign-key foreignTable="category">
                        <reference local="category_id" foreign="id"/>
                    </foreign-key>

                </table>

                <table name="nature" phpName="Nature">
                    <column name="id" type="integer" primaryKey="true" autoIncrement="true"/>
                </table>

            </database>
            ';
            QuickBuilder::buildSchema($schema);
        }
    }

    /**
     * @return void
     */
    public function testIssue941()
    {
        $nature = new Nature();
        $nature->save();

        $category = new Category();
        $category->save();

        // RechercheNature
        $rechercheNature = new RechercheNature();
        $rechercheNature->setNatureId($nature->getId());
        $rechercheNature->setCategoryId($category->getId());

        // Collection
        $collection = new ObjectCollection();
        $collection->setModel('\RechercheNature');
        $collection->setData([$rechercheNature]);

        // Recherche
        $recherche = new Recherche();
        $recherche->setRechercheNatures($collection);

        $countBeforeSave = $recherche->countRechercheNatures();

        $recherche->save();

        $countAfterSave = $recherche->countRechercheNatures();

        $this->assertEquals($countBeforeSave, $countAfterSave);
    }
}
