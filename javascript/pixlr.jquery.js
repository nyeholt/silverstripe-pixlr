
;(function ($) {
	$.fn.pixlrize = function (options) {
		var opts = $.extend($.fn.pixlrize.defaults, options);

		return this.each(function () {
			var $this = $(this);
			var baseUrl = $('base').attr('href');
			var o = $.meta ? $.extend({}, opts, $this.data()) : opts;

			// when clicked, want to launch pixlr
			$this.click(function () {
				o.exit = baseUrl + 'pixlr/closepixlr';
				o.target = baseUrl + 'pixlr/saveimage';
				o.method = 'get';

				if (o.mode && o.mode == 'popup') {
					pixlr.window(o);
				} else {
					pixlr.overlay.show(o);
				}
			});
		});
	};

	$.fn.pixlrize.defaults = {
		editor: 'full',
		title: ''
	};
})(jQuery);