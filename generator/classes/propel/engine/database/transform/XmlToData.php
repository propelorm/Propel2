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

require_once 'phing/parser/AbstractHandler.php';

/**
 * A Class that is used to parse an input xml schema file and creates an
 * AppData object.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Fedor Karpelevitch <fedor.karpelevitch@home.com> (Torque)
 * @version $Revision$
 * @package propel.engine.database.transform
 */
class XmlToData extends AbstractHandler {

    private $database;
    private $data;

    private $encoding;

    public $parser;

    const DEBUG = false;

    /**
     * Construct new XmlToData class.
     *
     * This class is passed the Database object so that it knows what to expect from
     * the XML file.
     *
     * @param Database $database
     */
    public function __construct(Database $database, $encoding = 'iso-8859-1')
    {
        $this->database = $database;
        $this->encoding = $encoding;
    }

    /**
     *
     */
    public function parseFile($xmlFile)
    {
        try {

            $this->data = array();

			$domDocument = new DomDocument('1.0', 'UTF-8');
			$domDocument->load($xmlFile);

			$xsl = new XsltProcessor();
			$xsl->importStyleSheet(DomDocument::load(realpath(dirname(__FILE__) . "/xsl/database.xsl")));
			$transformed = $xsl->transformToDoc($domDocument);

			$xmlFile = $xmlFile . "transformed.xml";
			$transformed->save($xmlFile);

			if ($transformed->getElementsByTagName("database")->item(0)->getAttribute("noxsd") != "true")
				if (!$transformed->schemaValidate(realpath(dirname(__FILE__) . "/xsd/database.xsd")))
					throw new EngineException("XML schema does not validate, sorry...");

            try {
                $fr = new FileReader($xmlFile);
            } catch (Exception $e) {
                $f = new PhingFile($xmlFile);
                throw new BuildException("XML File not found: " . $f->getAbsolutePath());
            }

            $br = new BufferedReader($fr);

            $this->parser = new ExpatParser($br);
            $this->parser->parserSetOption(XML_OPTION_CASE_FOLDING, 0);
            $this->parser->setHandler($this);

            try {
                $this->parser->parse();
            } catch (Exception $e) {
                print $e->getMessage() . "\n";
                $br->close();
            }
            $br->close();
        } catch (Exception $e) {
            print $e->getMessage() . "\n";
            print $e->getTraceAsString();
        }

        return $this->data;
    }

    /**
     * Handles opening elements of the xml file.
     */
    public function startElement($name, $attributes)
    {
        try {
            if ($name == "dataset") {
                // we don't do anything w/ <dataset> tag right now.
            } else {
                $table = $this->database->getTableByPhpName($name);

                $this->columnValues = array();
                foreach($attributes as $name => $value) {
                    $col = $table->getColumnByPhpName($name);
                    $this->columnValues[] = new ColumnValue($col, iconv('utf-8',$this->encoding, $value));
                }
                $this->data[] = new DataRow($table, $this->columnValues);
            }
        } catch (Exception $e) {
            print $e;
            throw $e;
        }
    }


    /**
     * Handles closing elements of the xml file.
     *
     * @param $name The local name (without prefix), or the empty string if
     *         Namespace processing is not being performed.
     */
    public function endElement($name)
    {
        if (self::DEBUG) {
            print("endElement(" . $name . ") called\n");
        }
    }

} // XmlToData

    /**
     * "inner class"
     * @package propel.engine.database.transform
     */
    class DataRow
    {
        private $table;
        private $columnValues;

        public function __construct(Table $table, $columnValues)
        {
            $this->table = $table;
            $this->columnValues = $columnValues;
        }

        public function getTable()
        {
            return $this->table;
        }

        public function getColumnValues()
        {
            return $this->columnValues;
        }
    }

    /**
     * "inner" class
     * @package propel.engine.database.transform
     */
    class ColumnValue {

        private $col;
        private $val;

        public function __construct(Column $col, $val)
        {
            $this->col = $col;
            $this->val = $val;
        }

        public function getColumn()
        {
            return $this->col;
        }

        public function getValue()
        {
            return $this->val;
        }
    }
