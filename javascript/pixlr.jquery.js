
;(function ($) {
	$.fn.pixlrize = function (options) {
		var opts = $.extend({}, $.fn.pixlrize.defaults, options);

		return this.each(function () {
			var $this = $(this);
			var o = $.meta ? $.extend({}, opts, $this.data()) : opts;

			// We sometimes change the target, so we need the original
			// just in case
			var originalTarget = o.target;
			var originalTitle = o.title;

			// when clicked, want to launch pixlr
			$this.click(function () {
				// see whether we need to preload the image
				// by posting it to pixlr
				var failure = false;
				if (o.id) {
					// see whether it's a selector based ID
					var currentVal = $this.attr('value');
					$this.attr('disabled', true);
					$this.attr('value', 'Please wait, loading image...');
					
					failure = PixlrEditor.prepareImageForEdit(o.id, o);
					
					$this.attr('disabled', false);
					$this.attr('value', currentVal);
				}

				if (!failure) {
					o.title = originalTitle;
					o.target = originalTarget;
					PixlrEditor.launch(o);
				}
			});
		});
	};

	var PixlrEditor = {
		prepareImageForEdit: function (id, o) {
			var imageId = PixlrEditor.replaceStrings(o.id);

			var failure = false;
			$.ajax({
				type: 'POST',
				url: o.preload,
				async: false,	// explicitly need the user to wait while we load... 
				data: {ID: imageId},
				success: function (data) {
					o.image = data;
				},
				error: function (msg) {
					alert("Failed sending data: " + msg);
					o.image = '';
					failure = true;
				}
			});
			
			return failure;
		},
		
		launch: function (o) {
			// see if we have a particular window to open the pixlr handler in,
			// which is the case if we are loading in an iframe
			var activePixlr = o.openin ? o.openin.pixlr : pixlr;

			o.target = this.replaceStrings(o.target);
			o.title = this.replaceStrings(o.title);

			activePixlr.overlay.show(o);

			if (activePixlr.overlay.div) {
				$(activePixlr.overlay.div).click(function () {
					if (confirm("Cancel editing?")) {
						activePixlr.overlay.hide();
					}
				});
			}
		},
		
		replaceStrings: function (targetString) {
			if (!targetString || typeof targetString != 'string') {
				return targetString;
			}
			var matches = targetString.match(/\{(.*?)\}/g);
			if (matches && matches.length) {
				for (var i = 0; i < matches.length; i ++) {
					var match = matches[i];
					var selector = match.replace(/{|}/g, '');
					var replaceWith = $(selector);
					if (replaceWith.length) {
						targetString = targetString.replace(match, replaceWith.val());
					}
				}
			}

			return targetString;
		}
	}

	$.fn.pixlrize.defaults = {
		referrer: 'SilverStripe CMS',
		loc: 'en',
		locktarget: 'true',
		exit: 'pixlr/closepixlr',
		target: 'pixlr/saveimage',
		method: 'get',
		editor: 'full',
		title: ''
	};
})(jQuery);