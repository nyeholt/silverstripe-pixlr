<?php
/*

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
OF SUCH DAMAGE.
*/

/**
 * 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class PixlrController extends Controller
{

	public static $pixlr_upload_uri = 'http://pixlr.com/store/';
	public static $pixlr_temp_uri = 'http://pixlr.com/_temp/';
	
	public static $allowed_actions = array(
		'saveimage',
		'ImageSaveForm',
		'saveimage',
		'closepixlr',
		'sendimage',
	);

	public static $allowed_hosts = array(
		''
	);

    public function init()
	{
		SS_Log::log("initting log", SS_Log::NOTICE);
		parent::init();
	}

	public function Link($action='')
	{
		return Director::baseURL().'pixlr'.$action;
	}

	/**
	 * Sends an image to the Pixlr server, receiving back the image ID as the
	 * response parameter, which is then forwarded to the browser
	 *
	 * @param HTTPRequest $request
	 */
	public function sendimage($request)
	{
		if (!isset($request['ID'])) {
			throw new Exception("Invalid image ID");
		}

		$file = DataObject::get_by_id('Image', $request['ID']);

		if ($file && $file->ID) {
			$content = base64_encode(file_get_contents($file->getFullPath()));
			$type = File::get_file_extension($file->Filename);
			include_once 'Zend/Http/Client.php';

			$client = new Zend_Http_Client(self::$pixlr_upload_uri);
			$client->setParameterPost('image', $content);
			$client->setParameterPost('type', $type);
			$client->setMethod('POST');
			$result = $client->request()->getBody();

			if (strpos($result, 'ERR') === 0) {
				throw new Exception("Failed uploading image: $result");
			}

			return self::$pixlr_temp_uri . $result;
		}
	}

	/**
	 * Called by the pixlr service when it wants to save an image back to
	 * silverstripe
	 *
	 * If it's a new image, will check to make sure that the selected folder
	 * doesn't have an image of the same name, otherwise will immediately overwrite
	 *
	 *
	 * @param HTTPRequest $request
	 * @return Form
	 */
	public function saveimage($request)
	{
		$form = $this->ImageSaveForm();
		
		$fields = $form->Fields();
		
		if (isset($request['image']) && isset($request['state'])) {
			if ($request['state'] == 'new') {
				// ?image=http://pixlr.com/_temp/4b544f161fd83.jpg&type=jpg&state=new&title=Untitled
				$fields->push(new HiddenField('image', 'Image', $request['image']));
				$fields->push(new HiddenField('type', 'Type', $request['type']));
				$fields->push(new HiddenField('state', 'State', $request['state']));

				// if the state is 'new', then need to make sure there's not another item with the same name
				// if so, the user MUST change it, otherwise it'll overwrite the existing image
				$parent = isset($request['parent']) ? $request['parent'] : 0;
				$fname = $request['title'].'.'.$request['type'];
				$existing = null;
				// only check for a parent if we've actually selected to save somewhere
				if ($parent) {
					$existing = $this->getExistingImage($fname, $parent);
				}

				if ($existing && $existing->ID) {
					$msg = '<div class="error"><p>That file already exists - please choose another name</p></div>';
					$fields->push(new TextField('title', _t('PixlrController.IMAGE_TITLE', 'Image Title'), $request['title']));
					$fields->push(new LiteralField('FileExists', _t('Pixlr.FILE_EXISTS', $msg)));
				} else {
					$fields->push(new HiddenField('title', 'title', $request['title']));
				}
			} else {
				return $this->storeimage($request);
			}
		}

		$data['SaveForm'] = $form;

		return $this->customise($data)->renderWith('PixlrController_saveimage');
	}

	/**
	 * Store an image within silverstripe. This is triggered either
	 * by the "create new" form, or passed on directly from the pixlr
	 * application
	 *
	 * @param array $request
	 * @return String
	 */
	public function storeimage($request)
	{
		if (!isset($request['parent']) || !$request['parent']) {
			return $this->saveimage($request);
		}

		if ($request['parent'] && isset($request['image'])) {
			// get the content and store it in the appropriate place in the assets
			// folder, then run a sync on that folder

			$folder = DataObject::get_by_id('Folder', $request['parent']);
			if ($folder->ID) {
				$fname = $request['title'].'.'.$request['type'];

				$existing = $this->getExistingImage($fname, $request['parent']);

				// if it exists, and it's a NEW image, then we don't allow creation
				if ($existing && $request['state'] == 'new') {
					return $this->saveimage($request);
				}

				/* @var $folder Folder */
				$path = $folder->getFullPath().$fname;

				// @TODO Manually using CURL here to handle file downloads
				// more efficiently, should probably swap to a correctly
				// configured Zend_Http_Client
				$session = curl_init($request['image']);
				// get the file and store it into a local item
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($session);
				$fp = fopen($path, 'w');
				if (!$fp) {
					throw new Exception("Could not write file to $toFile");
				}
				fwrite($fp, $response);
				fclose($fp);
				curl_close($session);

				// create a new image to represent the new file
				// if there was an existing one, make sure to clean up
				// its renditions
				if ($existing && $existing instanceof Image) {
					// we're going to cheat and ensure that the image is marked as
					// changed so that it will version etc if that module is installed
					$existing->LastEdited = date('Y-m-d H:i:s');
				}

				if (!$existing) {
					$existing = object::create('Image');
					$existing->ParentID = $folder->ID;
					$existing->Filename = $folder->Filename.'/'.$fname;
					$existing->Name = $image->Title = $fname;

					// save the image
					$existing->write();
				} else {
					// make sure to version it if the extension exists... This will
					// regenerate all renditions for us too 
					if (false && $existing->hasField('CurrentVersionID')) {
						$existing->createVersion();
					} else {
						$existing->regenerateFormattedImages();
					}
				}
			}
		}

		$data = array();
		return $this->customise($data)->renderWith('PixlrController_storeimage');
	}

	/**
	 *
	 * @return Form
	 */
	public function ImageSaveForm()
	{
		$actions = new FieldSet(
			new FormAction('storeimage', _t('PixlrController.SAVE_IMAGE', 'Save Image'))
		);

		$fields = new FieldSet();
		$fields->push(new TreeDropdownField('parent', _t('PixlrController.SAVE_TARGET', 'Save Image To'), 'Folder'));
		$fields->push(new LiteralField('asdfsadf', '<div class="clear"></div>'));

		$form = new Form($this, 'ImageSaveForm', $fields, $actions);
		
		return $form;
	}

	public function closepixlr()
	{
		return $this->renderWith('PixlrController_storeimage');
	}


	/**
	 * Determine whether an image exists or not
	 *
	 * @param The name of the image $name
	 * @param The parent folder to search within $parent
	 * @return File
	 */
	protected function getExistingImage($fname, $parent=0)
	{
		$filter = '"Title" = \''.Convert::raw2sql($fname)."'";
		$filter .= $parent ? ' AND "ParentID" = \''.$parent.'\'' : '';

		$existing = DataObject::get_one('Image', '"Name" = \''.Convert::raw2sql($fname)."'");
		return $existing;
	}

}
?>