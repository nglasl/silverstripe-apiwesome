<?php

/**
 *	APIwesome extension which allows JSON/XML preview capability for an individual data object type through the CMS interface.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class ModelAdminPreviewExtension extends Extension {

	/**
	 *	Update the model admin interface to add these buttons.
	 */

	public function updateEditForm(&$form) {

		if($form->fields->items[0]->name !== 'DataObjectOutputConfiguration') {
			$objects = singleton('APIwesomeService')->retrieveValidated($form->fields->items[0]->name);
			if($objects) {
				$configuration = $form->fields->items[0]->config;
				Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
				$configuration->addComponent(new APIwesomePreviewButton());
			}
		}
	}

}
