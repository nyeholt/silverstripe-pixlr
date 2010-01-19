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
	public static $allowed_actions = array(
		'saveimage',
		'ImageSaveForm',
		'saveimage',
		'closepixlr',
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

	public function saveimage($request)
	{
		SS_Log::log("Saving data ".var_export($request, true), SS_Log::NOTICE);

		$form = $this->ImageSaveForm();
		
		$fields = $form->Fields();
		
		if (isset($request['image'])) {
			// ?image=http://pixlr.com/_temp/4b544f161fd83.jpg&type=jpg&state=new&title=Untitled

			$fields->push(new HiddenField('Image', 'Image', $request['image']));
			$fields->push(new HiddenField('Type', 'Type', $request['type']));
			$fields->push(new HiddenField('State', 'State', $request['state']));
			$fields->push(new TextField('Title', _t('PixlrController.IMAGE_TITLE', 'Image Title'), $request['title']));
		}

		$data['SaveForm'] = $form;

		return $this->customise($data)->renderWith('PixlrController_saveimage');
	}

	/**
	 * Store an image within silverstripe
	 *
	 * @param array $request
	 * @return String
	 */
	public function storeimage($request)
	{
		if ($request['ImageTarget'] && isset($request['Image'])) {
			// get the content and store it in the appropriate place in the assets
			// folder, then run a sync on that folder

			$folder = DataObject::get_by_id('Folder', $request['ImageTarget']);
			if ($folder->ID) {
				/* @var $folder Folder */
				$path = $folder->getFullPath().$request['Title'].'.'.$request['Type'];

				$session = curl_init($request['Image']);

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

				Filesystem::sync($folder->ID);
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
		$fields->push(
			new TreeDropdownField('ImageTarget', _t('PixlrController.SAVE_TARGET', 'Save Image To'), 'Folder')
		);

		$form = new Form($this, 'ImageSaveForm', $fields, $actions);
		
		return $form;
	}

	public function closepixlr()
	{
		return $this->renderWith('PixlrController_storeimage');
	}
}
?>