<html>

	<head>
		<% base_tag %>
		<% require css(pixlr/css/pixlr.css) %>
		<% require javascript(sapphire/thirdparty/jquery/jquery-packed.js) %>
	</head>
	<body>
		<div id="PixlrDetails">
			<script type="text/javascript">
				function refreshAndClose() {
					if (window.parent && window.parent.pixlr) {
						// now, figure out what we need to refresh based on what may be onscreen
						var parentWindow = jQuery(window.parent.document);
						var pframe = parentWindow.find('iframe.GB_frame');
						if (pframe.length > 0) {
							var subframe = $(pframe[0].contentDocument).find('iframe');
							if (subframe.length > 0) {
								subframe[0].src = subframe[0].src;
							}
						}

						window.parent.pixlr.overlay.hide();
					} else if (window.opener) {
						window.close();
					}
				}
				refreshAndClose();
			</script>
			
		</div>
	</body>
</html>

