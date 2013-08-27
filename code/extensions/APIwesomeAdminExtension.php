<?php

/**
 *	An extension to remove the add button from the APIwesomeAdmin interface.
 *	These data object JSON/XML configurations are automatically created on project build.
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
