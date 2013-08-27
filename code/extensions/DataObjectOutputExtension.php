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

		$fields->removeByName('APIwesomeVisibility');
	}

}
