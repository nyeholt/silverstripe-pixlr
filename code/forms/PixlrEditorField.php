<?php

/**
 * A Form field which can be used to display a button which will
 * trigger a pixlr editor to appear. 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class PixlrEditorField extends FormField
{
    /**
     * Set this to true if you have created a top level crossdomain.xml file. There's an example in
     * the module's directory if you wish to use that.
     *
     * Additionally, if you are running on a lan or local network with no ability
     * for pixlr to request the image publicly from your server over the internet,
     * leave this false and the image will be transferred to pixlr instead.
     *
     * @var boolean
     */
    private static $use_credentials = false;

    /**
     * What the user sees
     *
     * @var String
     */
    private $label;

    /**
     * The URL that the user is sent back to when they 'save' in pixlr
     *
     * @var String
     */
    private $targetUrl;

    /**
     * The url to go to if the user clicks on "Exit" in pixlr
     *
     * @var String
     */
    private $exitUrl;

    /**
     * Additional parameters to send through to pixlr that then get sent back to the save handler
     *
     * Handy for passing additional IDs etc through
     *
     * @var String
     */
    private $returnParams;
    
    /**
     *
     * @var string 
     */
    private $editorMode = 'express';

    /**
     *
     * @param String $name
     * @param String $title
     * @param Image $value
     * @param array $params
     *			Any additional parameters that should be sent back to the save handler when pixlr calls us back
     * @param String $targetUrl
     *			The URL that pixlr returns data back to
     * @param String $exitUrl
     *			The URL called if pixlr is closed without saving any data
     */
    public function __construct($name, $title = '', $value = '', $returnParams = array(), $targetUrl = '', $exitUrl = '')
    {
        $this->label = $title;
        $this->targetUrl = $targetUrl;
        $this->exitUrl = $exitUrl;
        $this->returnParams = $returnParams;
        
        parent::__construct($name, '', $value);
    }
    
    public function setEditorMode($mode)
    {
        $this->editorMode = $mode;
        return $this;
    }

    public function Field($properties = array())
    {
        Requirements::javascript('pixlr/javascript/pixlr.js');
        Requirements::javascript('pixlr/javascript/pixlr.jquery.js');
        Requirements::javascript('pixlr/javascript/pixlr-image-field.js');

        $fieldAttributes = array(
            'id' => $this->id(),
            'class' => 'pixlrTrigger',
            'value' => Convert::raw2att($this->label),
            'type' => 'button',
        );

        $targetUrl = strlen($this->targetUrl) ? $this->targetUrl : Director::absoluteURL('pixlr/saveimage');
        $exitUrl = strlen($this->exitUrl) ? $this->exitUrl : Director::absoluteURL('pixlr/closepixlr');

        // now add any additional parameters onto the end of the target string
        $sep = strpos($targetUrl, '?') ? '&amp;' : '?';

        foreach ($this->returnParams as $param => $v) {
            $targetUrl .= $sep . $param . '=' . $v;
            $sep = '&';
        }

        $loc = ($m = Member::currentUser()) ? i18n::get_lang_from_locale($m->Locale) : 'en';
        
        $title = isset($this->returnParams['title']) ? $this->returnParams['title'] : 'New Image';
        
        $opts = array(
            'referrer' => Convert::raw2js('SilverStripe CMS'),
            'loc' => $loc,
            'title' => $this->value && is_object($this->value) ? Convert::raw2js($this->value->Name) : $title,
            'locktarget' => 'true',
            'exit' => $exitUrl,
            'target' => $targetUrl,
            'method' => 'get',
            'copy'    => false,
        );

        // where should we open the editor? as in, which window is it rooted in? 
        $openin = 'window';

        if ($this->value) {
            if ($this->config()->use_credentials) {
                $opts['credentials'] = 'true';
                $opts['image'] = Convert::raw2js(Director::absoluteBaseURL() . $this->value->Filename);
            } else {
                // need to post the image to their server first, so we'll stick the image ID into the
                // page, and let the jquery plugin handle uploading it to pixlr first
                if (is_object($this->value)) {
                    $opts['id'] = $this->value->ID;
                } else {
                    $opts['id'] = $this->value;
                }

                $opts['preload'] = Director::absoluteURL('pixlr/sendimage');

                // In silverstripe, when editing an image it actually occurs in an iframe popup contained within
                // another iframe. Because we want the editor to appear mounted in the top level window,
                // we have to explicitly add it to the correct location
//				$openin = 'window.parent.parent';
            }

            $opts['locktitle'] = 'true';
            $opts['mode'] = 'popup';
        }
        
        $opts['service'] = $this->editorMode;

//		$opts = Convert::raw2json($opts);

        $opts['openobjname'] = $openin;
        $fieldAttributes['data-pixlr-editor'] = json_encode($opts);

//		$script = <<<JSCRIPT
//jQuery().ready(function () {
//var opts = $opts;
//opts.openin = $openin;
//jQuery('#{$this->id()}').pixlrize(opts);
//});
//JSCRIPT;

//		Requirements::customScript($script, 'pixlr-'.$this->id());
        return $this->createTag('input', $fieldAttributes);
    }
}
