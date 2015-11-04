<?php

/**
 *	APIwesome extension which displays the current security token (allowing regeneration for an administrator).
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeTokenExtension extends Extension {

	/**
	 *	Add the security token functionality.
	 */

	public function updateEditForm(&$form) {

		// Determine whether the security section is being used.

		$security = ($this->owner instanceof SecurityAdmin);
		$gridfield = $security ? $form->fields->items[0]->Tabs()->first()->fieldByName('Members') : $form->fields->items[0];
		if(isset($gridfield) && $gridfield instanceof GridField) {

			// Restrict the security token to administrators.

			$user = Member::currentUserID();
			if(Permission::checkMember($user, 'ADMIN')) {
				Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');

				// Display a confirmation message when regenerating the security token.

				Requirements::javascript(APIWESOME_PATH . '/javascript/apiwesome.js');
				$configuration = $gridfield->config;
				$configuration->addComponent(new APIwesomeTokenView());
			}
		}
	}

}
