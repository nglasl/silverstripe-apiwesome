<?php

/**
 *	APIwesome extension which allows JSON/XML preview capability for an individual data object type through the CMS interface.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class ModelAdminPreviewExtension extends Extension {

	/**
	 *	Add the CMS JSON/XML preview buttons.
	 */

	public function updateEditForm(&$form) {

		$gridfield = $form->fields->items[0];
		if(isset($gridfield) && ($gridfield->name !== 'DataObjectOutputConfiguration')) {

			// Make sure the appropriate JSON/XML exists for this data object type.

			$objects = singleton('APIwesomeService')->retrieveValidated($gridfield->name);
			if($objects) {
				Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
				Requirements::javascript(APIWESOME_PATH . '/javascript/apiwesome.js');
				$configuration = $gridfield->config;
				$configuration->addComponent(new APIwesomePreviewButton());
			}
		}
	}

}
