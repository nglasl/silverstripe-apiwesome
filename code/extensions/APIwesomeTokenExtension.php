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

		// Determine whether the security section is being viewed.

		$security = ($this->owner instanceof SecurityAdmin);
		$gridfield = $security ? $form->fields->items[0]->Tabs()->first()->fieldByName('Members') : $form->fields->items[0];
		if(isset($gridfield) && $gridfield instanceof GridField) {
			$configuration = $gridfield->config;

			// Restrict the security token to administrators.

			$user = Member::currentUserID();
			if(Permission::checkMember($user, 'ADMIN')) {

				// Correct the button styling and implement a confirmation message for security token regeneration.

				Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
				Requirements::javascript(APIWESOME_PATH . '/javascript/apiwesome.js');
				$configuration->addComponent(new APIwesomeTokenView());
			}
		}
	}

}
