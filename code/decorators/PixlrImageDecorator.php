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
class PixlrImageDecorator extends DataObjectDecorator
{
    public function updateCMSFields($fields)
	{
		$params = array('parent' => $this->owner->ParentID);
		$fields->addFieldToTab('BottomRoot.Image', new PixlrEditorField('PixlrButton', 'Edit this image', $this->owner, $params));
	}

	/**
	 * Regenerate all the reformatted rengitions of this image.
	 *
	 * Code based on regeneration technique used in the VersionedFiles
	 * module by ajshort
	 *
	 * This should probably actually be in the base image class...
	 */
	public function regenerateFormattedImages()
	{
		$base      = $this->owner->ParentID ? $this->owner->Parent()->getFullPath() : ASSETS_PATH . '/';
		$resampled = "{$base}_resampled";

		if(!is_dir($resampled)) return;

		$files    = scandir($resampled);
		$iterator = new ArrayIterator($files);
		$filter   = new RegexIterator (
			$iterator,
			sprintf("/(?<method>[a-zA-Z]+)(?<arguments>[0-9]*)-%s/", preg_quote($this->owner->Name)),
			RegexIterator::GET_MATCH
		);

		// grab each resampled image and regenerate it
		foreach($filter as $cachedImage) {
			$path      = "$resampled/{$cachedImage[0]}";
			if (!file_exists($path)) {
				continue;
			}
			$size      = getimagesize($path);
			$method    = $cachedImage['method'];
			$arguments = $cachedImage['arguments'];

			unlink($path);

			// Determine the arguments used to generate an image, and regenerate it. Different methods need different
			// ways of determining the original arguments used.
			switch(strtolower($method)) {
				case 'resizedimage':
				case 'setsize':
				case 'paddedimage':
				case 'croppedimage':
					$this->owner->$method($size[0], $size[1]);
					break;

				case 'setwidth':
					$this->owner->$method($size[0]);
					break;

				case 'setheight':
					$this->owner->$method($size[1]);
					break;

				case 'setratiosize':
					if(strpos($arguments, $size[0]) === 0) {
						$this->owner->$method($size[0], substr($arguments, strlen($size[0])));
					} else {
						$this->owner->$method($size[1], substr($arguments, 0, strlen($size[0]) * -1));
					}
					break;

				default:
					$this->owner->$method($arguments);
					break;
			}
		}
	}
}
?>