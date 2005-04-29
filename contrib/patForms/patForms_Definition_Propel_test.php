<?

function __autoload($classname) {
	$filename = str_replace ('_', '/', $classname) . '.php';
	require_once $filename;
}

// include_once 'common.php';
$path = explode(PATH_SEPARATOR, get_include_path());
if (!in_array('f:/PEAR/pat', $path)) {
	$path[] = 'f:/PEAR/pat';
}
if (!in_array('f:/Q/lib/mod', $path)) {
	$path[] = 'f:/Q/lib/mod';
}
set_include_path(implode(PATH_SEPARATOR, $path));


// change these according to your propel settings
require_once 'bookstore/propel/BookPeer.php';
Propel::init('bookstore/propel/conf/propel.bookstore.php');
$object = BookPeer::retrieveByPK(1);
$path = './res';

// the rest should work out of the box if you don't have any unusal
// types in your database schema.xml (strings, int etc. should work)
$name = strtolower(get_class($object));
$definition = patForms_Definition_Propel::create(array(
	'name' => $name,
	'filename' => $path . '/form.' . $name . '.xml',
));

$form = &patForms::createCreator('Definition')->create($definition, $object);
$form->setRenderer(patForms::createRenderer('Array'));

$tpl = new patTemplate();
$tpl->setRoot($path);
$tpl->readTemplatesFromInput('form.dynamic.tpl');

$tpl->addVar('page', 'title', 'Bookstore party');
$tpl->addVar('form', 'start', $form->serializeStart());
$tpl->addVar('form', 'end', $form->serializeEnd());
$tpl->addRows('elements', $form->renderForm());

$tpl->displayParsedTemplate();

?>