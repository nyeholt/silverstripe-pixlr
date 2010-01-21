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
 * A Form field which can be used to display a button which will
 * trigger a pixlr editor to appear. 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class PixlrEditorField extends FormField
{
	public static $image_name_prefix = "Parent_ID_";

	private $label;

	/**
	 *
	 * @param String $name
	 * @param String $title
	 * @param Image $value
	 */
    public function __construct($name, $title = '', $value = '')
	{
		$this->label = $title;
		
		parent::__construct($name, '', $value);
	}

	public function Field()
	{
		Requirements::javascript('pixlr/javascript/pixlr.js');
		Requirements::javascript('pixlr/javascript/pixlr.jquery.js');

		$fieldAttributes = array(
			'id' => $this->id(),
			'class' => 'pixlrTrigger',
			'value' => Convert::raw2att($this->label),
			'type' => 'button',
		);

		$opts = array(
			'referrer' => Convert::raw2js('SilverStripe CMS'),
			// 'loc' => Member::currentUser()->
			'title' => $this->value ? Convert::raw2js(self::$image_name_prefix . $this->value->ParentID . '_' . $this->value->Title) : '',
			'locktarget' => 'true',
		);

		if ($this->value) {
			$opts['locktitle'] = 'true';
			$opts['image'] = Convert::raw2js(Director::absoluteBaseURL() . $this->value->Filename);
			$opts['mode'] = 'popup';
		}

		$opts = Convert::raw2json($opts);

		$script = <<<JSCRIPT
jQuery().ready(function () {
jQuery('#{$this->id()}').pixlrize($opts);
});
JSCRIPT;

		Requirements::customScript($script, 'pixlr-'.$this->id());

		return $this->createTag('input', $fieldAttributes);
	}
}
?>