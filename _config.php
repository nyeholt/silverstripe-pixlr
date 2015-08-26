<?php

// add Zend into include path
//set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/thirdparty');


if (($PIXLR_MODULE_DIR = basename(dirname(__FILE__))) != 'pixlr') {
	$msg = sprintf(_t(
			'Pixlr.INCORRECT_MODULE_DIR',
			"Please place the pixlr module in the pixlr directory in your SilverStripe root, not %s"
			), $PIXLR_MODULE_DIR);
	die($msg);
}
