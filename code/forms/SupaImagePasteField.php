<?php

/**
 * Description of SupaImagePasteField
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SupaImagePasteField extends FormField {
	
	protected $options = array(
		'width'			=> '180',
		'height'		=> '100',
		'target'		=> 'pasted',
		'show_selector'	=> false,
		'name'			=> 'screenshot'
	);

	public function __construct($name, $title = '', $value = '', $options = array()) {
		$this->options = array_merge($this->options, $options);
		parent::__construct($name, $title, $value);
	}

	public function Field() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('pixlr/thirdparty/supa/Supa.js');
		Requirements::javascript('pixlr/javascript/supa-field.js');
		
		Requirements::css('pixlr/css/pixlr.css');
		
		$id = $this->id();
		$div = '<div id="'.$id.'Container" class="'.$this->extraClass().' supaField">';
		$div .= '<div class="supaButtons">';
		$div .= $this->createTag('input', array(
			'id'		=> $id . 'Paste',
			'value'		=> _t('Pixlr.PASTE', 'Paste'),
			'type'		=> 'button',
			'class'		=> 'supaPasteButton',
		));

		$div .= $this->createTag('input', array(
			'id'		=> $id . 'Upload',
			'value'		=> _t('Pixlr.UPLOAD', 'Upload'),
			'type'		=> 'button',
			'class'		=> 'supaUploadButton',
			'style'		=> 'display: none'
		));

		$div .= $this->createTag('input', array(
			'type'		=> 'checkbox',
			'value'		=> '1',
			'name'		=> 'AndEdit',
			'id'		=> $id.'AndEdit',
		));
		
		$div .= '<label for="'.$id.'AndEdit'.'">and Edit</label>';
		
		$div .= $this->createTag('input', array(
			'class'			=> 'supaFileID',
			'type'			=> 'hidden',
			'value'			=> '',
		));

		$div .= '</div><div class="supaOptions" style="display: none">';

		if ($this->options['show_selector']) {
			$treeField = new TreeDropdownField('SupaLocation', _t('Pixlr.UPLOAD_LOCATION', 'Save to'), 'File');
			$div .= $treeField->Field();
		} else {
			// create a default target for uploading to
			$target = Folder::findOrMake($this->options['target']);
			$div .= $this->createTag('input', array(
				'type'			=> 'hidden',
				'name'			=> 'SupaLocation',
				'value'			=> $target->ID,
			));
		}

		$div .= $this->createTag('input', array(
			'id'		=> $id.'Filename',
			'value'		=> $this->options['name'],
			'name'		=> 'SupaImageName',
		));

		$params = array('parent' => '{input[name=FolderID]}', 'force' => true, 'imgstate' => 'existing', 'title' => '{#'.$id.'Filename}.png');
		$pixlr = new PixlrEditorField('EditSelectedImage'.$id, _t('Pixlr.EDIT_SELECTED', 'Edit Selected'), '{#'.$id.'Container .supaFileID}', $params);

		$div .= $pixlr->Field();
		
		$div .= '</div><div class="supaFileUrl"></div><div class="supaAppletWrapper" id="'.$id.'AppletWrapper">';
		
		$url = Director::absoluteBaseURL() .'pixlr/thirdparty/supa/Supa.jar';
		$div .= '<applet id="'.$id.'Applet" class="supaApplet"
              archive="'.$url.'"
              code="de.christophlinder.supa.SupaApplet" 
              width="'.$this->options['width'].'" 
              height="'.$this->options['height'].'">
        <!--param name="clickforpaste" value="true"-->
			<param name="imagecodec" value="png">
			<param name="encoding" value="base64">
			<param name="previewscaler" value="fit to canvas">
			<!--param name="trace" value="true"-->
			Applets disabled :(
		  </applet> ';
		
		$div .= '</div></div>';
		
		
		
		return $div;
	}
}
