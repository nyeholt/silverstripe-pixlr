<?php
Director::addRules(50, array(
	'pixlr' => 'PixlrController',
));

// add Zend into include path
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/thirdparty');

Object::add_extension('Image', 'PixlrImageDecorator');
Object::add_extension('HtmlEditorField_Toolbar', 'PixlrImageFormDecorator');
