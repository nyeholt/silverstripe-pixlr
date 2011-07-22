<?php
/**
 * An extension that hooks the pixlr editor into the image editing toolbar
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class PixlrImageFormDecorator extends Extension {
	
	static $use_applet_paste = false;

	public function updateImageForm(Form $form) {
		Requirements::css('pixlr/css/pixlr.css');
		$fields = $form->Fields();
		// need to find out the name of the field that contains the FolderID - this is used as a
		// replacement for our return value later on
		$folderField = $form->FormName() . '_FolderID';
		$params = array('parent' => '{#' . $folderField . '}', 'imgstate' => 'new');
		$fields->insertAfter(new PixlrEditorField('NewPixlrImage', _t('Pixlr.ADD_IMAGE', 'Add Image with Pixlr'), '', $params), 'FolderID');
		
		if (self::$use_applet_paste) {
			$fields->insertAfter(new SupaImagePasteField('PasteImage', _t('Pixlr.PASTE_IMAGE', 'Paste Image')), 'FolderID');
		}
	}
}
