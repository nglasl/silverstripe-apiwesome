<?php

/**
 *	APIwesome extension which removes the manual add functionality of the APIwesomeAdmin CMS interface.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdminExtension extends Extension {

	/**
	 *	Remove the CMS add button.
	 */

	public function updateEditForm(&$form) {

		$gridfield = $form->fields->items[0];
		if(isset($gridfield)) {
			$configuration = $gridfield->config;
			$configuration->removeComponent($configuration->getComponentByType('GridFieldAddNewButton'));
		}
	}

}
