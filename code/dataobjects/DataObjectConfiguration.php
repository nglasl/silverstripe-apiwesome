<?php

/**
 *	The CMS data object configuration so an individual JSON/XML feed may be customised.
 *	@author <nathan@silverstripe.com.au>
 */

class DataObjectConfiguration extends DataObject {

	public static $db = array(
		'CallbackFunction' => 'Text'
	);

	public static $has_one = array(
		'IsFor' => 'DataObject'
	);

}
