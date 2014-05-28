<?php

/**
 *	APIwesome button which will provide JSON/XML preview capability for the ModelAdminPreviewExtension.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomePreviewButton implements GridField_HTMLProvider {

	/**
	 *	Render the CMS JSON/XML preview buttons.
	 */

	public function getHTMLFragments($gridfield) {

		$object = strtolower(ltrim(preg_replace('/[A-Z]+[^A-Z]/', '-$0', $gridfield->name), '-'));
		return array(
			'before' => "<div class='apiwesome admin'>
				<div><strong>Security Token</strong></div>
				<div><input class='preview token' spellcheck='false'></input></div>
				<a href='/apiwesome/retrieve/{$object}/json' target='_blank' class='preview json disabled ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='preview'>Preview JSON &raquo;</a>
				<br>
				<a href='/apiwesome/retrieve/{$object}/xml' target='_blank' class='preview xml disabled ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='preview'>Preview XML &raquo;</a>
			</div>"
		);
	}

}
