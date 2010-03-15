
;(function ($) {
	$.fn.pixlrize = function (options) {
		var opts = $.extend($.fn.pixlrize.defaults, options);

		return this.each(function () {
			var $this = $(this);
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
						async: false,	// explicitly need the user to wait while we load... 
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
					// see if we have a particular window to open the pixlr handler in,
					// which is the case if we are loading in an iframe
					var activePixlr = o.openin ? o.openin.pixlr : pixlr;
					activePixlr.overlay.show(o);

					if (activePixlr.overlay.div) {
						$(activePixlr.overlay.div).click(function () {
							if (confirm("Cancel editing?")) {
								activePixlr.overlay.hide();
							}
						});
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