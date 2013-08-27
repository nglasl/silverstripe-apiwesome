<?php

/**
 *	An button that will appear for a data object's model admin to allow JSON/XML preview functionality.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomePreviewButton implements GridField_HTMLProvider {

	public function getHTMLFragments($gridField) {

		$name = strtolower(ltrim(preg_replace('/[A-Z]+[^A-Z]/', '-$0', $gridField->name), '-'));
		return array(
			'before' => "<p>
				<a href='" . BASE_URL . "/apiwesome/retrieve/{$name}/json' target='_blank' class='apiwesome preview json ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='preview' role='button' aria-disabled='false'>Preview JSON &raquo;</a>
				<br>
				<a href='" . BASE_URL . "/apiwesome/retrieve/{$name}/xml' target='_blank' class='apiwesome preview xml ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='preview' role='button' aria-disabled='false'>Preview XML &raquo;</a>
			</p>"
		);
	}

}
