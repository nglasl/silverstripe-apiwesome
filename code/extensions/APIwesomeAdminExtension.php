<?php

/**
 *	APIwesome extension which removes the manual APIwesomeAdmin add functionality, and displays the current security token (allowing regeneration for an administrator).
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdminExtension extends Extension {

	/**
	 *	Remove the CMS add button, and display the security token functionality.
	 */

	public function updateEditForm(&$form) {

		$gridfield = $form->fields->items[0];
		if(isset($gridfield)) {
			$configuration = $gridfield->config;
			$configuration->removeComponentsByType('GridFieldAddNewButton');

			// Restrict the security token to administrators.

			$user = Member::currentUserID();
			if(Permission::checkMember($user, 'ADMIN')) {
				$configuration->addComponent(new APIwesomeTokenView());
			}
		}
	}

}
