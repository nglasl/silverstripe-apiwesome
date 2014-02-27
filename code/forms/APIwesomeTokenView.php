<?php

/**
 *	APIwesome functionality to display the current security token (allowing regeneration).
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeTokenView implements GridField_HTMLProvider {

	/**
	 *	Render the current security token and button for regeneration.
	 */

	public function getHTMLFragments($gridfield) {

		// Temporarily retrieve the session value, preventing storage vulnerabilities.

		$token = Session::get('APIwesomeToken');
		$status = ($token !== -1) ? 'token' : 'error';
		$token = ($token && ($token !== -1)) ? "<div>{$token}</div>" : '';
		return array(
			'before' => "<div class='apiwesome admin {$status}'>
				<div><strong>Security Token</strong></div>
				{$token}
				<a href='/apiwesome/regenerate' class='regenerate ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='arrow-circle-double'>Regenerate &raquo;</a>
			</div>"
		);
	}

}
