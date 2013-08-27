<?php

/**
 *	An extension to allow JSON/XML preview capability from inside the model admin interface.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class ModelAdminPreviewExtension extends Extension {

	/**
	 *	Update the model admin interface to add these buttons.
	 */

	public function updateEditForm(&$form) {

		if($form->fields->items[0]->name !== 'DataObjectOutputConfiguration') {
			$objects = singleton('APIwesomeService')->validate($form->fields->items[0]->name);
			if($objects) {
				$configuration = $form->fields->items[0]->config;
				Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
				$configuration->addComponent(new APIwesomePreviewButton());
			}
		}
	}

}
