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
 * The controller that manages requests to and from Pixlr's servers,
 * which in turn provides inline image editing capabilities
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
		'saveappletupload',
	);

	public static $allowed_hosts = array(
		''
	);


    public function init()
	{
		SS_Log::log("initting log", SS_Log::NOTICE);
		parent::init();
	}

	/**
	 *
	 * @param String $action
	 * @return String
	 */
	public function Link($action='')
	{
		return Director::baseURL().'pixlr/'.$action;
	}

	/**
	 * Sends an image to the Pixlr server, receiving back the image ID as the
	 * response parameter, which is then forwarded to the browser
	 *
	 * This method is used as a means to work around the issue where images are stored
	 * on servers that are behind firewalls, in which case pixlr's servers are unable to
	 * retrieve the content directly, so it must be pushed to pixlr.
	 *
	 * This method is still relatively secure in that the image ID returned is only known to
	 * us and pixlr - it is a unique key, effectively a one time password.
	 *
	 * @param HTTPRequest $request
	 */
	public function sendimage($request)
	{
		if (!isset($request['ID'])) {
			throw new Exception("Invalid image ID");
		}

		$file = DataObject::get_by_id('Image', (int) $request['ID']);

		if ($file && $file->ID) {
			include_once 'Zend/Http/Client.php';

			$client = new Zend_Http_Client(self::$pixlr_upload_uri);

			$client->setFileUpload($file->getFullPath(), 'image');
			$client->setMethod('POST');
			$result = $client->request()->getBody();
			if (strpos($result, 'ERR') === 0) {
				throw new Exception("Failed uploading image: $result");
			}

			if(strpos($result, 'http') === 0){
				return $result;
			}else{
				return self::$pixlr_temp_uri . $result;
			}
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
		
		if (isset($request['image']) && isset($request['imgstate'])) {
			// see if there's a transaction key to be used, because if the
			// key is different, then we want the user to be prompted to
			// save the file somewhere else instead. This displays the form
			// for the user to fill out where to save the item to, but if
			// the user decides to leave everything as is, the transaction
			// ID won't be in the subsequent request here, so it'll just do an overwrite
			$editKey = isset($request['transaction']) ? $request['transaction'] : 0;
			$existEdit = null;
			if ($editKey) {
				$existEdit = DataObject::get_one('Image', singleton('PixlrUtils')->dbQuote(array('TransactionKey =' => $editKey)));
			}

			if (($request['imgstate'] == 'new' || !$existEdit || !$existEdit->ID) 
					&& !($request['imgstate'] == 'existing' && isset($request['force']))) {
				// ?image=http://pixlr.com/_temp/4b544f161fd83.jpg&type=jpg&state=new&title=Untitled
				$fields->push(new HiddenField('image', 'Image', $request['image']));
				$fields->push(new HiddenField('type', 'Type', $request['type']));
				$fields->push(new HiddenField('imgstate', 'State', $request['imgstate']));

				// if the imgstate is 'new', then need to check whatever parent the user selected
				// to make sure there's not another item with the same name
				// if so, the user MUST change it, otherwise it'll overwrite the existing image
				$parent = isset($request['parent']) ? $request['parent'] : 0;

				$fname = $request['title'].'.'.$request['type'];

				$existing = null;
				// only check for an existing item in a parent if we've already selected to save somewhere
				if ($parent) {
					$existing = $this->getExistingImage($fname, $parent);
						$tree = $fields->fieldByName('parent');
					$tree->setValue($parent);
					// $fields->push(new HiddenField('parent', 'ParentID', $parent));
				} else {

				}

				if ($editKey || ($existing && $existing->ID)) {
					if ($editKey) {
						$txt = 'It looks like someone else has edited the image since you started. You can choose to
save using the same name, but be aware that they may override this image later (they will also receive this warning
when they attempt to save). Otherwise, choose a new name and re-edit the image later';
						$msg = '<div class="error"><p>'._t('Pixlr.INVALID_TRANSACTION', $txt).'</p></div>';
					} else {
						$msg = '<div class="error"><p>'._t('Pixlr.EXISTING_FILE', $request['imgstate'] . ' That file already exists - please choose another name').'</p></div>';
					}
					if ($editKey) {
						$fname = $request['title'].'.'.$editKey;
					} else {
						$fname = $request['title'];
					}
					$fields->push(new TextField('title', _t('PixlrController.IMAGE_TITLE', 'Image Title'), $fname));
					$fields->push(new LiteralField('FileExists',$msg));
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
		if (!isset($request['parent'])) {
			return $this->saveimage($request);
		}

		$data = array();
		
		if (isset($request['image'])) {
			// get the content and store it in the appropriate place in the assets
			// folder, then run a sync on that folder

			$folder = (int) $request['parent'] ? DataObject::get_by_id('Folder', (int) $request['parent']) : null;
			
			// need to str replace things for Silverstripe's sake
			$fname = str_replace(' ', '-', $request['title'].'.'.$request['type']);
			$title = $request['title'];
			$existing = $this->getExistingImage($fname, $request['parent']);

			// if it exists, and it's a NEW image, then we don't allow creation
			if ($existing && $request['imgstate'] == 'new') {
				return $this->saveimage($request);
			}

			/* @var $path */
			if($folder){
				$path = $folder->getFullPath() . $fname;
			}else{
				$path = ASSETS_PATH . '/' . $fname;
			}

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
				// empty the transaction key setting, as we're done
				$existing->TransactionKey = '';
			}

			if (!$existing) {
				$existing = object::create('Image');
				$existing->ParentID = $folder->ID;
				$existing->Filename = $folder->Filename.'/'.$fname;
				$existing->Name = $fname;
				$image->Title = $title;

				// save the image
				$existing->write();
			} else {
				// make sure to version it if the extension exists... This will
				// regenerate all renditions for us too 
				if ($existing->hasField('CurrentVersionID')) {
					$existing->createVersion();
				} else {
					$existing->regenerateFormattedImages();
					$existing->write();
				}
			}

			$data['Parent'] = $folder;
			$data['Image'] = $existing;
			
		}

		return $this->customise($data)->renderWith('PixlrController_storeimage');
	}

	/**
	 *
	 * @return Form
	 */
	public function ImageSaveForm()
	{
		$actions = new FieldSet(
			new FormAction('storeimage', _t('PixlrController.SAVE_IMAGE', 'Save Image')),
			new FormAction('closepixlr', _t('PixlrController.CLOSE_PIXLR', 'Close Without Saving'))
		);

		$fields = new FieldSet();

		// this needs to be declared here, otherwise the ajax callback
		// doesn't work. We remove it later if we don't need it
		$fields->push(new TreeDropdownField('parent', _t('PixlrController.SAVE_TARGET', 'Save Image To'), 'Folder'));
		$fields->push(new LiteralField('asdfsadf', '<div class="clear"></div>'));

		$form = new Form($this, 'ImageSaveForm', $fields, $actions);
		
		return $form;
	}

	/**
	 * Close the overlay!
	 *
	 * @return String
	 */
	public function closepixlr()
	{
		return $this->renderWith('PixlrController_storeimage');
	}

	public function saveappletupload($request) {
		$location = (int) $request->postVar('Location');
		if ($location) {
			$folder = DataObject::get_by_id('Folder', $location);
			if (isset($_FILES) && isset($_FILES['screenshot'])) {
				$upload = new Upload;
				if ($upload->load($_FILES['screenshot'], substr($folder->Filename, 7))) {
					// need to base64decode the file data
					$data = file_get_contents($upload->getFile()->getFullPath());
					file_put_contents($upload->getFile()->getFullPath(), base64_decode($data));
					
					$upload->getFile()->ClassName = 'Image';
					$upload->getFile()->write();
					
					return '{"file": '. Convert::raw2json($upload->getFile()->toMap()).'}';
				}
			}
		}
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
		$filter = '"Name" = \''.Convert::raw2sql($fname)."'";
		$filter .= $parent ? ' AND "ParentID" = '. (int) $parent : '';

		$existing = DataObject::get_one('Image', $filter);
		return $existing;
	}
	

}