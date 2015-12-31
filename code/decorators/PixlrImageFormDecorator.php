<?php
/**
 * An extension that hooks the pixlr editor into the image editing toolbar
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class PixlrImageFormDecorator extends Extension
{
    
    public static $use_applet_paste = false;

    /**
     * @TODO - this is not currently working. Will still need to figure out
     * a better way of integrating this in the general process
     * 
     * @param Form $form
     */
    public function updateMediaForm(Form $form)
    {
        //		Requirements::css('pixlr/css/pixlr.css');
//		$fields = $form->Fields();
//		// need to find out the name of the field that contains the FolderID - this is used as a
//		// replacement for our return value later on
//		$folderField = $form->FormName() . '_FolderID';
//		$params = array('parent' => '{#' . $folderField . '}', 'imgstate' => 'new');
//		$fields->addFieldToTab('MediaFormInsertMediaTabs.FromCms', new PixlrEditorField('NewPixlrImage', _t('Pixlr.ADD_IMAGE', 'Add Image with Pixlr'), '', $params), 'FolderID');
//		
//		if (self::$use_applet_paste) {
//			$fields->insertAfter(new SupaImagePasteField('PasteImage', _t('Pixlr.PASTE_IMAGE', 'Paste Image')), 'FolderID');
//		}
    }
    
    public function updateFieldsForImage(FieldList $fields, $url, $file)
    {
        $f = $file->getFile();
        $editor = $f->getPixlrField();
        $fields->push($editor);
    }
}
