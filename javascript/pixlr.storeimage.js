
(function ($) {
	window.refreshAndClose = function (imageParent, imageName, imageTitle) {
		if (window.parent && window.parent.pixlr) {
			// now, figure out what we need to refresh based on what may be onscreen
			var parentWindow = $(window.parent.document);
			var pframe = parentWindow.find('iframe.GB_frame');
			// if no default frame class, search for the facebox one in DOM
			if (!pframe.length) {
				pframe = parentWindow.find('#facebox iframe');
			}
			if (pframe.length > 0) {
				var subframe = $(pframe[0].contentDocument).find('iframe');
				if (subframe.length > 0) {
					subframe[0].src = subframe[0].src;
				} else {
					pframe[0].src = pframe[0].src;
				}
				window.parent.pixlr.overlay.hide();
			} else {
				// we might have to refresh the FolderID tree...
				var folderList = parentWindow.find('#FolderImages');
				if (folderList.length) {
					var folderListElem = folderList[0];
					folderListElem.ajaxGetFiles(imageParent, imageName, function () {
						// so in that method it does a behaviour application, so we do
						// that
						folderListElem.reapplyBehaviour.bind(folderListElem).call();
						$(folderListElem).find('a[title="'+imageTitle+'"]').click();
						window.parent.pixlr.overlay.hide();
					});
				} else {
					window.parent.pixlr.overlay.hide();
				}
			}
		} else if (window.opener) {
			window.close();
		}
	}

})(jQuery);