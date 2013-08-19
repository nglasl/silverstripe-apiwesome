<?php

/**
 *	An extension to allow each data object JSON/XML preview capability, including some additional visibility fields for output customisation.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class DataObjectOutputExtension extends DataExtension {

	// Append an additional visibility field to each data object, which will allow the capability of extending APIwesome even further.

	public static $db = array(
		'APIwesomeVisibility' => 'TEXT'
	);

	/**
	 *	Update the CMS interface fields, since the visibility must not be changed.
	 */

	public function updateCMSFields(FieldList $fields) {

		Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
		$name = strtolower(ltrim(preg_replace('/[A-Z]+[^A-Z]/', '-$0', $this->owner->ClassName), '-'));
		$fields->addFieldToTab('Root.Main', LiteralField::create('APIwesomePreview', "<div class='field'>
			<a href='" . BASE_URL . "/apiwesome/retrieve/{$name}/json' target='_blank' class='apiwesome preview json ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='preview' role='button' aria-disabled='false'>Preview JSON &raquo;</a>
			<br>
			<a href='" . BASE_URL . "/apiwesome/retrieve/{$name}/xml' target='_blank' class='apiwesome preview xml ss-ui-action-constructive ss-ui-button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary' data-icon='preview' role='button' aria-disabled='false'>Preview XML &raquo;</a>
		</div>"), true);
		$fields->removeByName('APIwesomeVisibility');
	}

}
