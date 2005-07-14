<?php

/**
 * Control script which converts a properties file (in XML or INI-style .properties) into PHP array.
 *
 * This conversion exists for performance reasons only.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @version $Revision: 1.2 $
 */
 
 // we expect to have:
 //		$propertiesFile - path to xml/ini file.


$pfile = new PhingFile($propertiesFile);
if (!$pfile->exists()) {	
    throw new BuildException("Property file does not exist: $propertiesFile");
}

$format = array_pop(explode('.', $pfile->getName()));

switch($format) {
    case 'xml':
        include 'xml.tpl';
        break;        
    default:
        throw new BuildException("Propel now only supports the XML runtime conf format (expected to find a runtime file with .xml extension).");
}