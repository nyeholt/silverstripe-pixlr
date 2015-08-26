<?php

/**
 * Tests to make sure the html editor gets the pixlr 'create new' button inserted 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class TestHtmlEditorFieldPixlrExtension extends FunctionalTest
{
    public function testPixlrEditorExtension()
	{
		$controller = new ContentController();
		$toolbar = new HtmlEditorField_Toolbar($controller, 'DummyToolbar');
		$imageForm = $toolbar->ImageForm();
		// we expect the image form to now have the pixlr component inside
		$fields = $imageForm->Fields();
		$this->assertNotNull($fields->dataFieldByName('NewPixlrImage'));
	}
}
