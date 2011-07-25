
(function ($) {
	$(function () {
		var originalScale = {
			width: 600,
			height: 200
		}
		$('applet.supaApplet').livequery(function () {
			originalScale = {
				width: $(this).attr('width'),
				height: $(this).attr('height')
			}
			$(this).attr({
				width: '1',
				height: '1'
			})
		})
		
		
		function handlePaste(context) {
			var field = $(context).parents('div.supaField');
			var s = new supa();
			// Call the paste() method of the applet.
			// This will paste the image from the clipboard into the applet :)
			try {
				var applet = field.find('applet')[0];
				if(!s.ping( applet )) {
					throw "No paste target available";
				}

				var err = applet.pasteFromClipboard(); 
				switch( err ) {
					case 0:
						/* no error */
						break;
					case 1:
						alert( "Unknown Error" );
						break;
					case 2:
						alert( "Empty clipboard" );
						break;
					case 3:
						alert( "Only image pasting is supported." );
						break;
					case 4:
						alert("Clipboard in use by another application. Please try again in a few seconds." );
						break;
					default:
						alert( "Unknown error code: "+err );
				}

				$(context).parent().siblings('.supaOptions').show();
				$(context).siblings('.supaUploadButton').show();
				field.find('input[name=SupaImageName]').focus();
				$(applet).attr(originalScale);
			} catch( e ) {
				alert(e);
				throw e;
			}
		}
		
		$('.supaPasteButton').livequery(function () {
			$(this).click(function (e) {
				e.preventDefault();
				handlePaste(this);
			})
		})
		
		$(document).keydown(function (e) {
			if (e.which == 86 && e.ctrlKey && e.shiftKey) {
				handlePaste('#Form_EditorToolbarImageForm .supaPasteButton');
			}
		})

		$('.supaUploadButton').livequery(function () {
			$(this).click(function (e) {
				e.preventDefault();
				var field = $(this).parents('div.supaField');
				$(this).attr('disabled', 'disabled');
				
				// Get the base64 encoded data from the applet and POST it via an AJAX 
				// request. See the included Supa.js for details
				var s = new supa();
				var applet = field.find('applet')[0];

				try { 
					var location = field.find('input[name=SupaLocation]').val();
					var fname = field.find('input[name=SupaImageName]').val() ;
					if (!fname) {
						fname = 'new-screenshot';
					}
					fname = fname  + '.png';
					
					// see if we've got a location based on the Folder selector
					if ($('#Form_EditorToolbarImageForm_FolderID').val()) {
						location = $('#Form_EditorToolbarImageForm_FolderID').val();
					}
					
					var result = s.ajax_post( 
						applet,       // applet reference
						"pixlr/saveappletupload", // call this url
						"screenshot", // this is the name of the POSTed file-element
						fname,
						{
							params: {
								'Location': location
							}
						}
	//					, // this is the filename of tthe POSTed file
	//					{
	//						form: document.forms["form"]
	//					} // elements of this form will get POSTed, too
					);

					if (result) {
						var response = $.parseJSON(result);
						if (response && response.file) {
							applet.clear();
							// successfully uploaded, should we go and edit now?
							var folderList = $('#FolderImages');
							if (!field.parents('form').find('input[name=FolderID]').length) {
								// make sure this field exists...
								field.parents('form').append('<input name="FolderID" type="hidden" />');
							}
							field.parents('form').find('input[name=FolderID]').val(location);
							if (folderList.length) {
								var folderListElem = folderList[0];
								folderListElem.ajaxGetFiles(location, fname, function () {
									// so in that method it does a behaviour application, so we do
									// that
									folderListElem.reapplyBehaviour.bind(folderListElem).call();
									$(folderListElem).find('a[title="'+response.file.Title+'"]').click();
								});
							}

							// store the newly created file ID
							field.find('.supaFileID').val(response.file.ID);
							field.find('.supaFileUrl').html('<a class="newFileUrl" href="'+response.file.Filename+'" target="_blank">'+response.file.Name+'</a>');
							if (field.find('input[name=AndEdit]').is(':checked')) {
								field.find('.pixlrTrigger').click();
							} else {
								
							}
						} else {
							alert(result);
						}
					} else {
						alert(result);
					}
				} catch( ex ) {
					if( ex == "no_data_found" ) {
						alert( "Please paste an image first" );
					} else {
						alert( ex );
					}
				}

				$(this).removeAttr('disabled');
			});
		})

	})
})(jQuery);