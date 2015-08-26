<?php
/*
/**
 * Decorator that attaches an edit button to images when displayed in the
 * Files & Images section of the website
 *
 * Note that this decorator is actually attached to File objects; this is to
 * provide support for the DataObjectManager module, which doesn't
 * explicitly provide support for Image classes. Hence, we have to
 * add to all files and catch whether it's an image or not here. 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class PixlrImageDecorator extends DataExtension
{
	private static $db = array(
		'TransactionKey' => 'Varchar(32)',
	);

	public function updateCMSFields(FieldList $fields) {
		if (!$this->owner instanceof Image) {
			return;
		}

		$tabName = 'Root.Main';



		// okay, because we're editing, we want to make sure that our editing transaction is sane - if someone else
		// starts editing after us, the user is instead prompted to save imagename.TRANSACTIONID.ext. This works around
		// the problem of locking images whereby we need a way to maintain the lock, even if that edit takes
		// a long long time. Also, it lets other people edit at the same time, so that even if we overwrite each,
		// other's work, both people are made aware of it, and a consistent version history is maintained.
		// Be aware that this transaction key is added BEFORE we actually use pixlr, but if we don't end up using it,
		// it doesn't matter. It is ALWAYS overridden by the next user to attempt to edit, and just prompts the user
		// when they return to silverstripe that someone else has edited in the meantime. It's up to the user then
		// to decide to overwrite (which is fine, if versioning is enabled) or save as a differently named file
		$this->owner->TransactionKey = md5(Member::currentUserID().time());
		$this->owner->write();

		$params = array('parent' => $this->owner->ParentID, 'transaction' => $this->owner->TransactionKey, 'imgstate' => 'existing');

		$fields->removeByName('TransactionKey');
		$fields->addFieldToTab($tabName, new PixlrEditorField('PixlrButton', _t('Pixlr.EDIT_IMAGE', 'Edit this image'), $this->owner, $params));
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
			sprintf("/([a-zA-Z]+)([0-9]*)-%s/", preg_quote($this->owner->Name)),
			RegexIterator::GET_MATCH
		);

		// grab each resampled image and regenerate it
		foreach($filter as $cachedImage) {
			$path      = "$resampled/{$cachedImage[0]}";
			if (!file_exists($path)) {
				continue;
			}
			$size      = getimagesize($path);
			$method    = $cachedImage[1];
			$arguments = $cachedImage[2];

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
