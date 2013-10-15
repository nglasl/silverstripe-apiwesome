<?php

/**
 *	APIwesome extension which allows JSON/XML visibility customisation of an individual data object's attributes.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class DataObjectOutputExtension extends DataExtension {

	/**
	 *	Append an additional JSON/XML output visibility field to each data object.
	 */

	public static $db = array(
		'APIwesomeVisibility' => 'Text'
	);

	/**
	 *	Hide the CMS JSON/XML output visibility.
	 */

	public function updateCMSFields(FieldList $fields) {

		$fields->removeByName('APIwesomeVisibility');
	}

}
