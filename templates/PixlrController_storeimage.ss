<html>
	<head>
		<% base_tag %>
		<% require css(pixlr/css/pixlr.css) %>
		<% require javascript(framework/thirdparty/jquery/jquery.js) %>
		<% require javascript(pixlr/javascript/pixlr.storeimage.js) %>
	</head>
	<body class="pixlrPage">
		<div id="PixlrDetails">
			<script type="text/javascript">
				$().ready(function () {
					refreshAndClose('$Image.Parent.ID', '$Image.Name.JS', '$Image.Title.JS');
				})
			</script>
		</div>
	</body>
</html>

