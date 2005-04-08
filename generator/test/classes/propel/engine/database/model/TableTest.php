<?php

/*
 *  $Id: TableTest.php,v 1.1 2004/07/08 00:23:00 hlellelid Exp $
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
 
require_once 'PHPUnit2/Framework/TestCase.php';
include_once 'propel/engine/database/transform/XmlToAppData.php';

/**
 * Tests for package handling.
 *
 * @author <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version $Id: TableTest.php,v 1.1 2004/07/08 00:23:00 hlellelid Exp $
 */
class TableTest extends PHPUnit2_Framework_TestCase {

    private $xmlToAppData;
    private $appData;

    /**
     * test if the tables get the package name from the properties file
     * 
     */
    public function testIdMethodHandling() {
        $this->xmlToAppData = new XmlToAppData("mysql", "defaultpackage", null);
        $this->appData = $this->xmlToAppData->parseFile(dirname(__FILE__) . "/tabletest-schema.xml");
        $db = $this->appData->getDatabase("iddb");
        $this->assertEquals(IDMethod::NATIVE, $db->getDefaultIdMethod());
        $table = $db->getTable("table_none");
        $this->assertEquals(IDMethod::NO_ID_METHOD, $table->getIdMethod());
        $table2 = $db->getTable("table_native");
        $this->assertEquals(IDMethod::NATIVE, $table2->getIdMethod());
    }
}
