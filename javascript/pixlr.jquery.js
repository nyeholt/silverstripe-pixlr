
;(function ($) {
	$.fn.pixlrize = function (options) {
		var opts = $.extend($.fn.pixlrize.defaults, options);

		return this.each(function () {
			var $this = $(this);
			var baseUrl = $('base').attr('href');
			var o = $.meta ? $.extend({}, opts, $this.data()) : opts;
			
			// when clicked, want to launch pixlr
			$this.click(function () {
				// see whether we need to preload the image
				// by posting it to pixlr
				var failure = false;

				if (o.id) {
					var currentVal = $this.attr('value');
					$this.attr('disabled', true);
					$this.attr('value', 'Please wait, loading image...');

					$.ajax({
						type: 'POST',
						url: o.preload,
						async: false,
						data: { ID: o.id },
						success: function (data) {
							o.image = data;
						},
						error: function (msg) {
							alert("Failed sending data: " + msg);
							o.image = '';
							failure = true;
						}
					});

					$this.attr('disabled', false);
					$this.attr('value', currentVal);
				}
				if (!failure) {
					if (o.openin) {
						o.openin.pixlr.overlay.show(o);
					} else {
						pixlr.overlay.show(o);
					}
				}
			});
		});
	};

	$.fn.pixlrize.defaults = {
		editor: 'full',
		title: ''
	};
})(jQuery);