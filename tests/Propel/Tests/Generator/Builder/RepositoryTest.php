<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder;

use Propel\Common\Config\ConfigurationManager;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;
use Propel\Tests\TestCaseFixturesDatabase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 */
class RepositoryTest extends TestCase
{
    public function testBla()
    {
        return;
        $configuration = new \Propel\Runtime\Configuration('/Users/marc/Propel2/propel.yml');
        $session = $configuration->getSession();

        if (!class_exists('\Brand')) {
            $schema = '
    <database name="default">
        <entity name="Car">
            <field name="id" type="integer" primaryKey="true" autoIncrement="true"/>
            <field name="name" type="varchar" size="250"/>
            <relation field="brand" target="Brand" />
        </entity>
        <entity name="SuperCar">
            <field name="superField" type="varchar" size="250"/>
            <behavior name="concrete_inheritance">
                <parameter name="extends" value="Car" />
                <parameter name="copy_data_to_parent" value="false" />
              </behavior>
        </entity>
    </database>
        ';

            $con = $configuration->getConnectionManager('default')->getWriteConnection();
            $builder = new QuickBuilder();
            $builder->setSchema($schema);
            $builder->setPlatform(new MysqlPlatform());
            $builder->buildSQL($con);
            $builder->buildClasses(null, true);
            $builder->registerEntities($configuration);

            $v = $configuration->getEntitiesForDatabase('default');
        }

        $db = $configuration->getDatabase('default');

//        $serviceContainer = $configuration->buildRuntimeConfiguration();

        // builRuntimeConfiguration will be then in bookstore-conf.php for example
        // for active record
        // \Propel\Runtime\Propel::setActiveRecordSessionFactory($configuration->getSessionFactory());
        // \Propel\Runtime\Propel::getActiveRecordSession()--> return $this->sessionFactory->getLastSession())

        /** @var \Brand $ford */
        $con = $configuration->getConnectionManager('default')->getWriteConnection();

        $carRepository = $configuration->getRepository('\Car');
        $superCarRepository = $configuration->getRepository('\Superar');


        $con->prepare('DELETE FROM car')->execute();
        $con->prepare('DELETE FROM brand')->execute();
        $con->prepare('ALTER TABLE brand AUTO_INCREMENT = 1;')->execute();
        $con->prepare('ALTER TABLE car AUTO_INCREMENT = 1;')->execute();
//
        $start = microtime(true);
        $ford = new \Brand;
        $ford->setName('Ford USA');

        $tesla = new \Brand;
        $tesla->setName('Tesla');

        $mustang = new \Car();
        $mustang->setName('Mustang');
        $mustang->setBrand($ford);

        $modelS = new \Car();
        $modelS->setName('Model S');
        $modelS->setBrand($tesla);

        $session->persist($mustang);
        $session->persist($ford);
        $session->persist($tesla);
        $session->persist($modelS);

        $session->commit();

        $this->assertEquals(1, $ford->getId());
        $this->assertEquals(1, $mustang->getId());

        /** @var \Base\BaseCarRepository $carRepository */
        $carRepository = $configuration->getRepository('\Car');

//        for ($i= 0; $i< 1000; $i++) {
//            $ford = $carRepository->find(1);
//        }

        $query = $carRepository->createQuery();
        $query->joinBrand();
        $query->with('brand');
        var_dump($query->getEntityMap());
        var_dump($query->find());
        echo sprintf("took %s \n", microtime(true) - $start);
//        var_dump($carRepository->find(1));

//        var_dump($ford);

////        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
//        for ($i= 0; $i< 3; $i++) {
//            $ford = new \Brand;
//            $ford->setName('Ford USA ' .$i);
//
//            $mustang = new \Car();
//            $mustang->setName('Mustang ' . $i);
//            $mustang->setBrand($ford);
//
//            $session->persist($mustang);
//            $session->persist($ford);
//        }
//
//        $start = microtime(true);
//        $session->commit();
//
//        echo sprintf("took %s \n", microtime(true) - $start);
//        $this->stop();
    }

//
//    function stop() {
//        global $memory, $time;
//
//        echo sprintf(" %11s | %6.3f |\n", number_format(memory_get_usage(true) - $memory), (microtime(true) - $time));
//        $xhprof_data = xhprof_disable();
//
//        $XHPROF_ROOT = "/Users/marc/bude/xhprof/";
//        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
//        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
//
//        $xhprof_runs = new \XHProfRuns_Default();
//        $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");
//
//        echo "http://bude/xhprof/xhprof_html/index.php?run={$run_id}&source=xhprof_testing\n";
//        exit;
//    }

}