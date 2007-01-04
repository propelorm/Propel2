<?

function __autoload($classname) {
	$filename = str_replace ('_', '/', $classname) . '.php';
	require_once $filename;
}

/**
 * Required packages:
 *
 * - Propel Bookstore project (tested only with mysql, not sqlite)
 * - patForms (http://www.php-tools.net/site.php?file=patForms)
 * - patTemplate (http://www.php-tools.net/site.php?file=patTemplate)
 * - Xml_Serializer (http://pear.php.net/package/XML_Serializer)
 *
 * Installation:
 *
 * In theorie, it should work to
 *
 * - download the files from svn/propel/contrib/pat
 * - save them anywhere in your servers docroot
 * - tweak the following settings and
 * - run this file
 */

// change these according to your setup

$pathToBookstore = 'f:/test/propel'; // omit bookstore/ here
$pathToPear = 'f:/pear';
$pathToPat = 'f:/pear/pat';

$path = PATH_SEPARATOR . $pathToBookstore . PATH_SEPARATOR . $pathToPat;
set_include_path(get_include_path() . $path);

// change these according to your propel settings
$classname = 'book';
$path = './patForms/res';
$propelConfFilename = 'conf/bookstore-conf.php';

// uncomment this to edit an existing record
$pk = array('Id' => 2);


/**
 * the rest should work out of the box if you don't have any unusal
 * types in your database schema.xml (strings, int etc. should work)
 */

require_once 'bookstore/' . $classname . '.php';
Propel::init($propelConfFilename);

// create a form definition

$definition = patForms_Definition_Propel::create(array(
	'name' => $classname,
	'filename' => $path . '/form.' . $classname . '.xml',
));

// create a storage

$storage = patForms::createStorage('Propel');
$storage->setStorageLocation($classname . 'peer');

// create a form

$form = &patForms::createCreator('Definition')->create($definition);
$form->setRenderer(patForms::createRenderer('Array'));
$form->setStorage($storage);
if (isset($pk)) {
	$form->setValues($pk);
}

// render it to a patTemplate (could be done by other template engines)

$tpl = new patTemplate();
$tpl->setRoot($path);
$tpl->readTemplatesFromInput('form.dynamic.tpl');

$tpl->addVar('page', 'title', 'Bookstore party');
$tpl->addVar('form', 'start', $form->serializeStart());
$tpl->addVar('form', 'end', $form->serializeEnd());
$tpl->addRows('elements', $form->renderForm());

// this should be possible to be done in a more elegant way
if ($errors = $form->getValidationErrors()) {
	foreach ($errors as $field => $error) {
		$tpl->addVar('error', 'field', $field);
		foreach ($error as $line) {
			$tpl->addVar('error', 'message', $line['message']);
			$tpl->addVar('error', 'code', $line['code']);
			$tpl->parseTemplate('error', 'a');
		}
	}
	$tpl->setAttribute('errors', 'visibility', 'visible');
}

$tpl->displayParsedTemplate();
