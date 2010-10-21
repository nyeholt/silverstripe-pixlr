<?php
Director::addRules(50, array(
	'pixlr' => 'PixlrController',
));

// add Zend into include path
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/thirdparty');

Object::add_extension('Image', 'PixlrImageDecorator');
Object::remove_extension('Image', 'PixlrImageDecorator');
Object::add_extension('File', 'PixlrImageDecorator');
Object::add_extension('HtmlEditorField_Toolbar', 'PixlrImageFormDecorator');

if (($PIXLR_MODULE_DIR = basename(dirname(__FILE__))) != 'pixlr') {
	$msg = sprintf(_t(
			'Pixlr.INCORRECT_MODULE_DIR',
			"Please place the pixlr module in the pixlr directory in your SilverStripe root, not %s"
			), $PIXLR_MODULE_DIR);
	die($msg);
}