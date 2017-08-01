<?php

/**
 *	APIwesome extension which displays the current security token (allowing regeneration for an administrator).
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class APIwesomeTokenExtension extends Extension {

	/**
	 *	Display the current security token (allowing regeneration for an administrator).
	 */

	public function updateEditForm(&$form) {

		// Determine whether the security section is being used.

		if($this->owner instanceof SecurityAdmin) {
			$gridfield = null;
			foreach($form->fields->items[0]->Tabs()->first()->Fields() as $field) {
				if($field instanceof GridField) {
					$gridfield = $field;
					break;
				}
			}
		}
		else {
			$gridfield = $form->fields->items[0];
		}
		if(isset($gridfield) && ($gridfield instanceof GridField)) {

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
