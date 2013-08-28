<?php

/**
 *	APIwesome extension which removes the manual add functionality of the APIwesomeAdmin CMS interface.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdminExtension extends Extension {

	/**
	 *	Update the model admin interface to remove the add button.
	 */

	public function updateEditForm(&$form) {

		$configuration = $form->fields->items[0]->config;
		$configuration->removeComponent($configuration->getComponentByType('GridFieldAddNewButton'));
	}

}
