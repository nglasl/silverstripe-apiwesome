<?php

/**
 *	An extension to remove the add button from the APIwesomeAdmin interface.
 *	These data object JSON/XML configurations are automatically created on project build.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdminButtonExtension extends Extension {

	/**
	 *	Update the model admin interface to remove the add functionality.
	 */

	public function updateEditForm(&$form) {

		$configuration = $form->fields->dataFieldByName('DataObjectConfiguration')->config;
		$configuration->removeComponent($configuration->getComponentByType('GridFieldAddNewButton'));
	}

}
