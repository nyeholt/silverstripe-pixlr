
;(function ($) {
	$().ready(function () {
		$('#PixlrEditButton').click(function () {
			// pixlr.settings.target = 'http://developer.pixlr.com/save_post_modal.php';
			pixlr.settings.exit = 'http://localhost/modules/examples/pixlr/pixlr/closepixlr';
			pixlr.settings.target = 'http://localhost/modules/examples/pixlr/pixlr/saveimage';
			// pixlr.settings.credentials = true;
			pixlr.settings.method = 'get';
			pixlr.overlay.show({title:'Example image 1'});
		});

		
	});
})(jQuery);