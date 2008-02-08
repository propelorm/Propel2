<?php
/*
This PHP5 script will load the transform a DB Designer 4 database model to the
propel database schema file format
*/

// load the DB Designer 4 XML
$xml = new DOMDocument;
$xml->load('model.xml');

// load the transformation stylesheet
$xsl = new DOMDocument;
$xsl->load('dbd2propel.xsl');

$proc = new XSLTProcessor();
// attach the xsl rules
$proc->importStyleSheet($xsl);

$schema_xml = $proc->transformToXML($xml);

file_put_contents('schema.xml', $schema_xml);
