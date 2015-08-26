;(function ($) {
	$('input[data-pixlr-editor]').entwine({
		onmatch: function () {
			var opts = $(this).data('pixlr-editor');
			if (opts.openobjname == 'window') {
				opts.openin = window;
			} else {
				opts.openin = window.parent.parent;
			}
			
			$(this).pixlrize(opts);
		}
	})
})(jQuery);